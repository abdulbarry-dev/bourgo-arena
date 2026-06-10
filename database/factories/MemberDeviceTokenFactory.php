<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberDeviceToken;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberDeviceToken>
 */
class MemberDeviceTokenFactory extends Factory
{
    protected $model = MemberDeviceToken::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'token' => fake()->sha256(),
            'provider' => 'fcm',
            'device_type' => fake()->randomElement(['android', 'ios']),
            'is_active' => true,
            'last_used_at' => now(),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
