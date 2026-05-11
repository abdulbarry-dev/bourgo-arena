<?php

namespace App\Policies;

use App\Models\ApiReservation;
use App\Models\Member;

class ReservationPolicy
{
    /**
     * Determine whether the member can view any reservations.
     */
    public function viewAny(Member $member): bool
    {
        return true;
    }

    /**
     * Determine whether the member can view the reservation.
     */
    public function view(Member $member, ApiReservation $apiReservation): bool
    {
        return $member->id === $apiReservation->member_id;
    }

    /**
     * Determine whether the member can create reservations.
     */
    public function create(Member $member): bool
    {
        return true;
    }

    /**
     * Determine whether the member can delete (cancel) the reservation.
     */
    public function delete(Member $member, ApiReservation $apiReservation): bool
    {
        return $member->id === $apiReservation->member_id;
    }
}
