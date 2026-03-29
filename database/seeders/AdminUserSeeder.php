<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@bourgoarena.com'],
            [
                'name' => 'Admin User',
                'password' => 'Test@12345',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );
    }
}
