<?php

namespace App\Services;

use App\DTOs\StoreReservationDTO;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ActivityTimeSlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Reservation;
use App\Models\ReservationStateLog;
use App\Models\User;
use App\Repositories\ReservationRepository;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected TierResolutionService $tierResolutionService,
        protected ReservationRepository $reservationRepository,
        protected PaymentManager $paymentManager,
        protected PaymentAuditService $paymentAuditService
    ) {}

    /**
     * Log reservation state transitions in a polymorphic state log.
     */
    protected function logStateChange($reservation, ?string $from, string $to, ?string $reason = null): void
    {
        try {
            ReservationStateLog::create([
                'reservationable_type' => $reservation::class,
                'reservationable_id' => $reservation->id,
                'from_status' => $from,
                'to_status' => $to,
                'reason' => $reason,
                'changed_by' => auth()->id() ?? null,
            ]);
        } catch (\Throwable $e) {
            // best effort: do not break main flow on logging failure
        }
    }

    /**
     * Create a reservation while holding a row lock on the selected time slot.
     *
     * @throws ValidationException
     */
    public function createReservation(
        User $user,
        int $activityId,
        int $timeSlotId,
        bool $requiresPayment = true,
        ?string $paymentGateway = null
    ): Reservation {
        return DB::transaction(function () use ($user, $activityId, $timeSlotId, $requiresPayment, $paymentGateway): Reservation {
            $slot = ActivityTimeSlot::query()
                ->lockForUpdate()
                ->findOrFail($timeSlotId);

            $activity = Activity::query()->findOrFail($activityId);

            if ($slot->activity_id !== $activity->id) {
                throw ValidationException::withMessages([
                    'activity_time_slot_id' => ['The selected time slot does not belong to this activity.'],
                ]);
            }

            if (! $slot->is_available || $slot->isFullyBooked()) {
                throw ValidationException::withMessages([
                    'activity_time_slot_id' => ['This time slot is no longer available.'],
                ]);
            }

            $alreadyReserved = Reservation::query()
                ->where('user_id', $user->id)
                ->where('activity_time_slot_id', $slot->id)
                ->whereIn('reservation_status', ['pending_payment', 'confirmed'])
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages([
                    'activity_time_slot_id' => ['You already have a reservation for this time slot.'],
                ]);
            }

            $fullAmount = round((float) $activity->base_price, 3);
            $depositAmount = round($fullAmount * 0.10, 3);

            $reservation = Reservation::query()->create([
                'user_id' => $user->id,
                'activity_id' => $activity->id,
                'activity_time_slot_id' => $slot->id,
                'reservation_status' => $requiresPayment ? 'pending_payment' : 'confirmed',
                'payment_status' => $requiresPayment ? 'not_initiated' : 'completed',
                'deposit_amount' => $depositAmount,
                'full_amount' => $fullAmount,
                'payment_gateway' => $requiresPayment ? ($paymentGateway ?? 'konnect') : null,
                'transaction_reference' => null,
                'refund_status' => 'not_requested',
            ]);

            // Log creation transition
            $this->logStateChange($reservation, null, $reservation->reservation_status, 'created');

            $reservedCount = $slot->reserved_count + 1;

            $slot->update([
                'reserved_count' => $reservedCount,
                'is_available' => $reservedCount < $slot->max_capacity,
            ]);

            return $reservation;
        }, attempts: 5);
    }

    /**
     * Cancel a reservation and reconcile slot inventory.
     *
     * @throws ValidationException
     */
    public function cancelReservation(Reservation $reservation, ?string $cancellationReason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $cancellationReason): Reservation {
            $reservation = Reservation::query()
                ->lockForUpdate()
                ->findOrFail($reservation->id);

            if (! in_array($reservation->reservation_status, ['pending_payment', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'reservation' => ['Only pending payment or confirmed reservations can be cancelled.'],
                ]);
            }

            $slot = ActivityTimeSlot::query()
                ->lockForUpdate()
                ->findOrFail($reservation->activity_time_slot_id);

            $oldStatus = $reservation->reservation_status;

            $reservation->update([
                'reservation_status' => 'cancelled',
                'cancellation_reason' => $cancellationReason,
            ]);

            $this->logStateChange($reservation, $oldStatus, 'cancelled', $cancellationReason);

            $reservedCount = max(0, $slot->reserved_count - 1);

            $slot->update([
                'reserved_count' => $reservedCount,
                'is_available' => $reservedCount < $slot->max_capacity,
            ]);

            if (in_array($reservation->payment_status, ['paid', 'completed'], true)) {
                $this->initiateRefund($reservation, null, $cancellationReason);
            }

            return $reservation->fresh();
        }, attempts: 5);
    }

    /**
     * Initiate a refund using the reservation payment gateway and audit the attempt.
     */
    public function initiateRefund(Reservation $reservation, ?float $amount = null, ?string $reason = null): array
    {
        $gateway = $reservation->payment_gateway ?? 'manual_admin';
        $refundAmount = round((float) ($amount ?? $reservation->deposit_amount ?? $reservation->full_amount), 3);
        $reference = $reservation->transaction_reference;

        $requestPayload = [
            'transaction_reference' => $reference,
            'amount' => $refundAmount,
            'reason' => $reason,
        ];

        if (! in_array($gateway, ['konnect'], true)) {
            $result = [
                'success' => false,
                'error' => 'Unsupported payment gateway for automatic refund.',
            ];

            $reservation->update(['refund_status' => 'pending']);

            $this->paymentAuditService->logStandalone([
                'transaction_id' => 'refund_'.Str::uuid(),
                'user_id' => $reservation->user_id,
                'reservation_id' => $reservation->id,
                'amount' => $refundAmount,
                'currency' => 'TND',
                'payment_gateway' => $gateway,
                'transaction_status' => 'refund_pending_manual',
                'external_gateway_reference' => $reference,
                'reservation_details' => [
                    'reservation_id' => $reservation->id,
                    'activity_id' => $reservation->activity_id,
                    'time_slot_id' => $reservation->activity_time_slot_id,
                ],
                'refund_status' => 'pending',
                'refund_amount' => $refundAmount,
                'refund_reference' => null,
                'refund_details' => [
                    'reason' => $reason,
                    'response' => $result,
                ],
                'request_payload' => $requestPayload,
                'response_payload' => $result,
            ]);

            return $result;
        }

        if ($reference === null || $reference === '') {
            $result = [
                'success' => false,
                'error' => 'Transaction reference is required to initiate a refund.',
            ];

            $reservation->update(['refund_status' => 'failed']);

            $this->paymentAuditService->logStandalone([
                'transaction_id' => 'refund_'.Str::uuid(),
                'user_id' => $reservation->user_id,
                'reservation_id' => $reservation->id,
                'amount' => $refundAmount,
                'currency' => 'TND',
                'payment_gateway' => $gateway,
                'transaction_status' => 'refund_failed',
                'external_gateway_reference' => null,
                'reservation_details' => [
                    'reservation_id' => $reservation->id,
                    'activity_id' => $reservation->activity_id,
                    'time_slot_id' => $reservation->activity_time_slot_id,
                ],
                'refund_status' => 'failed',
                'refund_amount' => $refundAmount,
                'refund_reference' => null,
                'refund_details' => [
                    'reason' => $reason,
                    'response' => $result,
                ],
                'request_payload' => $requestPayload,
                'response_payload' => $result,
            ]);

            return $result;
        }

        $provider = $this->paymentManager->driver($gateway);
        $result = $provider->refund($reference, $refundAmount);

        $refundSucceeded = ! empty($result['success']);
        $refundReference = $result['refund_id'] ?? null;

        $reservation->update([
            'refund_status' => $refundSucceeded ? 'completed' : 'failed',
            'payment_status' => $refundSucceeded ? 'refunded' : $reservation->payment_status,
        ]);

        $this->paymentAuditService->logStandalone([
            'transaction_id' => (string) ($refundReference ?? ('refund_'.Str::uuid())),
            'user_id' => $reservation->user_id,
            'reservation_id' => $reservation->id,
            'amount' => $refundAmount,
            'currency' => 'TND',
            'payment_gateway' => $gateway,
            'transaction_status' => $refundSucceeded ? 'refund_completed' : 'refund_failed',
            'external_gateway_reference' => $reference,
            'reservation_details' => [
                'reservation_id' => $reservation->id,
                'activity_id' => $reservation->activity_id,
                'time_slot_id' => $reservation->activity_time_slot_id,
            ],
            'refund_status' => $refundSucceeded ? 'completed' : 'failed',
            'refund_amount' => $refundAmount,
            'refund_reference' => $refundReference,
            'refunded_at' => $refundSucceeded ? now() : null,
            'refund_details' => [
                'reason' => $reason,
                'response' => $result,
            ],
            'request_payload' => $requestPayload,
            'response_payload' => $result,
        ]);

        return $result;
    }

    /**
     * Create a new activity reservation.
     *
     * @throws ValidationException
     */
    public function makeActivityReservation(Member $member, StoreReservationDTO $dto): ApiReservation
    {
        return DB::transaction(function () use ($member, $dto) {
            $slot = $this->reservationRepository->lockSlotForUpdate($dto->activitySlotId);
            $activity = $this->reservationRepository->getActivity($dto->activityId);

            if ($slot->isFullyBooked()) {
                throw ValidationException::withMessages([
                    'activity_slot_id' => ['This activity slot is already fully booked.'],
                ]);
            }

            $price = $this->calculateReservationPrice($member, $activity);

            $reservation = $this->reservationRepository->createReservation([
                'member_id' => $member->id,
                'activity_id' => $dto->activityId,
                'activity_slot_id' => $dto->activitySlotId,
                // The reservation date is provided by the caller (DTO)
                'date' => $dto->date,
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'price' => $price,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $this->reservationRepository->updateReservation($reservation, [
                'qr_code' => hash('sha256', $reservation->id.$member->id.now()),
            ]);

            $slot->increment('booked_count');

            return $reservation;
        });
    }

    /**
     * Ensure the member does not already have an active reservation for the slot.
     *
     * @throws ValidationException
     */
    public function assertNoActiveReservationForSlot(Member $member, int $activitySlotId): void
    {
        // We get the slot just to know the date
        // Since we don't need a lock here, we can just find it normally
        $slot = ActivitySlot::query()->findOrFail($activitySlotId);

        $exists = $this->reservationRepository->hasActiveReservationForSlot($member, $activitySlotId);

        if ($exists) {
            throw ValidationException::withMessages([
                'activity_slot_id' => ['You already have an active reservation for this slot.'],
            ]);
        }
    }

    protected function calculateReservationPrice(Member $member, Activity $activity): float
    {
        $tier = $this->tierResolutionService->resolveTier($member);
        $discount = (float) config('loyalty.pricing_discounts.'.$tier->currentTier->label, 0.0);

        $discount = max(0.0, min(1.0, $discount));
        $basePrice = (float) $activity->base_price;

        return round(max(0.0, $basePrice * (1 - $discount)), 2);
    }

    /**
     * Cancel an activity reservation.
     */
    public function cancelActivityReservation(ApiReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            if ($reservation->status === 'cancelled') {
                return;
            }

            $this->reservationRepository->updateReservation($reservation, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $reservation->slot()->decrement('booked_count');
        });
    }
}
