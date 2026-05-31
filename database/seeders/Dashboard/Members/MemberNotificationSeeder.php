<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\Member;
use App\Models\MemberNotification;
use Illuminate\Database\Seeder;

class MemberNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $notifications = [
            ['email' => 'amira.elmansouri@example.com', 'type' => 'subscription', 'title' => 'Subscription renewed', 'message' => 'Your membership has been renewed successfully.', 'is_read' => true],
            ['email' => 'othman.bennis@example.com', 'type' => 'event', 'title' => 'Event reminder', 'message' => 'Your event registration is confirmed for the weekend.', 'is_read' => false],
            ['email' => 'nadia.rachid@example.com', 'type' => 'loyalty', 'title' => 'Points added', 'message' => 'You earned extra loyalty points this week.', 'is_read' => false],
            ['email' => 'mehdi.amrani@example.com', 'type' => 'schedule', 'title' => 'Session updated', 'message' => 'Your session time has been updated.', 'is_read' => true],
            ['email' => 'sara.berrada@example.com', 'type' => 'payment', 'title' => 'Payment received', 'message' => 'Your payment has been received and recorded.', 'is_read' => true],
            ['email' => 'bilal.hajar@example.com', 'type' => 'onboarding', 'title' => 'Onboarding available', 'message' => 'You can now continue your onboarding flow.', 'is_read' => false],
        ];

        foreach ($notifications as $index => $notificationData) {
            $member = Member::query()->where('email', $notificationData['email'])->first();

            if ($member === null) {
                continue;
            }

            MemberNotification::query()->updateOrCreate(
                ['member_id' => $member->id, 'title' => $notificationData['title']],
                [
                    'type' => $notificationData['type'],
                    'message' => $notificationData['message'],
                    'channel' => 'app',
                    'status' => $notificationData['is_read'] ? 'read' : 'sent',
                    'is_read' => $notificationData['is_read'],
                    'metadata' => ['seed_index' => $index],
                    'delivered_at' => now()->subHours($index + 1),
                ],
            );
        }
    }
}
