<?php

namespace Database\Factories\Shared\Activities;

use App\Models\Activity;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    protected $model = Activity::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'title' => fake()->sentence(3),
            'base_price' => fake()->randomFloat(2, 10, 100),
            'capacity' => fake()->numberBetween(1, 50),
            'image_url' => null,

            'description' => fake()->sentence(),
            'features' => [],
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }
}
