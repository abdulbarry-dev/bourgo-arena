<?php

namespace Database\Factories;

use App\Models\MemberNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberNotification>
 */
class MemberNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_id' => \App\Models\Member::factory(),
            'type' => $this->faker->word(),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'channel' => 'app',
            'status' => 'sent',
            'is_read' => false,
            'delivered_at' => now(),
        ];
    }
}
