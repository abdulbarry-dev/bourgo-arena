<?php

namespace Database\Factories;

use App\Models\Activity;
use App\Models\ActivitySession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivitySession>
 */
class ActivitySessionFactory extends Factory
{
    protected $model = ActivitySession::class;

    public function definition(): array
    {
        return [
            'activity_id' => Activity::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'starts_at' => $this->faker->time(),
            'starts_at_date' => now(),
            'ends_at_date' => now()->addMonth(),
            'duration_minutes' => 60,
        ];
    }
}
