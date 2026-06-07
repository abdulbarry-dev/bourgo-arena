<?php

namespace Database\Seeders\Api;

use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\ApiReservation;
use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\MemberNotification;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Database\Seeders\Dashboard\Activities\ActivitySeeder;
use Database\Seeders\Dashboard\Activities\ActivitySlotSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MobileUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $member = Member::query()->updateOrCreate(
            ['email' => 'abdelbariguenichi@gmail.com'],
            [
                'name' => 'Abdelbari Guenichi',
                'phone' => '22555666',
                'password' => Hash::make('Password@123'),
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
                'onboarding_completed_at' => now(),
                'status' => 'active',
                'state' => 'active',
                'rgpd_consented_at' => now(),
                'gender' => 'male',
                'date_of_birth' => '1990-01-01',
                'loyalty_points' => 1250,
                'avatar' => 'https://i.pravatar.cc/150?u=abdelbariguenichi@gmail.com',
            ]
        );

        // Also create a User record for the same member to ensure compatibility with features that use the User model (like events)
        User::query()->updateOrCreate(
            ['email' => 'abdelbariguenichi@gmail.com'],
            [
                'name' => 'Abdelbari Guenichi',
                'phone' => '22555666',
                'password' => Hash::make('Password@123'),
                'role' => UserRole::Member,
                'email_verified_at' => now(),
            ]
        );

        // Seed Children
        $childrenData = [
            [
                'name' => 'Sami Guenichi',
                'date_of_birth' => now()->subYears(8)->format('Y-m-d'),
                'gender' => 'male',
                'avatar' => 'https://i.pravatar.cc/150?u=sami',
            ],
            [
                'name' => 'Lina Guenichi',
                'date_of_birth' => now()->subYears(5)->format('Y-m-d'),
                'gender' => 'female',
                'avatar' => 'https://i.pravatar.cc/150?u=lina',
            ],
            [
                'name' => 'Yassine Guenichi',
                'date_of_birth' => now()->subYears(12)->format('Y-m-d'),
                'gender' => 'male',
                'avatar' => 'https://i.pravatar.cc/150?u=yassine',
            ],
        ];

        foreach ($childrenData as $childData) {
            Member::query()->updateOrCreate(
                ['name' => $childData['name'], 'parent_id' => $member->id],
                array_merge($childData, [
                    'status' => 'active',
                    'state' => 'active',
                    'onboarding_completed_at' => now(),
                    'rgpd_consented_at' => now(),
                ])
            );
        }

        // Add Loyalty Points transactions
        if ($member->loyaltyPoints()->count() < 5) {
            $transactions = [
                ['points' => 500, 'type' => 'manual', 'source' => 'welcome_bonus', 'date' => now()->subMonths(2)],
                ['points' => 250, 'type' => 'earned', 'source' => 'reservation_completed', 'date' => now()->subMonth()],
                ['points' => 300, 'type' => 'earned', 'source' => 'subscription_renewal', 'date' => now()->subWeeks(2)],
                ['points' => 100, 'type' => 'earned', 'source' => 'referral', 'date' => now()->subDays(5)],
                ['points' => 100, 'type' => 'earned', 'source' => 'daily_checkin', 'date' => now()],
            ];

            foreach ($transactions as $t) {
                LoyaltyPoint::create([
                    'member_id' => $member->id,
                    'points' => $t['points'],
                    'transaction_type' => $t['type'],
                    'source_type' => $t['source'],
                    'idempotency_key' => Str::uuid()->toString(),
                    'created_at' => $t['date'],
                ]);
            }
        }

        // Add Subscription if none exists
        if ($member->subscriptions()->count() === 0) {
            $plan = Plan::where('name', 'Performance Monthly')->first() ?? Plan::first();
            $admin = User::where('role', UserRole::Admin)->first();

            if ($plan && $admin) {
                Subscription::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now()->startOfMonth(),
                    'ends_at' => now()->addMonths(6)->endOfMonth(),
                    'amount_paid' => $plan->price,
                    'payment_method' => 'cash',
                    'payment_reference' => 'SEED-MOBILE-001',
                    'enrolled_by' => $admin->id,
                ]);
            }
        }

        // Add Reservations History and Upcoming
        if ($member->reservations()->count() < 5) {
            // Ensure activities exist
            if (Activity::count() === 0) {
                $this->call(ActivitySeeder::class);
                $this->call(ActivitySlotSeeder::class);
            }

            $activities = Activity::all();
            $children = $member->children()->get();

            // Create some extra slots to have enough for everyone
            foreach ($activities as $activity) {
                for ($h = 7; $h <= 9; $h++) {
                    ActivitySlot::updateOrCreate(
                        ['activity_id' => $activity->id, 'starts_at' => sprintf('%02d:00:00', $h)],
                        ['ends_at' => sprintf('%02d:00:00', $h + 1), 'capacity' => 10, 'booked_count' => 0, 'is_available' => true]
                    );
                }
            }
            $allSlots = ActivitySlot::all()->shuffle();
            $slotIndex = 0;

            // Past Reservations (History) for Main Member
            for ($i = 1; $i <= 3; $i++) {
                if (! isset($allSlots[$slotIndex])) {
                    break;
                }
                $slot = $allSlots[$slotIndex++];
                $date = now()->subDays($i * 5);

                ApiReservation::create([
                    'member_id' => $member->id,
                    'activity_id' => $slot->activity_id,
                    'activity_slot_id' => $slot->id,
                    'date' => $date->toDateString(),
                    'starts_at' => $slot->starts_at,
                    'ends_at' => $slot->ends_at,
                    'price' => $slot->activity->base_price,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'qr_code' => 'seed-history-'.$i,
                ]);
            }

            // Upcoming Reservations for Main Member
            for ($i = 1; $i <= 2; $i++) {
                if (! isset($allSlots[$slotIndex])) {
                    break;
                }
                $slot = $allSlots[$slotIndex++];
                $date = now()->addDays($i * 2);

                ApiReservation::create([
                    'member_id' => $member->id,
                    'activity_id' => $slot->activity_id,
                    'activity_slot_id' => $slot->id,
                    'date' => $date->toDateString(),
                    'starts_at' => $slot->starts_at,
                    'ends_at' => $slot->ends_at,
                    'price' => $slot->activity->base_price,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'qr_code' => 'seed-upcoming-'.$i,
                ]);
            }

            // Reservations for Children
            foreach ($children as $child) {
                if (! isset($allSlots[$slotIndex])) {
                    break;
                }
                $slot = $allSlots[$slotIndex++];
                ApiReservation::create([
                    'member_id' => $child->id,
                    'activity_id' => $slot->activity_id,
                    'activity_slot_id' => $slot->id,
                    'date' => now()->addDays(3)->toDateString(),
                    'starts_at' => $slot->starts_at,
                    'ends_at' => $slot->ends_at,
                    'price' => $slot->activity->base_price,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                    'qr_code' => 'seed-child-'.$child->id,
                ]);
            }

            // Cancelled Reservation for Main Member
            if (isset($allSlots[$slotIndex])) {
                $slot = $allSlots[$slotIndex++];
                ApiReservation::create([
                    'member_id' => $member->id,
                    'activity_id' => $slot->activity_id,
                    'activity_slot_id' => $slot->id,
                    'date' => now()->subDays(10)->toDateString(),
                    'starts_at' => $slot->starts_at,
                    'ends_at' => $slot->ends_at,
                    'price' => $slot->activity->base_price,
                    'status' => 'cancelled',
                    'payment_status' => 'refunded',
                    'cancelled_at' => now()->subDays(11),
                    'qr_code' => 'seed-cancelled',
                ]);
            }
        }

        // Seed Notifications
        if ($member->notifications()->count() <= 5) {
            $notificationTemplates = [
                [
                    'type' => 'reservation_confirmed',
                    'title' => 'Reservation Confirmed',
                    'message' => 'Your Padel Intro Clinic reservation for tomorrow is confirmed. See you there!',
                ],
                [
                    'type' => 'loyalty_points',
                    'title' => 'Points Earned!',
                    'message' => 'Congratulations! You just earned 100 loyalty points for your last visit.',
                ],
                [
                    'type' => 'subscription_reminder',
                    'title' => 'Subscription Renewal',
                    'message' => 'Your Performance Monthly subscription will renew in 3 days.',
                ],
                [
                    'type' => 'promotion',
                    'title' => 'Special Weekend Offer',
                    'message' => 'Book any wellness session this weekend and get 20% off!',
                ],
                [
                    'type' => 'event_update',
                    'title' => 'Tournament Update',
                    'message' => 'The bracket for the Summer Padel Open has been updated. Check your position!',
                ],
                [
                    'type' => 'payment_success',
                    'title' => 'Payment Successful',
                    'message' => 'Your payment for the Yoga Recovery Flow has been processed successfully.',
                ],
                [
                    'type' => 'member_update',
                    'title' => 'Profile Updated',
                    'message' => 'Your account profile has been successfully updated.',
                ],
                [
                    'type' => 'family_activity',
                    'title' => 'Child Activity',
                    'message' => 'Sami has a scheduled activity today at 4:00 PM.',
                ],
                [
                    'type' => 'announcement',
                    'title' => 'New Facility Opening',
                    'message' => 'Our new sauna and steam room are now open for all wellness members!',
                ],
                [
                    'type' => 'support',
                    'title' => 'Support Ticket Closed',
                    'message' => 'Your inquiry regarding loyalty point redemption has been resolved.',
                ],
            ];

            // Create a bunch of notifications over the last 30 days
            for ($i = 0; $i < 25; $i++) {
                $template = $notificationTemplates[array_rand($notificationTemplates)];
                MemberNotification::create([
                    'member_id' => $member->id,
                    'type' => $template['type'],
                    'title' => $template['title'],
                    'message' => $template['message'],
                    'channel' => 'push',
                    'status' => 'delivered',
                    'is_read' => (bool) rand(0, 1),
                    'delivered_at' => now()->subMinutes(rand(10, 43200)), // up to 30 days
                    'created_at' => now()->subMinutes(rand(10, 43200)),
                ]);
            }

            // Ensure some recent unread ones
            MemberNotification::create([
                'member_id' => $member->id,
                'type' => 'reservation_reminder',
                'title' => 'Upcoming Session',
                'message' => 'Don\'t forget your Yoga session today at 6:00 PM!',
                'channel' => 'push',
                'status' => 'delivered',
                'is_read' => false,
                'delivered_at' => now()->subMinutes(15),
                'created_at' => now()->subMinutes(15),
            ]);

            MemberNotification::create([
                'member_id' => $member->id,
                'type' => 'loyalty_points',
                'title' => 'Bonus Points!',
                'message' => 'You\'ve been awarded 50 bonus points for your 10th reservation this month!',
                'channel' => 'push',
                'status' => 'delivered',
                'is_read' => false,
                'delivered_at' => now()->subHours(1),
                'created_at' => now()->subHours(1),
            ]);
        }
    }
}
