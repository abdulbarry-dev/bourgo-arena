<?php

namespace Database\Seeders;

use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class NotificationTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'slug' => 'subscription_reminder',
                'name' => 'Subscription Reminder',
                'description' => 'Remind members when their subscription is about to expire.',
                'category' => 'billing',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'icon' => 'bell',
            ],
            [
                'slug' => 'subscription_expired',
                'name' => 'Subscription Expired',
                'description' => 'Notify members when their subscription has expired.',
                'category' => 'billing',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'icon' => 'bell-alert',
            ],
            [
                'slug' => 'event_reminder',
                'name' => 'Event Reminder',
                'description' => 'Send reminders for upcoming events and tournaments.',
                'category' => 'events',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => true,
                'icon' => 'calendar',
            ],
            [
                'slug' => 'event_cancellation',
                'name' => 'Event Cancellation',
                'description' => 'Notify members when an event or tournament is cancelled.',
                'category' => 'events',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => true,
                'icon' => 'x-circle',
            ],
            [
                'slug' => 'course_update',
                'name' => 'Course Update',
                'description' => 'Notify members about course schedule changes or cancellations.',
                'category' => 'events',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'icon' => 'book-open',
            ],
            [
                'slug' => 'promotional_offer',
                'name' => 'Promotional Offer',
                'description' => 'Send promotional offers and special deals to members.',
                'category' => 'promotions',
                'push_enabled' => true,
                'email_enabled' => true,
                'sms_enabled' => false,
                'icon' => 'gift',
            ],
            [
                'slug' => 'loyalty_update',
                'name' => 'Loyalty Update',
                'description' => 'Notify members about loyalty points changes and rewards.',
                'category' => 'system',
                'push_enabled' => true,
                'email_enabled' => false,
                'sms_enabled' => false,
                'icon' => 'star',
            ],
            [
                'slug' => 'account_notice',
                'name' => 'Account Notice',
                'description' => 'Send important account-related notices and security alerts.',
                'category' => 'system',
                'push_enabled' => false,
                'email_enabled' => true,
                'sms_enabled' => false,
                'icon' => 'shield-check',
            ],
        ];

        foreach ($types as $type) {
            NotificationType::updateOrCreate(
                ['slug' => $type['slug']],
                $type,
            );
        }
    }
}
