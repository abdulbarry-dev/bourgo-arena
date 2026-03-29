<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\NfcCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NfcCard>
 */
class NfcCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'uid' => strtoupper(fake()->bothify('????####????')),
            'status' => 'active',
            'assigned_by' => User::factory()->manager(),
            'assigned_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state([
            'status' => 'suspended',
        ]);
    }
}
