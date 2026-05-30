<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\Member;
use App\Models\MemberDeviceToken;
use Illuminate\Database\Seeder;

class MemberDeviceTokenSeeder extends Seeder
{
    public function run(): void
    {
        $tokens = [
            ['email' => 'amira.elmansouri@example.com', 'provider' => 'firebase', 'device_type' => 'ios'],
            ['email' => 'othman.bennis@example.com', 'provider' => 'firebase', 'device_type' => 'android'],
            ['email' => 'nadia.rachid@example.com', 'provider' => 'firebase', 'device_type' => 'ios'],
            ['email' => 'bilal.hajar@example.com', 'provider' => 'firebase', 'device_type' => 'android'],
        ];

        foreach ($tokens as $index => $tokenData) {
            $member = Member::query()->where('email', $tokenData['email'])->first();

            if ($member === null) {
                continue;
            }

            MemberDeviceToken::query()->updateOrCreate(
                ['token' => 'device-token-'.$index.'-'.$member->id],
                [
                    'member_id' => $member->id,
                    'provider' => $tokenData['provider'],
                    'device_type' => $tokenData['device_type'],
                    'is_active' => true,
                    'last_used_at' => now()->subDays($index + 1),
                ],
            );
        }
    }
}
