<?php

namespace Database\Factories;

use App\Models\LoyaltyPoint;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoyaltyPoint>
 */
class LoyaltyPointFactory extends Factory
{
    protected $model = LoyaltyPoint::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'points' => $this->faker->numberBetween(10, 1000),
            'transaction_type' => $this->faker->randomElement(['fixed', 'variable', 'gift', 'refund']),
            'source_type' => $this->faker->randomElement(['subscription', 'reservation', 'Bourgo Arena']),
            'source_id' => $this->faker->randomNumber(),
            'idempotency_key' => $this->faker->unique()->uuid(),
            'created_at' => now(),
        ];
    }
}
