<?php

namespace Database\Factories;

use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true).' Championship',
            'description' => fake()->sentence(),
            'sport_type' => fake()->randomElement(['padel', 'football', 'tennis']),
            'format' => fake()->randomElement(['1v1', '2v2', '5v5']),
            'max_participants' => fake()->randomElement([8, 16, 32]),
            'registration_deadline' => now()->addDays(5),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(10),
            'requires_check_in' => fake()->boolean(),
            'status' => 'open',
        ];
    }
}
