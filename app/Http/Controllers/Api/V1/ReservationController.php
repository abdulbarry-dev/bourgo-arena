<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PaymentInitiateDTO;
use App\DTOs\StoreReservationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReservationRequest;
use App\Http\Resources\Api\ApiReservationResource;
use App\Models\ApiReservation;
use App\Services\LoyaltyCalculatorService;
use App\Services\PaymentService;
use App\Services\ReservationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ReservationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected ReservationService $reservationService,
        protected LoyaltyCalculatorService $loyaltyCalculatorService,
        protected PaymentService $paymentService
    ) {}

    public function ongoing(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['activity', 'session'])
            ->where('status', 'confirmed')
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(10);

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    public function history(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['activity', 'session'])
            ->where(function ($query) {
                $query->where('status', '!=', 'confirmed')
                    ->orWhere('date', '<', now()->toDateString());
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    public function store(StoreReservationRequest $request): JsonResponse
    {
        $dto = StoreReservationDTO::fromRequest($request->validated());

        $this->reservationService->assertNoActiveReservationForSession($request->user(), $dto->activitySessionId, $dto->date);

        $reservation = $this->reservationService->makeActivityReservation($request->user(), $dto);

        $depositAmount = round($reservation->price * 0.10, 3);

        $paymentDto = new PaymentInitiateDTO(
            memberId: $request->user()->id,
            reservationId: $reservation->id,
            subscriptionId: null,
            amount: $depositAmount,
            description: 'Reservation deposit for activity #'.$reservation->activity_id,
            type: 'reservation_deposit',
            paymentReference: null,
            metadata: null
        );

        $payment = $this->paymentService->createPayment($paymentDto);

        try {
            $result = $this->paymentService->initiate($payment, [
                'description' => 'Reservation deposit for activity #'.$reservation->activity_id,
                'success_url' => $request->input('success_url', config('app.url')),
                'failure_url' => $request->input('failure_url', config('app.url')),
            ]);
        } catch (\Throwable $e) {
            $this->paymentService->markFailed($payment, ['error' => $e->getMessage()]);

            return $this->error('Payment initiation failed', 500);
        }

        if (! empty($result['success'])) {
            return (new ApiReservationResource($reservation->load(['activity', 'session'])))->additional([
                'success' => true,
                'message' => 'Reservation created successfully',
                'payment' => [
                    'id' => $payment->id,
                    'payment_url' => $result['payment_url'] ?? null,
                    'payment_reference' => $payment->payment_reference,
                ],
            ])->response()->setStatusCode(201);
        }

        $this->paymentService->markFailed($payment, $result);

        return $this->error('Payment initiation failed', 400);
    }

    public function initiatePayment(Request $request, ApiReservation $reservation)
    {
        $this->authorize('view', $reservation);

        $amount = $reservation->price;
        if ($request->filled('amount')) {
            $amount = (float) $request->input('amount');
        }

        $dto = new PaymentInitiateDTO(
            memberId: $request->user()->id,
            reservationId: $reservation->id,
            subscriptionId: null,
            amount: $amount,
            description: 'Reservation payment for reservation #'.$reservation->id,
            type: 'reservation',
            paymentReference: null,
            metadata: null
        );

        $payment = $this->paymentService->createPayment($dto);

        try {
            $result = $this->paymentService->initiate($payment, [
                'description' => $dto->description,
                'success_url' => $request->input('success_url', config('app.url')),
                'failure_url' => $request->input('failure_url', config('app.url')),
            ]);
        } catch (\Throwable $e) {
            $this->paymentService->markFailed($payment, ['error' => $e->getMessage()]);

            return $this->error('Payment initiation failed', 500);
        }

        if (! empty($result['success'])) {
            return $this->success([
                'payment' => [
                    'id' => $payment->id,
                    'payment_url' => $result['payment_url'] ?? null,
                    'payment_reference' => $payment->payment_reference,
                ],
            ], 'Payment initiated');
        }

        $this->paymentService->markFailed($payment, $result);

        return $this->error('Payment initiation failed', 400);
    }

    public function verifyPayment(Request $request, ApiReservation $reservation)
    {
        $this->authorize('view', $reservation);

        $paymentId = $request->query('payment_id');
        $payment = $reservation->payments()->findOrFail($paymentId);

        $result = $this->paymentService->verify($payment);

        if (! empty($result['success']) && $result['status'] === 'paid') {
            $reservation->update(['payment_status' => 'paid']);
        }

        return $this->success($result, 'Payment verification completed');
    }

    public function destroy(ApiReservation $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        if ($reservation->payment_status === 'paid') {
            return $this->error(__('Paid reservations cannot be cancelled. Please contact an administrator.'), 403);
        }

        $this->reservationService->cancelActivityReservation($reservation);

        return $this->success(null, 'Reservation cancelled successfully');
    }
}
