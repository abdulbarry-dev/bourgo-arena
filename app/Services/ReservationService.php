<?php

namespace App\Services;

use App\DTOs\StoreReservationDTO;
use App\Models\Activity;
use App\Models\ActivityTimeSlot;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use App\Repositories\ReservationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected TierResolutionService $tierResolutionService,
        protected ReservationRepository $reservationRepository
    ) {}

    public function makeActivityReservation(Member $member, StoreReservationDTO $dto): ApiReservation
    {
        return DB::transaction(function () use ($member, $dto) {
            $session = $this->reservationRepository->lockSessionForUpdate($dto->activitySessionId);
            $activity = $this->reservationRepository->getActivity($dto->activityId);

            $alreadyReserved = ApiReservation::where('activity_session_id', $dto->activitySessionId)
                ->whereDate('date', $dto->date)
                ->where('status', '!=', 'cancelled')
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages([
                    'activity_session_id' => ['This activity session is already reserved for this date.'],
                ]);
            }

            $price = $this->calculateReservationPrice($member, $activity);

            $reservation = $this->reservationRepository->createReservation([
                'member_id' => $member->id,
                'activity_id' => $dto->activityId,
                'activity_session_id' => $dto->activitySessionId,
                'date' => $dto->date,
                'price' => $price,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $this->reservationRepository->updateReservation($reservation, [
                'qr_code' => hash('sha256', $reservation->id.$member->id.now()),
            ]);

            return $reservation;
        });
    }

    public function assertNoActiveReservationForSession(Member $member, int $sessionId, string $date): void
    {
        $reservation = ApiReservation::where('member_id', $member->id)
            ->where('activity_session_id', $sessionId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($reservation === null) {
            return;
        }

        if ($this->isStaleReservation($reservation)) {
            $reservation->payments()
                ->whereIn('status', ['pending', 'initiated'])
                ->get()
                ->each(function (Payment $payment): void {
                    $payment->update([
                        'status' => 'failed',
                        'metadata' => array_merge($payment->metadata ?? [], [
                            'cancelled_reason' => 'stale_reservation',
                        ]),
                    ]);
                });

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return;
        }

        throw ValidationException::withMessages([
            'activity_session_id' => ['You already have an active reservation for this session.'],
        ]);
    }

    protected function calculateReservationPrice(Member $member, Activity $activity): float
    {
        $tier = $this->tierResolutionService->resolveTier($member);
        $discount = (float) config('loyalty.pricing_discounts.'.$tier->currentTier->label, 0.0);

        $discount = max(0.0, min(1.0, $discount));
        $basePrice = (float) $activity->base_price;

        return round(max(0.0, $basePrice * (1 - $discount)), 2);
    }

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
        });
    }

    public function createReservation(
        User $user,
        int $activityId,
        int $timeSlotId,
        bool $requiresPayment,
        string $paymentGateway
    ): Reservation {
        return DB::transaction(function () use ($user, $activityId, $timeSlotId, $requiresPayment, $paymentGateway) {
            $slot = ActivityTimeSlot::lockForUpdate()->findOrFail($timeSlotId);
            $activity = Activity::findOrFail($activityId);

            $alreadyReserved = Reservation::where('activity_time_slot_id', $timeSlotId)
                ->whereIn('reservation_status', ['pending_payment', 'confirmed'])
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages([
                    'time_slot_id' => ['This slot is already reserved.'],
                ]);
            }

            $exists = Reservation::where('user_id', $user->id)
                ->where('activity_time_slot_id', $timeSlotId)
                ->whereIn('reservation_status', ['pending_payment', 'confirmed'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'time_slot_id' => ['You already have a reservation for this slot.'],
                ]);
            }

            $fullAmount = $activity->base_price;
            $depositAmount = $fullAmount * 0.1;

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'activity_id' => $activityId,
                'activity_time_slot_id' => $timeSlotId,
                'reservation_status' => $requiresPayment ? 'pending_payment' : 'confirmed',
                'payment_status' => 'not_initiated',
                'deposit_amount' => $depositAmount,
                'full_amount' => $fullAmount,
                'payment_gateway' => $paymentGateway,
                'transaction_reference' => null,
            ]);

            return $reservation;
        });
    }

    public function cancelReservation(Reservation $reservation, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $reason) {
            $reservation->update([
                'reservation_status' => 'cancelled',
                'cancellation_reason' => $reason,
            ]);

            return $reservation;
        });
    }

    private function isStaleReservation(ApiReservation $reservation): bool
    {
        $timeout = (int) config('payment.subscription.pending_timeout_minutes', 30);

        if ($reservation->created_at->diffInMinutes(now()) < $timeout) {
            return false;
        }

        return ! $reservation->payments()
            ->whereIn('status', ['pending', 'initiated'])
            ->where('updated_at', '>=', now()->subMinutes($timeout))
            ->exists();
    }
}
