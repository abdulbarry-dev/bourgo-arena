<?php

namespace Database\Factories\Dashboard\Events;

use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventParticipant>
 */
class EventParticipantFactory extends Factory
{
    protected $model = EventParticipant::class;

    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'seed_number' => null,
            'has_checked_in' => false,
            'status' => 'approved',
            'withdrawn_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => 'approved',
        ]);
    }

    public function waitlisted(): static
    {
        return $this->state(fn (): array => [
            'status' => 'waitlisted',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
        ]);
    }

    public function withdrawn(): static
    {
        return $this->state(fn (): array => [
            'status' => 'withdrawn',
            'withdrawn_at' => now(),
        ]);
    }

    public function checkedIn(): static
    {
        return $this->state(fn (): array => [
            'has_checked_in' => true,
        ]);
    }

    public function seeded(int $seedNumber): static
    {
        return $this->state(fn (): array => [
            'seed_number' => $seedNumber,
        ]);
    }

    public function forEvent(Event $event): static
    {
        return $this->state(fn (): array => [
            'event_id' => $event->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
        ]);
    }
}
