<?php

namespace Database\Factories\Shared\Members;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

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
            'status' => 'active',
            'state' => 'pending_verification',
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'onboarding_completed_at' => null,
            'rgpd_consented_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => [
            'status' => 'active',
            'state' => 'active',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'onboarding_completed_at' => now(),
        ]);
    }

    public function pendingOnboarding(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending_onboarding',
            'state' => 'pending_onboarding',
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
            'onboarding_completed_at' => null,
        ]);
    }

    public function pendingAdditionalVerification(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending_additional_verification',
            'state' => 'pending_additional_verification',
            'email_verified_at' => now(),
            'phone_verified_at' => null,
            'onboarding_completed_at' => null,
        ]);
    }
}
