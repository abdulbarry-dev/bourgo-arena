<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberNotification;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive member notification data:
 * - Subscription-related notifications
 * - Booking confirmations and cancellations
 * - Course session updates
 * - Check-in alerts
 * - Promotional messages
 * - Read and unread notifications
 */
class ComprehensiveNotificationsSeeder extends Seeder
{
    private array $notificationTemplates = [
        // Subscription notifications
        [
            'type' => 'subscription.activated',
            'title' => 'Subscription Activated',
            'body' => 'Your gym membership is now active!',
        ],
        [
            'type' => 'subscription.expiring_soon',
            'title' => 'Subscription Expiring Soon',
            'body' => 'Your subscription expires in 3 days. Renew now to continue your fitness journey.',
        ],
        [
            'type' => 'subscription.expired',
            'title' => 'Subscription Expired',
            'body' => 'Your subscription has expired. Please renew to regain access.',
        ],
        [
            'type' => 'subscription.suspended',
            'title' => 'Subscription Suspended',
            'body' => 'Your subscription has been suspended. Contact support for more information.',
        ],
        // Booking notifications
        [
            'type' => 'booking.confirmed',
            'title' => 'Booking Confirmed',
            'body' => 'Your course session booking has been confirmed.',
        ],
        [
            'type' => 'booking.cancelled',
            'title' => 'Booking Cancelled',
            'body' => 'Your course session booking has been cancelled.',
        ],
        // Course notifications
        [
            'type' => 'course.session_cancelled',
            'title' => 'Course Session Cancelled',
            'body' => 'The course session you booked has been cancelled by the instructor.',
        ],
        [
            'type' => 'course.reminder',
            'title' => 'Upcoming Course Session',
            'body' => 'Your course session starts in 1 hour.',
        ],
        // Check-in notifications
        [
            'type' => 'checkin.denied',
            'title' => 'Access Denied',
            'body' => 'Your access was denied due to an expired subscription.',
        ],
        [
            'type' => 'checkin.card_issue',
            'title' => 'Card Issue Detected',
            'body' => 'We detected an issue with your NFC card. Please contact support.',
        ],
        // Promotional
        [
            'type' => 'promo.special_offer',
            'title' => 'Special Offer',
            'body' => 'Get 20% off on your next renewal!',
        ],
        [
            'type' => 'loyalty.points_earned',
            'title' => 'Loyalty Points Earned',
            'body' => 'You earned 100 loyalty points! Current balance: 500.',
        ],
    ];

    public function run(): void
    {
        // Get all active members
        $activeMembers = Member::query()
            ->where('status', 'active')
            ->where('state', 'active')
            ->get();

        foreach ($activeMembers as $member) {
            // Each member gets 5-15 notifications
            $notificationCount = random_int(5, 15);

            $selectedTemplates = fake()->randomElements(
                $this->notificationTemplates,
                min($notificationCount, count($this->notificationTemplates))
            );

            // Duplicate if needed to reach target count
            while (count($selectedTemplates) < $notificationCount) {
                $selectedTemplates[] = fake()->randomElement($this->notificationTemplates);
            }

            foreach (array_slice($selectedTemplates, 0, $notificationCount) as $template) {
                $isRead = fake()->boolean(60); // 60% read, 40% unread

                MemberNotification::create([
                    'member_id' => $member->id,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'body' => $template['body'],
                    'data' => json_encode([
                        'action_url' => fake()->optional(0.5)->url(),
                        'resource_id' => fake()->randomNumber(5),
                    ]),
                    'is_read' => $isRead,
                    'read_at' => $isRead ? now()->subDays(random_int(0, 10)) : null,
                    'created_at' => now()->subDays(random_int(0, 30)),
                ]);
            }
        }
    }
}
