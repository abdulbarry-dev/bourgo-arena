<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\Member;
use App\Models\MemberOnboardingToken;
use Illuminate\Database\Seeder;

class MemberOnboardingTokenSeeder extends Seeder
{
    public function run(): void
    {
        $members = [
            'lina.chafik@example.com',
            'karim.aitali@example.com',
            'yassine.elfassi@example.com',
            'siham.ziani@example.com',
        ];

        foreach ($members as $index => $email) {
            $member = Member::query()->where('email', $email)->first();

            if ($member === null) {
                continue;
            }

            MemberOnboardingToken::query()->updateOrCreate(
                ['email' => $member->email],
                [
                    'member_id' => $member->id,
                    'token_hash' => hash('sha256', 'onboarding-'.$member->email),
                    'expires_at' => now()->addDays(7 - $index),
                    'used_at' => $index === 0 ? now()->subDay() : null,
                ],
            );
        }
    }
}
