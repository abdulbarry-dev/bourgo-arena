<?php

namespace App\Repositories;

use App\Models\Activity;
use App\Models\ActivitySession;
use App\Models\ApiReservation;
use App\Models\Member;

class ReservationRepository
{
    public function lockSessionForUpdate(int $sessionId): ActivitySession
    {
        return ActivitySession::lockForUpdate()->findOrFail($sessionId);
    }

    public function getActivity(int $activityId): Activity
    {
        return Activity::findOrFail($activityId);
    }

    public function createReservation(array $data): ApiReservation
    {
        return ApiReservation::create($data);
    }

    public function updateReservation(ApiReservation $reservation, array $data): bool
    {
        return $reservation->update($data);
    }

    public function hasActiveReservationForSession(Member $member, int $sessionId, string $date): bool
    {
        return ApiReservation::where('member_id', $member->id)
            ->where('activity_session_id', $sessionId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->exists();
    }
}
