<?php

namespace Database\Factories;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CheckInEvent>
 */
class CheckInEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $result = fake()->randomElement(['authorized', 'denied']);
        $denialReason = $result === 'denied'
            ? fake()->randomElement(['expired_subscription', 'suspended_card', 'invalid_card', 'anti_passback'])
            : null;
        $checkedInAt = fake()->dateTimeBetween('-7 days', 'now');

        return [
            'member_id' => Member::factory(),
            'card_uid' => strtoupper(fake()->bothify('????####????')),
            'terminal_id' => HikvisionTerminal::factory(),
            'result' => $result,
            'denial_reason' => $denialReason,
            'is_suspicious' => $denialReason === 'anti_passback',
            'checked_in_at' => $checkedInAt,
            'created_at' => $checkedInAt,
        ];
    }

    public function authorized(): static
    {
        return $this->state([
            'result' => 'authorized',
            'denial_reason' => null,
            'is_suspicious' => false,
        ]);
    }

    public function denied(string $reason = 'invalid_card'): static
    {
        $allowedReasons = ['expired_subscription', 'suspended_card', 'invalid_card', 'anti_passback'];

        if (! in_array($reason, $allowedReasons, true)) {
            $reason = 'invalid_card';
        }

        return $this->state([
            'result' => 'denied',
            'denial_reason' => $reason,
            'is_suspicious' => $reason === 'anti_passback',
        ]);
    }

    public function suspicious(): static
    {
        return $this->denied('anti_passback')->state([
            'is_suspicious' => true,
        ]);
    }
}
