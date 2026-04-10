<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'price' => fake()->randomFloat(3, 30, 250),
            'duration_days' => fake()->randomElement([30, 60, 90, 365]),
            'included_services' => ['gym', 'classes'],
            'has_all_courses' => false,
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state([
            'is_archived' => true,
        ]);
    }

    public function withAllCourses(): static
    {
        return $this->state([
            'has_all_courses' => true,
        ]);
    }
}
