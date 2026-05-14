<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivitySlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySlot>
 */
class ActivitySlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'date' => now()->addDay(),
            'starts_at' => '10:00:00',
            'ends_at' => '11:00:00',
            'capacity' => 10,
            'booked_count' => 0,
            'is_available' => true,
        ];

    }
}
