<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CourseSession>
 */
class CourseSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'day_of_week' => $this->faker->numberBetween(0, 6),
            'starts_at' => $this->faker->time(),
            'starts_at_date' => now(),
            'ends_at_date' => now()->addMonth(),
            'duration_minutes' => 60,
            'capacity' => 10,
        ];
    }
}
