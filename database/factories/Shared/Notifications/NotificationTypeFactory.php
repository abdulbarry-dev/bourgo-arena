<?php

namespace Database\Factories\Shared\Notifications;

use App\Models\NotificationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationType>
 */
class NotificationTypeFactory extends Factory
{
    protected $model = NotificationType::class;

    public function definition(): array
    {
        $types = [
            'subscription_reminder' => ['name' => 'Subscription Reminder', 'category' => 'billing', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => false],
            'subscription_expired' => ['name' => 'Subscription Expired', 'category' => 'billing', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => false],
            'event_reminder' => ['name' => 'Event Reminder', 'category' => 'events', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => true],
            'event_cancellation' => ['name' => 'Event Cancellation', 'category' => 'events', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => true],
            'course_update' => ['name' => 'Course Update', 'category' => 'events', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => false],
            'promotional_offer' => ['name' => 'Promotional Offer', 'category' => 'promotions', 'push_enabled' => true, 'email_enabled' => true, 'sms_enabled' => false],
            'loyalty_update' => ['name' => 'Loyalty Update', 'category' => 'system', 'push_enabled' => true, 'email_enabled' => false, 'sms_enabled' => false],
            'account_notice' => ['name' => 'Account Notice', 'category' => 'system', 'push_enabled' => false, 'email_enabled' => true, 'sms_enabled' => false],
        ];

        $slug = fake()->unique()->randomElement(array_keys($types));
        $type = $types[$slug];

        return [
            'slug' => $slug,
            'name' => $type['name'],
            'description' => fake()->sentence(),
            'category' => $type['category'],
            'push_enabled' => $type['push_enabled'],
            'email_enabled' => $type['email_enabled'],
            'sms_enabled' => $type['sms_enabled'],
            'icon' => fake()->randomElement(['bell', 'bell-alert', 'megaphone', 'envelope', 'chat-bubble-left-right', 'speaker-x-mark']),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }
}
