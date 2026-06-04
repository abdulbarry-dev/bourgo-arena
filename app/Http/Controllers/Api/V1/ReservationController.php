<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\PaymentInitiateDTO;
use App\DTOs\StoreReservationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReservationRequest;
use App\Http\Resources\Api\ApiReservationResource;
use App\Models\ApiReservation;
use App\Models\Payment;
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

    /**
     * Initiate a payment for an existing reservation (deposit or due amount).
     */
    public function initiatePayment(Request $request, ApiReservation $reservation)
    {
        $this->authorize('view', $reservation);

        $gateway = $request->input('gateway', null);
        if ($gateway !== null && ! in_array($gateway, ['konnect', 'flouci'], true)) {
            return $this->error('Unsupported payment gateway', 422);
        }

        $amount = $reservation->price; // default to full price unless caller specifies
        if ($request->filled('amount')) {
            $amount = (float) $request->input('amount');
        }

        $dto = new PaymentInitiateDTO(
            memberId: $request->user()->id,
            reservationId: $reservation->id,
            subscriptionId: null,
            amount: $amount,
            currency: 'TND',
            description: 'Reservation payment for reservation #'.$reservation->id,
            type: 'reservation',
            paymentReference: null,
            metadata: null,
            provider: $gateway,
        );

        // Ensure deposit amount equals 10% of reservation price
        $expectedDeposit = round($reservation->price * 0.10, 3);
        if (abs($dto->amount - $expectedDeposit) > 0.001) {
            return $this->error('Deposit amount must be 10% of reservation price', 422);
        }

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

    /**
     * Verify a payment for a reservation and mark reservation as paid when appropriate.
     */
    public function verifyPayment(Request $request, ApiReservation $reservation)
    {
        $this->authorize('view', $reservation);

        $paymentId = $request->query('payment_id');
        if (! $paymentId) {
            return $this->error('payment_id is required', 422);
        }

        $payment = Payment::query()->where('id', $paymentId)->where('reservation_id', $reservation->id)->first();
        if (! $payment) {
            return $this->error('Payment not found', 404);
        }

        $result = $this->paymentService->verify($payment, null);

        if (! empty($result['success'])) {
            // mark reservation paid
            $reservation->update(['payment_status' => 'paid', 'status' => 'confirmed']);

            try {
                $this->loyaltyCalculatorService->creditVariableForReservation($reservation);
            } catch (\Throwable $e) {
                // best-effort
            }

            return $this->success(['status' => 'paid'], 'Payment verified');
        }

        return $this->error('Payment not successful', 400);
    }

    /**
     * Display a listing of the member's reservations.
     *
     * @return AnonymousResourceCollection<ApiReservationResource>
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $reservations = $request->user()->reservations()
            ->with(['activity', 'slot'])
            ->orderBy('date', 'desc')
            ->paginate(10);

        return $this->paginated($reservations, ApiReservationResource::class);
    }

    /**
     * Store a new reservation.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $dto = StoreReservationDTO::fromRequest($request->validated());

        $this->reservationService->assertNoActiveReservationForSlot($request->user(), $dto->activitySlotId);

        $reservation = $this->reservationService->makeActivityReservation($request->user(), $dto);

        if (! config('payment.konnect.api_key') || ! config('payment.konnect.api_secret')) {
            $reservation->update(['payment_status' => 'paid']);

            // Credit loyalty for the reservation (best-effort)
            try {
                $this->loyaltyCalculatorService->creditVariableForReservation($reservation);
            } catch (\Throwable $e) {
                // do not block reservation creation on loyalty failures
            }

            return (new ApiReservationResource($reservation->load(['activity', 'slot'])))->additional([
                'success' => true,
                'message' => 'Reservation created successfully',
            ])->response()->setStatusCode(201);
        }

        // Create deposit payment (10%) and initiate checkout
        $depositAmount = round($reservation->price * 0.10, 3);

        $paymentDto = new PaymentInitiateDTO(
            memberId: $request->user()->id,
            reservationId: $reservation->id,
            subscriptionId: null,
            amount: $depositAmount,
            currency: 'TND',
            description: null,
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
            return (new ApiReservationResource($reservation->load(['activity', 'slot'])))->additional([
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

    /**
     * Cancel a reservation.
     */
    public function destroy(ApiReservation $reservation): JsonResponse
    {
        $this->authorize('delete', $reservation);

        $this->reservationService->cancelActivityReservation($reservation);

        return $this->success(null, 'Reservation cancelled successfully');
    }
}
