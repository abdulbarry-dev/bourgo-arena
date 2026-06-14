<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class MemberUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'iyed.tawila7@gmail.com'],
            [
                'name' => 'Iyed Tawila',
                'role' => UserRole::Member,
                'password' => 'Password@12345',
                'email_verified_at' => now(),
            ]
        );
    }
}
