<?php

namespace Database\Seeders\Dashboard\Members;

use App\Models\LoyaltyPoint;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $members = [
            ['name' => 'Amira El Mansouri', 'email' => 'amira.elmansouri@example.com', 'phone' => '300000001', 'gender' => 'female', 'points' => 120, 'years_ago' => 24],
            ['name' => 'Othman Bennis', 'email' => 'othman.bennis@example.com', 'phone' => '300000002', 'gender' => 'male', 'points' => 260, 'years_ago' => 37, 'is_family_account' => true],
            ['name' => 'Lina Chafik', 'email' => 'lina.chafik@example.com', 'phone' => '300000003', 'gender' => 'female', 'points' => 340, 'years_ago' => 16, 'parent_email' => 'othman.bennis@example.com'],
            ['name' => 'Karim Ait Ali', 'email' => 'karim.aitali@example.com', 'phone' => '300000004', 'gender' => 'male', 'points' => 480, 'years_ago' => 18, 'parent_email' => 'othman.bennis@example.com'],
            ['name' => 'Nadia Rachid', 'email' => 'nadia.rachid@example.com', 'phone' => '300000005', 'gender' => 'female', 'points' => 620, 'years_ago' => 35, 'is_family_account' => true],
            ['name' => 'Yassine El Fassi', 'email' => 'yassine.elfassi@example.com', 'phone' => '300000006', 'gender' => 'male', 'points' => 780, 'years_ago' => 14, 'parent_email' => 'nadia.rachid@example.com'],
            ['name' => 'Siham Ziani', 'email' => 'siham.ziani@example.com', 'phone' => '300000007', 'gender' => 'female', 'points' => 910, 'years_ago' => 12, 'parent_email' => 'nadia.rachid@example.com'],
            ['name' => 'Mehdi Amrani', 'email' => 'mehdi.amrani@example.com', 'phone' => '300000008', 'gender' => 'male', 'points' => 1040, 'years_ago' => 29],
            ['name' => 'Rania Boulahya', 'email' => 'rania.boulahya@example.com', 'phone' => '300000009', 'gender' => 'female', 'points' => 1150, 'years_ago' => 31],
            ['name' => 'Hicham El Ouardi', 'email' => 'hicham.elouardi@example.com', 'phone' => '300000010', 'gender' => 'male', 'points' => 1320, 'years_ago' => 28],
            ['name' => 'Sara Berrada', 'email' => 'sara.berrada@example.com', 'phone' => '300000011', 'gender' => 'female', 'points' => 1490, 'years_ago' => 26],
            ['name' => 'Bilal Hajar', 'email' => 'bilal.hajar@example.com', 'phone' => '300000012', 'gender' => 'male', 'points' => 1670, 'years_ago' => 22],
        ];

        $createdMembers = [];

        foreach ($members as $memberData) {
            $member = Member::query()->updateOrCreate(
                ['email' => $memberData['email']],
                [
                    'name' => $memberData['name'],
                    'phone' => $memberData['phone'],
                    'date_of_birth' => now()->subYears($memberData['years_ago'])->toDateString(),
                    'gender' => $memberData['gender'],
                    'emergency_contact' => '900000'.str_pad((string) (count($createdMembers) + 1), 3, '0', STR_PAD_LEFT),
                    'avatar' => null,
                    'status' => 'active',
                    'state' => 'active',
                    'rgpd_consented_at' => now(),
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'onboarding_completed_at' => now(),
                    'password' => 'Test@12345',
                    'is_family_account' => $memberData['is_family_account'] ?? false,
                    'is_archived' => false,
                    'loyalty_points' => $memberData['points'],
                ],
            );

            $createdMembers[$member->email] = $member;
        }

        foreach ($members as $memberData) {
            if (! isset($memberData['parent_email'])) {
                continue;
            }

            $parent = $createdMembers[$memberData['parent_email']] ?? null;

            if ($parent === null) {
                continue;
            }

            Member::query()
                ->where('email', $memberData['email'])
                ->update(['parent_id' => $parent->id]);
        }

        // Ensure we have plans
        if (Plan::count() === 0) {
            Service::factory()->create();
            Plan::factory()->count(5)->create();
        }
        $plans = Plan::all();

        // Create remaining members to reach target total (default 500)
        $currentCount = Member::count();
        $targetCount = config('seeder.members.target', 500);

        if ($currentCount < $targetCount) {
            $membersToCreate = $targetCount - $currentCount;

            $newMembers = Member::factory()->active()
                ->count($membersToCreate)
                ->has(Subscription::factory()->state(fn () => ['plan_id' => $plans->random()->id]))
                ->create();

            $loyaltyPoints = $newMembers->map(fn ($member) => [
                'member_id' => $member->id,
                'points' => fake()->numberBetween(0, 1000),
                'transaction_type' => 'manual',
                'source_type' => 'manual',
                'idempotency_key' => Str::uuid()->toString(),
                'created_at' => now(),
            ])->toArray();
            LoyaltyPoint::insert($loyaltyPoints);
        }
    }
}
