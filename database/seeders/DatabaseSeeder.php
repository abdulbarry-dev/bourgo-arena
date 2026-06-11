<?php

namespace Database\Seeders;

use Database\Seeders\Api\MobileApplicationSeeder;
use Database\Seeders\Staging\StressSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * - staging / local:  php artisan db:seed         → full stress dataset
     * - mobile dev only:  php artisan db:seed --class=MobileApplicationSeeder
     * - full dataset:     php artisan db:seed --class=FullApplicationSeeder
     */
    public function run(): void
    {
        $env = app()->environment();

        if ($env === 'production') {
            // Production: only the mobile demo account + notification types.
            $this->call(MobileApplicationSeeder::class);

            return;
        }

        // staging / local / testing: full stress dataset
        $this->call(StressSeeder::class);
    }
}
