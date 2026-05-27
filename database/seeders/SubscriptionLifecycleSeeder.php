<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class SubscriptionLifecycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Plan::query()->where('is_archived', false)->doesntExist()) {
            $this->call(PlanCatalogSeeder::class);
        }

        $this->call([
            PendingMemberSeeder::class,
            ActiveSubscriptionSeeder::class,
            SuspendedSubscriptionSeeder::class,
            ExpiredSubscriptionSeeder::class,
            SubscriptionTransferSeeder::class,
        ]);
    }
}
