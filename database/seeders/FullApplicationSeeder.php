<?php

namespace Database\Seeders;

use Database\Seeders\Dashboard\DashboardSeeder;
use Illuminate\Database\Seeder;

class FullApplicationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DashboardSeeder::class);
    }
}
