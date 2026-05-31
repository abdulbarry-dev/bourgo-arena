<?php

namespace Database\Seeders\Dashboard\Analytics;

use App\Models\RevenueSnapshot;
use Illuminate\Database\Seeder;

class RevenueSnapshotSeeder extends Seeder
{
    public function run(): void
    {
        $snapshots = [
            ['date' => now()->subDays(2)->toDateString(), 'total_revenue' => 1328.00, 'active_subscriptions' => 5, 'expired_subscriptions' => 1, 'churn_rate' => 12.50, 'revenue_by_method' => ['cash' => 28.00, 'konnect' => 1300.00], 'plan_metrics' => ['Starter Monthly' => 1, 'Performance Monthly' => 2]],
            ['date' => now()->subDay()->toDateString(), 'total_revenue' => 1617.00, 'active_subscriptions' => 6, 'expired_subscriptions' => 1, 'churn_rate' => 10.00, 'revenue_by_method' => ['cash' => 129.00, 'konnect' => 1488.00], 'plan_metrics' => ['Quarterly Plus' => 1, 'Annual Elite' => 1]],
            ['date' => now()->toDateString(), 'total_revenue' => 1745.00, 'active_subscriptions' => 6, 'expired_subscriptions' => 1, 'churn_rate' => 8.50, 'revenue_by_method' => ['cash' => 154.00, 'konnect' => 1591.00], 'plan_metrics' => ['Starter Monthly' => 2, 'Annual Elite' => 2]],
        ];

        foreach ($snapshots as $snapshotData) {
            RevenueSnapshot::query()->updateOrCreate(
                ['date' => $snapshotData['date']],
                $snapshotData,
            );
        }
    }
}
