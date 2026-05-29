<?php

namespace Database\Factories\Dashboard\Events;

use App\Models\Event;
use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventMatch>
 */
class EventMatchFactory extends Factory
{
    protected $model = EventMatch::class;

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

    public function scheduled(): static
    {
        return $this->state(fn (): array => [
            'status' => 'scheduled',
        ]);
    }

    public function walkover(): static
    {
        return $this->state(fn (): array => [
            'status' => 'walkover',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
        ]);
    }

    public function inRound(int $round, int $matchNumber): static
    {
        return $this->state(fn (): array => [
            'round' => $round,
            'match_number' => $matchNumber,
        ]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (): array => [
            'event_id' => $event->id,
        ]);
    }
}
