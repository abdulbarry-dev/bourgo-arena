<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\MemberDeviceToken;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive device token data for:
 * - Mobile push notifications (FCM, APNs)
 * - Multiple devices per member
 * - Active and inactive tokens
 * - Last used tracking for token validation
 */
class ComprehensiveDeviceTokensSeeder extends Seeder
{
    public function run(): void
    {
        // Get active members
        $activeMembers = Member::query()
            ->where('status', 'active')
            ->where('state', 'active')
            ->get();

        foreach ($activeMembers as $member) {
            // Each member has 1-3 active devices
            $deviceCount = random_int(1, 3);

            for ($i = 0; $i < $deviceCount; $i++) {
                $deviceType = fake()->randomElement(['android', 'ios', 'web']);
                $isActive = fake()->boolean(80); // 80% active

                MemberDeviceToken::create([
                    'member_id' => $member->id,
                    'token' => 'fcm_token_'.$member->id.'_'.$i.'_'.fake()->sha256(),
                    'provider' => 'fcm', // Firebase Cloud Messaging
                    'device_type' => $deviceType,
                    'is_active' => $isActive,
                    'last_used_at' => $isActive
                        ? now()->subDays(random_int(0, 7))
                        : now()->subDays(random_int(8, 30)),
                ]);
            }
        }

        // Create some old/inactive tokens that should be pruned
        $oldMembers = Member::query()
            ->whereHas('subscriptions', function ($q) {
                $q->where('status', 'expired');
            })
            ->get()
            ->take(5);

        foreach ($oldMembers as $member) {
            MemberDeviceToken::create([
                'member_id' => $member->id,
                'token' => 'fcm_token_old_'.$member->id.'_'.fake()->sha256(),
                'provider' => 'fcm',
                'device_type' => fake()->randomElement(['android', 'ios']),
                'is_active' => false,
                'last_used_at' => now()->subDays(random_int(30, 90)),
            ]);
        }
    }
}
