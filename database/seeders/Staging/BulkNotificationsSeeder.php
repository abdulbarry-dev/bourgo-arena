<?php

namespace Database\Seeders\Staging;

use App\Models\Member;
use App\Models\MemberNotification;
use App\Models\NotificationType;
use Illuminate\Database\Seeder;

class BulkNotificationsSeeder extends Seeder
{
    private array $templates = [
        'subscription_reminder' => [
            ['title' => 'Your subscription expires in 7 days', 'message' => 'Your Performance Monthly plan expires on {date}. Renew now to keep your access.'],
            ['title' => 'Subscription expiring soon', 'message' => 'Your Starter Monthly subscription ends in 3 days. Head to the app to renew.'],
            ['title' => 'Last chance to renew', 'message' => 'Your subscription expires tomorrow. Don\'t lose access to your favourite activities.'],
        ],
        'subscription_expired' => [
            ['title' => 'Your subscription has expired', 'message' => 'Your plan ended on {date}. Renew to continue accessing all facilities.'],
            ['title' => 'Subscription expired', 'message' => 'We miss you! Your membership lapsed. Reactivate today and get back in the game.'],
        ],
        'event_reminder' => [
            ['title' => 'Event reminder: Summer Padel Cup', 'message' => 'Your event starts tomorrow at 9:00. Don\'t forget to check in at the courts.'],
            ['title' => 'Tournament starts in 2 days', 'message' => 'The Autumn Tennis Ladder begins on {date}. Confirm your availability in the app.'],
            ['title' => 'Check-in opens today', 'message' => 'Check-in for your upcoming event is now available in the app.'],
        ],
        'event_cancellation' => [
            ['title' => 'Event cancelled: Winter Padel Invitational', 'message' => 'We\'re sorry — this event has been cancelled due to facility maintenance.'],
            ['title' => 'Tournament cancelled', 'message' => 'The Summer Swim Meet has been cancelled due to insufficient registrations.'],
        ],
        'course_update' => [
            ['title' => 'Course session rescheduled', 'message' => 'Your Functional Strength session on Wednesday has been moved to Thursday 18:00.'],
            ['title' => 'New course available: Muay Thai', 'message' => 'A new Muay Thai course is now available for enrollment. Limited spots!'],
            ['title' => 'Course session cancelled', 'message' => 'The Padel Basics session on Saturday 10:00 has been cancelled by the coach.'],
        ],
        'loyalty_update' => [
            ['title' => 'You earned 250 loyalty points!', 'message' => 'Your subscription renewal credited 250 points to your account. Balance: {balance} pts.'],
            ['title' => 'Loyalty points redeemed', 'message' => 'You used {points} points to pay for your subscription. Remaining balance: {balance} pts.'],
            ['title' => 'Loyalty milestone reached!', 'message' => 'You\'ve accumulated 5,000 loyalty points. Keep going to unlock exclusive rewards!'],
            ['title' => 'Points expiry reminder', 'message' => 'Some of your loyalty points are expiring in 30 days. Use them before they\'re gone!'],
        ],
        'promotional_offer' => [
            ['title' => 'Exclusive offer: 20% off Quarterly Plus', 'message' => 'For the next 48 hours, upgrade to Quarterly Plus and save 20%. Offer ends {date}.'],
            ['title' => 'Bring a friend, earn 500 points', 'message' => 'Refer a friend and earn 500 loyalty points when they subscribe. Share your code today.'],
            ['title' => 'Summer Family Package now available', 'message' => 'Add up to 3 family members to your account at a special group rate this summer.'],
        ],
        'account_notice' => [
            ['title' => 'Verify your email address', 'message' => 'Please verify your email to unlock full access to your Bourgo Arena account.'],
            ['title' => 'Profile updated successfully', 'message' => 'Your profile information has been updated. If this wasn\'t you, contact support immediately.'],
            ['title' => 'Account security alert', 'message' => 'A new device signed in to your account from Tunis. If this wasn\'t you, secure your account now.'],
        ],
    ];

    public function run(): void
    {
        if (MemberNotification::count() > 500) {
            $this->command?->info('  Notifications already seeded. Skipping.');

            return;
        }

        $notificationTypes = NotificationType::all()->keyBy('slug');

        if ($notificationTypes->isEmpty()) {
            $this->command?->warn('  No notification types found. Run NotificationTypeSeeder first.');

            return;
        }

        $members = Member::where('status', 'active')
            ->where('state', 'active')
            ->inRandomOrder()
            ->take(300)
            ->get();

        $channels = ['push', 'email', 'sms'];
        $statuses = ['delivered', 'delivered', 'delivered', 'pending', 'failed'];
        $typeKeys = array_keys($this->templates);

        $batch = [];

        foreach ($members as $member) {
            $notifCount = rand(3, 12);

            for ($n = 0; $n < $notifCount; $n++) {
                $typeSlug = $typeKeys[array_rand($typeKeys)];
                $templates = $this->templates[$typeSlug];
                $template = $templates[array_rand($templates)];
                $daysAgo = rand(0, 60);
                $isRead = rand(0, 3) > 0;
                $channel = $channels[array_rand($channels)];
                $status = $statuses[array_rand($statuses)];

                $title = str_replace('{date}', now()->addDays(rand(1, 10))->format('M d, Y'), $template['title']);
                $message = str_replace(
                    ['{date}', '{balance}', '{points}'],
                    [now()->addDays(rand(1, 10))->format('M d, Y'), number_format(rand(100, 15000)), number_format(rand(100, 5000))],
                    $template['message']
                );

                $deliveredAt = $status === 'delivered' ? now()->subDays($daysAgo)->subMinutes(rand(1, 60)) : null;

                $batch[] = [
                    'member_id' => $member->id,
                    'type' => $typeSlug,
                    'title' => $title,
                    'message' => $message,
                    'channel' => $channel,
                    'status' => $status,
                    'is_read' => $status === 'delivered' ? $isRead : false,
                    'delivered_at' => $deliveredAt,
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ];

                if (count($batch) >= 500) {
                    MemberNotification::insert($batch);
                    $batch = [];
                }
            }
        }

        if (! empty($batch)) {
            MemberNotification::insert($batch);
        }

        $this->command?->info('  Notifications created: '.MemberNotification::count());
    }
}
