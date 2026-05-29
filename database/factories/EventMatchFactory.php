<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMatch>
 */
class EventMatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'round' => 1,
            'match_number' => 1,
            'scheduled_at' => fake()->optional()->dateTimeBetween('+1 day', '+2 days'),
            'participant1_id' => null,
            'participant2_id' => null,
            'winner_id' => null,
            'score' => null,
            'status' => 'scheduled',
            'next_match_id' => null,
        ];
    }
}
