<?php

namespace Database\Seeders\Staging;

use App\Models\Member;
use App\Models\OccupancyHourlyAggregate;
use App\Models\Plan;
use App\Models\RevenueSnapshot;
use Illuminate\Database\Seeder;

class BulkAnalyticsSeeder extends Seeder
{
    private const DAYS = 90;

    public function run(): void
    {
        $this->seedRevenueSnapshots();
        $this->seedOccupancyData();

        $this->command?->info(sprintf(
            '  Analytics: %d revenue snapshots | %d occupancy records',
            RevenueSnapshot::count(),
            OccupancyHourlyAggregate::count()
        ));
    }

    private function seedRevenueSnapshots(): void
    {
        if (RevenueSnapshot::count() >= self::DAYS) {
            return;
        }

        $totalPlans = Plan::withoutGlobalScopes()->where('is_archived', false)->count();
        $totalMembers = Member::count() ?: 120;
        $activeNow = Member::where('status', 'active')->count() ?: 80;

        // Growth curve: revenue grows ~40% over 90 days with weekend spikes
        $baseRevenue = 800.0;
        $growthPerDay = 5.5;

        for ($i = self::DAYS; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $daysSinceStart = self::DAYS - $i;

            $isWeekend = in_array($date->dayOfWeek, [5, 6]);
            $weekendMultiplier = $isWeekend ? 1.35 : 1.0;

            $trend = $baseRevenue + ($growthPerDay * $daysSinceStart);
            $noise = rand(-150, 200);
            $dailyRevenue = max(400, ($trend + $noise) * $weekendMultiplier);
            $dailyRevenue = round($dailyRevenue, 3);

            $konnectPct = rand(50, 65) / 100;
            $cashPct = rand(25, 35) / 100;
            $loyaltyPct = 1 - $konnectPct - $cashPct;

            $konnectAmount = round($dailyRevenue * $konnectPct, 3);
            $cashAmount = round($dailyRevenue * $cashPct, 3);
            $loyaltyAmount = round($dailyRevenue * $loyaltyPct, 3);

            $activeSubs = max(20, (int) round($activeNow * 0.65 + ($daysSinceStart * 0.4) + rand(-5, 8)));
            $expiredSubs = max(2, (int) round($activeSubs * 0.08 + rand(-2, 3)));
            $newMembersToday = rand(0, $isWeekend ? 8 : 5);
            $pendingVerif = rand(4, 14);
            $pendingOnboard = rand(2, 9);
            $churnRate = round(rand(200, 900) / 100, 2);

            $resRevenue = round($dailyRevenue * rand(10, 20) / 100, 3);
            $resCount = rand(5, $isWeekend ? 25 : 15);

            $eventUpcoming = rand(1, 4);
            $eventInProgress = rand(0, 1);
            $eventCompleted = max(0, (int) floor($daysSinceStart / 8) + rand(0, 2));
            $eventCancelled = rand(0, 1);
            $totalParticipants = rand(15, 60) + (int) floor($daysSinceStart * 0.3);

            $planMetrics = [];
            if ($totalPlans > 0) {
                $plans = Plan::withoutGlobalScopes()->where('is_archived', false)->get();
                foreach ($plans as $plan) {
                    $planMetrics[$plan->name] = rand(1, 20);
                }
            }

            RevenueSnapshot::updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'total_revenue' => $dailyRevenue,
                    'active_subscriptions' => $activeSubs,
                    'expired_subscriptions' => $expiredSubs,
                    'churn_rate' => $churnRate,
                    'revenue_by_method' => [
                        'konnect' => $konnectAmount,
                        'cash' => $cashAmount,
                        'loyalty' => $loyaltyAmount,
                    ],
                    'plan_metrics' => $planMetrics,
                    'member_metrics' => [
                        'total' => max(50, $totalMembers - ($i * rand(0, 2))),
                        'active' => max(30, $activeNow - ($i * rand(0, 1))),
                        'new_today' => $newMembersToday,
                        'pending_verification' => $pendingVerif,
                        'pending_onboarding' => $pendingOnboard,
                        'family_accounts' => rand(5, 15),
                        'inactive' => rand(5, 20),
                        'suspended' => rand(2, 8),
                    ],
                    'event_metrics' => [
                        'upcoming' => $eventUpcoming,
                        'in_progress' => $eventInProgress,
                        'completed' => $eventCompleted,
                        'canceled' => $eventCancelled,
                        'total_participants' => $totalParticipants,
                        'avg_fill_rate' => rand(45, 95),
                    ],
                    'activity_metrics' => [
                        'active_activities' => rand(8, 18),
                        'reservations_today' => $resCount,
                        'revenue_from_reservations' => $resRevenue,
                        'cancellation_rate' => round(rand(3, 15), 1),
                    ],
                ]
            );
        }
    }

    private function seedOccupancyData(): void
    {
        if (OccupancyHourlyAggregate::count() >= self::DAYS * 14) {
            return;
        }

        OccupancyHourlyAggregate::truncate();

        for ($day = self::DAYS; $day >= 0; $day--) {
            $date = now()->subDays($day);
            $isWeekend = in_array($date->dayOfWeek, [5, 6]);

            for ($hour = 6; $hour <= 22; $hour++) {
                [$entries, $exits, $avgOcc] = $this->hourlyOccupancy($hour, $isWeekend);

                OccupancyHourlyAggregate::create([
                    'date' => $date->toDateString(),
                    'hour' => $hour,
                    'entries_count' => $entries,
                    'exits_count' => $exits,
                    'avg_occupancy' => $avgOcc,
                ]);
            }
        }
    }

    private function hourlyOccupancy(int $hour, bool $isWeekend): array
    {
        $weekendBoost = $isWeekend ? 1.4 : 1.0;

        $base = match (true) {
            $hour >= 6 && $hour < 8 => rand(2, 6),
            $hour >= 8 && $hour < 10 => rand(12, 22),
            $hour >= 10 && $hour < 12 => rand(18, 30),
            $hour >= 12 && $hour < 14 => rand(8, 16),
            $hour >= 14 && $hour < 16 => rand(10, 20),
            $hour >= 16 && $hour < 18 => rand(22, 38),
            $hour >= 18 && $hour < 20 => rand(30, 50),
            $hour >= 20 && $hour < 22 => rand(20, 35),
            default => rand(3, 8),
        };

        $base = (int) round($base * $weekendBoost);
        $entries = max(0, $base + rand(-3, 4));
        $exits = max(0, $base + rand(-4, 3));
        $avg = max(0, (int) round(($entries + $exits) / 2 + rand(-2, 5)));

        return [$entries, $exits, $avg];
    }
}
