<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;

class ReservationRepository
{
    /**
     * Get a slot with a lock for update.
     */
    public function lockSlotForUpdate(int $slotId): ActivitySlot
    {
        return ActivitySlot::lockForUpdate()->findOrFail($slotId);
    }

    /**
     * Get an activity.
     */
    public function getActivity(int $activityId): Activity
    {
        return Activity::findOrFail($activityId);
    }

    /**
     * Create a new API reservation.
     */
    public function createReservation(array $data): ApiReservation
    {
        return ApiReservation::create($data);
    }

    /**
     * Update an API reservation.
     */
    public function updateReservation(ApiReservation $reservation, array $data): bool
    {
        return $reservation->update($data);
    }

    /**
     * Check if member has an active reservation for a given slot.
     */
    public function hasActiveReservationForSlot(Member $member, int $slotId): bool
    {
        return ApiReservation::where('member_id', $member->id)
            ->where('activity_slot_id', $slotId)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}
