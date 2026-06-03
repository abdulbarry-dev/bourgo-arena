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
            'category' => fake()->word(),
            'base_price' => fake()->randomFloat(2, 10, 100),
            'currency' => 'TND',
            'image_url' => null,

            'description' => fake()->sentence(),
            'features' => [],
            'rating' => fake()->randomFloat(1, 3, 5),
            'review_count' => fake()->numberBetween(0, 250),
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
            'rating' => 5.0,
            'review_count' => fake()->numberBetween(50, 250),
        ]);
    }
}
