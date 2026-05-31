<?php

namespace Database\Factories\Dashboard\Catalog;

use App\Models\Course;
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
            'name' => fake()->words(2, true),
            'instructor' => fake()->name(),
            'description' => fake()->sentence(),
            'category' => fake()->word(),
            'icon' => 'sports_martial_arts',
        ];
    }

    public function withInstructor(string $instructor): static
    {
        return $this->state(fn (): array => [
            'instructor' => $instructor,
        ]);
    }
}
