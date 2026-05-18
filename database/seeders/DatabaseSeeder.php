<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Use ComprehensiveAppDataSeeder for full test data with realistic scenarios:
     *   php artisan db:seed --class=ComprehensiveAppDataSeeder
     *
     * Or use this default minimal seeding:
     *   php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            ManagerUserSeeder::class,
            AdminUserSeeder::class,
            PlanCatalogSeeder::class,
            SubscriptionLifecycleSeeder::class,
            CourseSeeder::class,
            CoursePlanSeeder::class,
        ]);
    }
}
