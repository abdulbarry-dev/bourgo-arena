<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@bourgoarena.com'],
            [
                'name' => 'Admin',
                'role' => UserRole::Admin,
                'password' => 'Test@12345',
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'manager@bourgoarena.com'],
            [
                'name' => 'Manager',
                'role' => UserRole::Manager,
                'password' => 'Test@12345',
                'email_verified_at' => now(),
            ]
        );
    }
}
