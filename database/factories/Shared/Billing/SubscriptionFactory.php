<?php

namespace Database\Factories\Shared\Billing;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'plan_id' => Plan::factory(),
            'status' => 'active',
            'starts_at' => now()->subDays(3)->toDateString(),
            'ends_at' => now()->addDays(27)->toDateString(),
            'suspended_at' => null,
            'days_remaining' => null,
            'resumed_at' => null,
            'payment_method' => fake()->randomElement(['cash', 'konnect']),
            'payment_reference' => fake()->optional()->bothify('TXN-####-??'),
            'amount_paid' => fake()->randomFloat(3, 20, 500),
            'receipt_path' => null,
            'enrolled_by' => User::factory()->manager(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'days_remaining' => 10,
        ]);
    }

    public function suspendedWithRemaining(int $days): static
    {
        return $this->state(fn (): array => [
            'status' => 'suspended',
            'suspended_at' => now(),
            'days_remaining' => max(0, $days),
        ]);
    }

    public function expiringSoon(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'ends_at' => now()->addDays(7)->toDateString(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'status' => 'expired',
            'ends_at' => now()->subDay()->toDateString(),
        ]);
    }
}
