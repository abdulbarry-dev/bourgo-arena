<?php

namespace Database\Factories\Shared\Auth;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OtpCode>
 */
class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'identifier' => fake()->phoneNumber(),
            'code' => '123456',
            'expires_at' => now()->addMinutes(10),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (): array => [
            'expires_at' => now()->subMinute(),
        ]);
    }

    public function used(): static
    {
        return $this->state(fn (): array => [
            'used_at' => now(),
        ]);
    }
}
