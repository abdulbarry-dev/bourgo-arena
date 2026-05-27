<?php

namespace Database\Seeders;

use App\Models\LoyaltyPoint;
use App\Models\Member;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive loyalty program data:
 * - Point balances for members
 * - Point transactions (earned, redeemed, expired)
 * - Loyalty tier progression
 * - Redemption history
 */
class ComprehensiveLoyaltySeeder extends Seeder
{
    // Loyalty tier definitions: points threshold
    private array $tiers = [
        'bronze' => 0,
        'silver' => 500,
        'gold' => 1500,
        'platinum' => 3000,
    ];

    public function run(): void
    {
        // Get active members
        $activeMembers = Member::query()
            ->where('status', 'active')
            ->where('state', 'active')
            ->get();

        foreach ($activeMembers as $member) {
            // Calculate loyalty points based on membership duration and activity
            $daysActive = $member->created_at->diffInDays(now());
            $basePoints = $daysActive * 5; // 5 points per day
            $bookingCount = $member->bookings()
                ->where('status', 'confirmed')
                ->count();
            $bookingPoints = $bookingCount * 10; // 10 points per booking

            $totalPoints = max(0, $basePoints + $bookingPoints + random_int(-50, 100));

            // Update member with loyalty points
            $member->update(['loyalty_points' => $totalPoints]);

            // Create loyalty point transaction records
            $this->createLoyaltyTransactions($member, $totalPoints);
        }
    }

    private function createLoyaltyTransactions(Member $member, int $totalPoints): void
    {
        $pointsAccumulated = 0;

        // Transaction 1: Initial bonus points (new member)
        if ($pointsAccumulated < $totalPoints) {
            $pointsToAdd = min(50, $totalPoints - $pointsAccumulated);
            LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => $pointsToAdd,
                'type' => 'earned',
                'description' => 'Welcome bonus points',
                'source' => 'registration',
                'created_at' => $member->created_at->clone()->addDays(1),
            ]);
            $pointsAccumulated += $pointsToAdd;
        }

        // Transaction 2-N: Regular earning from bookings
        $daysActive = $member->created_at->diffInDays(now());
        $weekCount = max(1, intdiv($daysActive, 7));

        for ($week = 0; $week < min($weekCount, 10); $week++) {
            if ($pointsAccumulated >= $totalPoints) {
                break;
            }

            $weekPoints = random_int(10, 40);
            $pointsToAdd = min($weekPoints, $totalPoints - $pointsAccumulated);

            LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => $pointsToAdd,
                'type' => 'earned',
                'description' => 'Weekly booking points',
                'source' => 'booking',
                'created_at' => $member->created_at->clone()->addWeeks($week),
            ]);

            $pointsAccumulated += $pointsToAdd;
        }

        // Transaction N+1: Possible redemption (20% chance)
        if (fake()->boolean(20) && $pointsAccumulated >= 100) {
            $redeemAmount = random_int(50, min(300, (int) ($pointsAccumulated * 0.5)));

            LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => -$redeemAmount,
                'type' => 'redeemed',
                'description' => 'Redeemed for discount voucher',
                'source' => 'redemption',
                'created_at' => now()->subDays(random_int(0, 20)),
            ]);
        }

        // Transaction N+2: Possible expiration (5% of inactive members)
        if (fake()->boolean(5) && $member->subscriptions()->where('status', 'expired')->exists()) {
            $expireAmount = random_int(10, 50);

            LoyaltyPoint::create([
                'member_id' => $member->id,
                'points' => -$expireAmount,
                'type' => 'expired',
                'description' => 'Points expired due to inactivity',
                'source' => 'expiration',
                'created_at' => now()->subDays(random_int(30, 60)),
            ]);
        }
    }
}
