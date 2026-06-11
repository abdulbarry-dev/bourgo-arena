<?php

namespace Database\Seeders\Staging;

use Database\Seeders\Dashboard\DashboardSeeder;
use Database\Seeders\NotificationTypeSeeder;
use Illuminate\Database\Seeder;

/**
 * Full stress-test dataset for staging and review environments.
 *
 * Produces:
 *  - 500+ members (families, various verification states)
 *  - 10 services, 20+ plans, 20+ courses, 20+ activities
 *  - 800+ subscriptions across all statuses
 *  - 600+ payments (konnect / cash / loyalty) with full audit trails
 *  - 1,500+ loyalty point entries + audit logs
 *  - 17+ events with seeded brackets and results
 *  - 2,000+ member notifications
 *  - 90 days of revenue snapshots + hourly occupancy data
 */
class StressSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('');
        $this->command?->info('════════════════════════════════════════════════════');
        $this->command?->info('  Bourgo Arena — Stress Dataset Seeder');
        $this->command?->info('════════════════════════════════════════════════════');

        $this->command?->info('[1/8] Seeding base dashboard data...');
        $this->call(DashboardSeeder::class);

        $this->command?->info('[2/8] Seeding notification types...');
        $this->call(NotificationTypeSeeder::class);

        $this->command?->info('[3/8] Seeding 500+ members...');
        $this->call(BulkMembersSeeder::class);

        $this->command?->info('[4/8] Seeding extra services, plans, courses, activities...');
        $this->call(BulkCatalogSeeder::class);

        $this->command?->info('[5/8] Seeding subscriptions, payments & loyalty transactions...');
        $this->call(BulkSubscriptionsPaymentsSeeder::class);

        $this->command?->info('[6/8] Seeding 17+ events with brackets...');
        $this->call(BulkEventsSeeder::class);

        $this->command?->info('[7/8] Seeding 90-day analytics snapshots + occupancy data...');
        $this->call(BulkAnalyticsSeeder::class);

        $this->command?->info('[8/8] Seeding member notifications...');
        $this->call(BulkNotificationsSeeder::class);

        $this->command?->info('');
        $this->command?->info('════════════════════════════════════════════════════');
        $this->command?->info('  ✓ Stress dataset seeded successfully.');
        $this->command?->info('════════════════════════════════════════════════════');
        $this->command?->info('');
    }
}
