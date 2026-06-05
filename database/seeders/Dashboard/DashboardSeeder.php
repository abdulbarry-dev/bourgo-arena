<?php

namespace Database\Seeders\Dashboard;

use Database\Seeders\Dashboard\Users\AdminUserSeeder;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            \Database\Seeders\Dashboard\Users\AdminUserSeeder::class,
            \Database\Seeders\Dashboard\Users\ManagerUserSeeder::class,
            \Database\Seeders\Dashboard\Catalog\ServiceSeeder::class,
            \Database\Seeders\Dashboard\Catalog\PlanCatalogSeeder::class,
            \Database\Seeders\Dashboard\Members\MemberSeeder::class,
            \Database\Seeders\Dashboard\Catalog\CourseSeeder::class,
            \Database\Seeders\Dashboard\Catalog\CourseSessionSeeder::class,
            \Database\Seeders\Dashboard\Activities\ActivitySeeder::class,
            \Database\Seeders\Dashboard\Activities\ActivitySlotSeeder::class,
            \Database\Seeders\Dashboard\Bookings\CourtSlotSeeder::class,
            \Database\Seeders\Dashboard\Events\EventSeeder::class,
            \Database\Seeders\Dashboard\Events\EventParticipantSeeder::class,
            \Database\Seeders\Dashboard\Events\EventMatchSeeder::class,
        ]);
    }
}
