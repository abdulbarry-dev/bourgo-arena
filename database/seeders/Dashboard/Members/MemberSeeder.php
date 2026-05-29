<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\LoyaltyPoint;
use App\Models\Member;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            ['name' => 'Amira El Mansouri', 'email' => 'amira.elmansouri@example.com', 'phone' => '300000001', 'gender' => 'female', 'points' => 120],
            ['name' => 'Othman Bennis', 'email' => 'othman.bennis@example.com', 'phone' => '300000002', 'gender' => 'male', 'points' => 260],
            ['name' => 'Lina Chafik', 'email' => 'lina.chafik@example.com', 'phone' => '300000003', 'gender' => 'female', 'points' => 340],
            ['name' => 'Karim Ait Ali', 'email' => 'karim.aitali@example.com', 'phone' => '300000004', 'gender' => 'male', 'points' => 480],
            ['name' => 'Nadia Rachid', 'email' => 'nadia.rachid@example.com', 'phone' => '300000005', 'gender' => 'female', 'points' => 620],
            ['name' => 'Yassine El Fassi', 'email' => 'yassine.elfassi@example.com', 'phone' => '300000006', 'gender' => 'male', 'points' => 780],
            ['name' => 'Siham Ziani', 'email' => 'siham.ziani@example.com', 'phone' => '300000007', 'gender' => 'female', 'points' => 910],
            ['name' => 'Mehdi Amrani', 'email' => 'mehdi.amrani@example.com', 'phone' => '300000008', 'gender' => 'male', 'points' => 1040],
            ['name' => 'Rania Boulahya', 'email' => 'rania.boulahya@example.com', 'phone' => '300000009', 'gender' => 'female', 'points' => 1150],
            ['name' => 'Hicham El Ouardi', 'email' => 'hicham.elouardi@example.com', 'phone' => '300000010', 'gender' => 'male', 'points' => 1320],
            ['name' => 'Sara Berrada', 'email' => 'sara.berrada@example.com', 'phone' => '300000011', 'gender' => 'female', 'points' => 1490],
            ['name' => 'Bilal Hajar', 'email' => 'bilal.hajar@example.com', 'phone' => '300000012', 'gender' => 'male', 'points' => 1670],
        ];

        foreach ($members as $index => $memberData) {
            $member = Member::query()->updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'phone' => $memberData['phone'],
                    'date_of_birth' => now()->subYears(24 + $index)->toDateString(),
                    'gender' => $memberData['gender'],
                    'emergency_contact' => '900000'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                    'avatar' => null,
                    'status' => 'active',
                    'state' => 'active',
                    'rgpd_consented_at' => now(),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'onboarding_completed_at' => now(),
                    'password' => 'Test@12345',
                    'is_family_account' => false,
                    'is_archived' => false,
                    'loyalty_points' => $memberData['points'],
                ],
            );

            LoyaltyPoint::query()->updateOrCreate(
                ['idempotency_key' => 'member-seed-'.$member->email],
                [
                    'member_id' => $member->id,
                    'points' => $memberData['points'],
                    'transaction_type' => 'seed',
                    'source_type' => 'member_seed',
                    'source_id' => $member->id,
                    'created_at' => now(),
                ],
            );
        }
    }
}
