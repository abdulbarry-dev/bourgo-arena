<?php

namespace Database\Seeders\Api;

use Database\Seeders\Dashboard\Activities\ActivitySeeder;
use Database\Seeders\Dashboard\Activities\ActivitySlotSeeder;
use Database\Seeders\Dashboard\Catalog\CourseSeeder;
use Database\Seeders\Dashboard\Catalog\CourseSessionSeeder;
use Database\Seeders\Dashboard\Catalog\PlanCatalogSeeder;
use Database\Seeders\Dashboard\Catalog\ServiceSeeder;
use Database\Seeders\Dashboard\Events\EventMatchSeeder;
use Database\Seeders\Dashboard\Events\EventParticipantSeeder;
use Database\Seeders\Dashboard\Events\EventSeeder;
use Database\Seeders\Dashboard\Users\AdminUserSeeder;
use Database\Seeders\Dashboard\Users\ManagerUserSeeder;
use Illuminate\Database\Seeder;

class MobileApplicationSeeder extends Seeder
{
    /**
     * Seed the application's database for mobile view.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            ManagerUserSeeder::class,
            ServiceSeeder::class,
            PlanCatalogSeeder::class,
            MobileUserSeeder::class,
            CourseSeeder::class,
            CourseSessionSeeder::class,
            ActivitySeeder::class,
            ActivitySlotSeeder::class,
            EventSeeder::class,
            EventParticipantSeeder::class,
            EventMatchSeeder::class,
        ]);
    }
}
