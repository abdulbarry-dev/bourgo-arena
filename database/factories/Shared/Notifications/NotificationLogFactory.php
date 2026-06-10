<?php

namespace Database\Factories\Shared\Notifications;

use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationLog>
 */
class NotificationLogFactory extends Factory
{
    protected $model = NotificationLog::class;

    public function definition(): array
    {
        return [
            'notification_type_id' => NotificationType::factory(),
            'member_id' => Member::factory(),
            'channel' => fake()->randomElement(['push', 'email', 'sms']),
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'status' => 'sent',
            'sent_at' => now(),
            'metadata' => ['recipient_count' => 1],
        ];
    }

    public function queued(): static
    {
        return $this->state(fn (): array => [
            'status' => 'queued',
            'sent_at' => null,
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'failed',
            'metadata' => ['error' => 'FCM token expired'],
        ]);
    }
}
