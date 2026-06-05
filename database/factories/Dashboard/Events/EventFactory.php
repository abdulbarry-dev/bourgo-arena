<?php

namespace Database\Factories\Dashboard\Events;

use App\Models\Event;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'name' => fake()->words(3, true).' Championship',
            'description' => fake()->sentence(),
            'images' => [
                'https://picsum.photos/seed/'.fake()->uuid.'/800/600',
                'https://picsum.photos/seed/'.fake()->uuid.'/800/600',
                'https://picsum.photos/seed/'.fake()->uuid.'/800/600',
            ],
            'format' => fake()->randomElement(['1v1', '2v2', '5v5']),
            'max_participants' => fake()->randomElement([8, 16, 32]),
            'registration_deadline' => now()->addDays(5),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(10),
            'requires_check_in' => fake()->boolean(),
        ];
    }

    public function requiresCheckIn(): static
    {
        return $this->state(fn (): array => [
            'requires_check_in' => true,
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'registration_deadline' => now()->addDays(5),
            'start_date' => now()->addDays(7),
            'end_date' => now()->addDays(10),
        ]);
    }

    public function open(): static
    {
        return $this->state(fn (): array => [
            'registration_deadline' => now()->subDays(1),
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
        ]);
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'registration_deadline' => now()->subDays(5),
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(1),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'registration_deadline' => now()->subDays(10),
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(1),
        ]);
    }
}
