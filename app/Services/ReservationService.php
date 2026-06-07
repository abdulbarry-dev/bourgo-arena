<?php

namespace App\Services;

use App\DTOs\StoreReservationDTO;
use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\Member;
use App\Repositories\ReservationRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected TierResolutionService $tierResolutionService,
        protected ReservationRepository $reservationRepository
    ) {}

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
    public function assertNoActiveReservationForSlot(Member $member, int $activitySlotId, string $date): void
    {
        $exists = $this->reservationRepository->hasActiveReservationForSlot($member, $activitySlotId, $date);

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
