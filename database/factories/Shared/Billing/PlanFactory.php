<?php

namespace Database\Factories\Shared\Billing;

use App\Models\Plan;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'price' => fake()->randomFloat(3, 30, 250),
            'duration_days' => fake()->randomElement([30, 60, 90, 365]),
            'has_all_courses' => false,
            'is_archived' => false,
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'is_archived' => true,
        ]);
    }

    public function withAllCourses(): static
    {
        return $this->state(fn (): array => [
            'has_all_courses' => true,
        ]);
    }
}
