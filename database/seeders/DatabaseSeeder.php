<?php

namespace Database\Seeders;

use Database\Seeders\Dashboard\Users\AdminUserSeeder;
use Database\Seeders\Dashboard\Users\ManagerUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Use this default minimal seeding:
     *   php artisan db:seed
     */
    public function run(): void
    {
        $this->call([
            ManagerUserSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
