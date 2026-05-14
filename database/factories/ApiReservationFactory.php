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
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'activity_id' => Activity::factory(),
            'activity_slot_id' => ActivitySlot::factory(),
            'date' => now()->addDay(),
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
            'price' => 50.00,
            'status' => 'confirmed',
        ];

    }
}
