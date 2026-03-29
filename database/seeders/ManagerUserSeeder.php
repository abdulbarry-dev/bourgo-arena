<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class ManagerUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'manager@bourgoarena.com'],
            [
                'name' => 'Manager User',
                'password' => 'Test@12345',
                'role' => UserRole::Manager,
                'email_verified_at' => now(),
            ],
        );
    }
}
