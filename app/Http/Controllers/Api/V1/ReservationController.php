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
