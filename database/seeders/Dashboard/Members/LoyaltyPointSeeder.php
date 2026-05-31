<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\LoyaltyPoint;
use App\Models\Member;
use Illuminate\Database\Seeder;

class LoyaltyPointSeeder extends Seeder
{
    public function run(): void
    {
        $pointsByEmail = [
            'amira.elmansouri@example.com' => 120,
            'othman.bennis@example.com' => 260,
            'lina.chafik@example.com' => 340,
            'karim.aitali@example.com' => 480,
            'nadia.rachid@example.com' => 620,
            'yassine.elfassi@example.com' => 780,
            'siham.ziani@example.com' => 910,
            'mehdi.amrani@example.com' => 1040,
            'rania.boulahya@example.com' => 1150,
            'hicham.elouardi@example.com' => 1320,
            'sara.berrada@example.com' => 1490,
            'bilal.hajar@example.com' => 1670,
        ];

        foreach ($pointsByEmail as $email => $points) {
            $member = Member::query()->where('email', $email)->first();

            if ($member === null) {
                continue;
            }

            LoyaltyPoint::query()->updateOrCreate(
                ['idempotency_key' => 'member-seed-'.$member->email],
                [
                    'member_id' => $member->id,
                    'points' => $points,
                    'transaction_type' => 'seed',
                    'source_type' => 'member_seed',
                    'source_id' => $member->id,
                    'created_at' => now(),
                ],
            );
        }
    }
}
