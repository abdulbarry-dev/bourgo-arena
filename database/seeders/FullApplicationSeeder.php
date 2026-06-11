<?php

namespace Database\Seeders;

use Database\Seeders\Staging\StressSeeder;
use Illuminate\Database\Seeder;

/**
 * Full application seeder — runs the complete stress dataset.
 *
 * Use this for staging / review environments:
 *   php artisan db:seed --class=FullApplicationSeeder
 *
 * For production, run only targeted seeders (no bulk data).
 */
class FullApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(StressSeeder::class);
    }
}
