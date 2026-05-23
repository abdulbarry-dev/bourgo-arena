<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiReservation>
 */
class ApiReservationFactory extends Factory
{
    protected $model = ApiReservation::class;

    public function definition(): array
    {
        $activity = Activity::factory()->create();
        $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id]);

        return [
            'member_id' => Member::factory(),
            'activity_id' => $activity->id,
            'activity_slot_id' => $slot->id,
            'date' => $slot->date->toDateString(),
            'starts_at' => $slot->starts_at,
            'ends_at' => $slot->ends_at,
            'price' => $activity->base_price,
            'status' => 'confirmed',
            'payment_status' => 'pending',
            'qr_code' => null,
        ];
    }
}
