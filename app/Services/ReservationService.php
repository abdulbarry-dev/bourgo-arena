<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationService
{
    public function __construct(
        protected TierResolutionService $tierResolutionService
    ) {}

    /**
     * Create a new activity reservation.
     *
     * @throws ValidationException
     */
    public function makeActivityReservation(Member $member, array $data): ApiReservation
    {
        return DB::transaction(function () use ($member, $data) {
            $slot = ActivitySlot::lockForUpdate()->findOrFail($data['activity_slot_id']);
            $activity = Activity::query()->findOrFail($data['activity_id']);

            if ($slot->isFullyBooked()) {
                throw ValidationException::withMessages([
                    'activity_slot_id' => ['This activity slot is already fully booked.'],
                ]);
            }

            $price = $this->calculateReservationPrice($member, $activity);

            $reservation = ApiReservation::create([
                'member_id' => $member->id,
                'activity_id' => $data['activity_id'],
                'activity_slot_id' => $data['activity_slot_id'],
                'date' => $slot->date,
                'starts_at' => $slot->starts_at,
                'ends_at' => $slot->ends_at,
                'price' => $price,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ]);

            $reservation->update([
                'qr_code' => hash('sha256', $reservation->id.$member->id.now()),
            ]);

            $slot->increment('booked_count');

            return $reservation;
        });
    }

    protected function calculateReservationPrice(Member $member, Activity $activity): float
    {
        $tier = $this->tierResolutionService->resolveTier($member);
        $discount = (float) config('loyalty.pricing_discounts.'.$tier['label'], 0.0);

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

            $reservation->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $reservation->slot()->decrement('booked_count');
        });
    }
}
