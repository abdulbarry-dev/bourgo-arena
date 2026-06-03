<?php

namespace Database\Factories\Dashboard\Catalog;

use App\Models\Course;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'category' => fake()->word(),
            'image_url' => null,
        ];
    }
}
