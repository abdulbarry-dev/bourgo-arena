<?php

namespace Database\Factories\Shared\Notifications;

use App\Models\Member;
use App\Models\MemberNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberNotification>
 */
class MemberNotificationFactory extends Factory
{
    protected $model = MemberNotification::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'type' => fake()->word(),
            'title' => fake()->sentence(),
            'message' => fake()->paragraph(),
            'channel' => 'app',
            'status' => 'sent',
            'is_read' => false,
            'metadata' => [],
            'delivered_at' => now(),
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (): array => [
            'is_read' => false,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'is_read' => true,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }
}
