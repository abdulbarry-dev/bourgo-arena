<?php

namespace App\Services;

use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    /**
     * Create a new activity reservation.
     *
     * @throws ValidationException
     */
    public function makeActivityReservation(Member $member, array $data): ApiReservation
    {
        return DB::transaction(function () use ($member, $data) {
            $slot = ActivitySlot::lockForUpdate()->findOrFail($data['activity_slot_id']);

            if ($slot->isFullyBooked()) {
                throw ValidationException::withMessages([
                    'activity_slot_id' => ['This activity slot is already fully booked.'],
                ]);
            }

            $reservation = ApiReservation::create([
                'member_id' => $member->id,
                'activity_id' => $data['activity_id'],
                'activity_slot_id' => $data['activity_slot_id'],
                'date' => $data['date'],
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'price' => $data['price'],
                'status' => 'confirmed',
                'payment_status' => 'paid', // Assuming paid for now as per DTO
            ]);

            $reservation->update([
                'qr_code' => hash('sha256', $reservation->id.$member->id.now()),
            ]);

            $slot->increment('booked_count');

            return $reservation;
        });
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

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $reservation->slot()->decrement('booked_count');
        });
    }
}
