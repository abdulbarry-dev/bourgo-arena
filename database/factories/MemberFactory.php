<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('2#######'),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-16 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female']),
            'emergency_contact' => fake()->phoneNumber(),
            'avatar' => null,
            'status' => 'pending',
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
            'rgpd_consented_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => null,
        ];
    }

    public function active(): static
    {
        return $this->state([
            'status' => 'active',
            'email_verified_at' => now(),
            'onboarding_completed_at' => now(),
        ]);
    }
}
