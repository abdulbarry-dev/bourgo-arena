<?php

namespace Database\Seeders\Dashboard;

use Database\Seeders\Dashboard\Users\AdminUserSeeder;
use Illuminate\Database\Seeder;

class DashboardSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([AdminUserSeeder::class]);
    }
}
