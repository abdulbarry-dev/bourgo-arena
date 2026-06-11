<?php

namespace Database\Seeders\Staging;

use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BulkMembersSeeder extends Seeder
{
    private const TARGET = 500;

    private array $maleFirstNames = [
        'Adam', 'Youssef', 'Karim', 'Mehdi', 'Hicham', 'Bilal', 'Othman', 'Rami',
        'Amine', 'Nassim', 'Mohamed', 'Sami', 'Walid', 'Fares', 'Zied', 'Houssem',
        'Anis', 'Marouane', 'Skander', 'Aziz', 'Oussama', 'Nizar', 'Hamza', 'Aymen',
        'Tarek', 'Khalil', 'Seifeddine', 'Sofiene', 'Hatem', 'Raouf', 'Iheb', 'Wassim',
        'Khaled', 'Bassem', 'Maher', 'Slim', 'Wissem', 'Farouk', 'Fethi', 'Chokri',
    ];

    private array $femaleFirstNames = [
        'Amira', 'Sara', 'Nadia', 'Rania', 'Siham', 'Lina', 'Meriem', 'Ines',
        'Nora', 'Hela', 'Yasmine', 'Selma', 'Dorra', 'Khaoula', 'Fatma', 'Aicha',
        'Mouna', 'Leila', 'Salma', 'Asma', 'Wafa', 'Rim', 'Sonia', 'Olfa',
        'Hanen', 'Radhia', 'Samira', 'Najet', 'Abir', 'Dalila', 'Emna', 'Ghofrane',
        'Hajer', 'Imen', 'Jihen', 'Kenza', 'Lobna', 'Manel', 'Nawel', 'Ons',
    ];

    private array $lastNames = [
        'Ben Salah', 'El Mansouri', 'Bennis', 'Chafik', 'Rachid', 'El Fassi',
        'Ziani', 'Amrani', 'Berrada', 'Ben Salem', 'Cherif', 'Haddad', 'Khelifi',
        'Jaziri', 'Toumi', 'Mansouri', 'Trabelsi', 'Elloumi', 'Gharbi', 'Jebali',
        'Karray', 'Laabidi', 'Maamouri', 'Nasr', 'Oueslati', 'Riahi', 'Sfaxi',
        'Tlili', 'Zghal', 'Abidi', 'Baccar', 'Chaari', 'Dridi', 'Fakhfakh',
        'Hamrouni', 'Jerbi', 'Khemiri', 'Louati', 'Mbarki', 'Nouri',
    ];

    public function run(): void
    {
        $existing = Member::count();

        if ($existing < self::TARGET) {
            $needed = self::TARGET - $existing;
            $this->command?->info("Creating {$needed} members (current: {$existing})...");
            $this->createMembers($existing, $needed);
        } else {
            $this->command?->info("Members at target ({$existing}). Checking date distribution...");
        }

        // Always ensure historical join dates are spread over 90 days.
        // This fixes cases where members were created with today's timestamp.
        $this->ensureHistoricalDates();

        if (Member::whereNotNull('parent_id')->doesntExist()) {
            $this->createFamilyAccounts();
        }

        $this->command?->info('  Members: '.Member::count());
    }

    private function createMembers(int $existingIndex, int $needed): void
    {
        $distributions = [
            ['status' => 'active', 'state' => 'active', 'verified' => true, 'onboarded' => true, 'weight' => 60],
            ['status' => 'active', 'state' => 'pending_verification', 'verified' => false, 'onboarded' => false, 'weight' => 12],
            ['status' => 'pending_onboarding', 'state' => 'pending_onboarding', 'verified' => true, 'onboarded' => false, 'weight' => 10],
            ['status' => 'pending_additional_verification', 'state' => 'pending_additional_verification', 'verified' => false, 'onboarded' => false, 'weight' => 8],
            ['status' => 'inactive', 'state' => 'active', 'verified' => true, 'onboarded' => true, 'weight' => 10],
        ];

        $buckets = $this->distributeToBuckets($needed, $distributions);
        $index = $existingIndex + 1;

        foreach ($distributions as $k => $dist) {
            $count = $buckets[$k];

            for ($i = 0; $i < $count; $i++) {
                $isMale = ($index % 2 === 0);
                $firstName = $isMale
                    ? $this->maleFirstNames[array_rand($this->maleFirstNames)]
                    : $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
                $lastName = $this->lastNames[array_rand($this->lastNames)];

                $daysAgo = match (true) {
                    $dist['status'] === 'active' && $dist['state'] === 'active' => rand(1, 180),
                    $dist['status'] === 'inactive' => rand(30, 365),
                    default => rand(0, 30),
                };

                $loyaltyPoints = $dist['status'] === 'active' && $dist['state'] === 'active'
                    ? rand(0, 15000)
                    : 0;

                Member::create([
                    'name' => $firstName.' '.$lastName,
                    'email' => strtolower(Str::slug($firstName).'.'.Str::slug($lastName).$index).'@mail.tn',
                    'phone' => '2'.str_pad((string) (1000000 + $index), 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => now()->subYears(rand(18, 65))->subDays(rand(0, 364))->toDateString(),
                    'gender' => $isMale ? 'male' : 'female',
                    'emergency_contact' => '5'.str_pad((string) (1000000 + $index + 500), 7, '0', STR_PAD_LEFT),
                    'status' => $dist['status'],
                    'state' => $dist['state'],
                    'rgpd_consented_at' => now()->subDays($daysAgo),
                    'email_verified_at' => $dist['verified'] ? now()->subDays($daysAgo) : null,
                    'phone_verified_at' => $dist['verified'] ? now()->subDays($daysAgo) : null,
                    'onboarding_completed_at' => $dist['onboarded'] ? now()->subDays($daysAgo) : null,
                    'loyalty_points' => $loyaltyPoints,
                    'password' => bcrypt('Test@12345'),
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);

                $index++;
            }
        }
    }

    /**
     * Distribute all members' created_at over 90 days using a linear growth curve.
     * Grows from ~2 new members/day (90 days ago) to ~9/day (today), summing to ~500.
     *
     * Only runs when the majority of members were created very recently (typical of a
     * fresh seed where Member::factory() stamped everything with today's timestamp).
     */
    private function ensureHistoricalDates(): void
    {
        $total = Member::count();

        if ($total === 0) {
            return;
        }

        // Skip if already spread out: fewer than 50% of members created in the last 3 days
        $recentCount = Member::whereDate('created_at', '>=', now()->subDays(3))->count();

        if (($recentCount / $total) < 0.5) {
            $this->command?->info('  Member join dates already distributed. Skipping backdate.');

            return;
        }

        $this->command?->info("  Backdating {$total} member join dates over 90 days...");

        // Build date pool: linear growth from 2/day (day 0 = 90 days ago) to 9/day (today).
        // Sum: 91 * 2 + 7 * (90/2) = 182 + 315 = 497 ≈ 500 members.
        $pool = [];

        for ($daysAgo = 90; $daysAgo >= 0; $daysAgo--) {
            $daysSinceStart = 90 - $daysAgo;
            $slotsForDay = max(1, (int) round(2 + ($daysSinceStart / 90) * 7));
            $baseDate = now()->subDays($daysAgo);

            for ($j = 0; $j < $slotsForDay; $j++) {
                $pool[] = $baseDate->copy()
                    ->setHour(rand(7, 22))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));
            }
        }

        shuffle($pool);

        // Pad to cover all members if pool is slightly smaller
        while (count($pool) < $total) {
            $pool[] = now()->subDays(rand(0, 30))->setHour(rand(8, 21))->setMinute(rand(0, 59));
        }

        $pool = array_slice($pool, 0, $total);

        $memberIds = Member::orderBy('id')->pluck('id')->toArray();

        foreach ($memberIds as $idx => $memberId) {
            /** @var Carbon $date */
            $date = $pool[$idx];
            Member::where('id', $memberId)->update([
                'created_at' => $date,
                'updated_at' => $date->copy()->addMinutes(rand(0, 120)),
            ]);
        }

        $this->command?->info('  Done. Members spread from '.now()->subDays(90)->format('M d').' to '.now()->format('M d').'.');
    }

    private function createFamilyAccounts(): void
    {
        $parents = Member::where('status', 'active')
            ->where('state', 'active')
            ->whereNull('parent_id')
            ->where('is_family_account', false)
            ->inRandomOrder()
            ->take(30)
            ->get();

        $childIndex = Member::max('id') + 100;

        foreach ($parents as $parent) {
            $parent->update(['is_family_account' => true]);
            $childCount = rand(1, 3);
            $lastName = explode(' ', $parent->name)[1] ?? 'Child';

            for ($c = 0; $c < $childCount; $c++) {
                $isMale = ($c % 2 === 0);
                $firstName = $isMale
                    ? $this->maleFirstNames[array_rand($this->maleFirstNames)]
                    : $this->femaleFirstNames[array_rand($this->femaleFirstNames)];

                $daysAgo = rand(0, 60);

                Member::create([
                    'parent_id' => $parent->id,
                    'name' => $firstName.' '.$lastName,
                    'email' => 'child.'.strtolower(Str::slug($firstName)).'.'.strtolower(Str::slug($lastName)).$childIndex.'@mail.tn',
                    'phone' => '9'.str_pad((string) (1000000 + $childIndex), 7, '0', STR_PAD_LEFT),
                    'date_of_birth' => now()->subYears(rand(5, 17))->toDateString(),
                    'gender' => $isMale ? 'male' : 'female',
                    'emergency_contact' => $parent->phone,
                    'status' => 'active',
                    'state' => 'active',
                    'rgpd_consented_at' => now()->subDays($daysAgo),
                    'email_verified_at' => now()->subDays($daysAgo),
                    'phone_verified_at' => now()->subDays($daysAgo),
                    'onboarding_completed_at' => now()->subDays($daysAgo),
                    'loyalty_points' => rand(0, 2000),
                    'password' => bcrypt('Test@12345'),
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);

                $childIndex++;
            }
        }
    }

    private function distributeToBuckets(int $total, array $distributions): array
    {
        $totalWeight = array_sum(array_column($distributions, 'weight'));
        $buckets = [];
        $assigned = 0;

        foreach ($distributions as $k => $dist) {
            if ($k === array_key_last($distributions)) {
                $buckets[$k] = $total - $assigned;
            } else {
                $buckets[$k] = (int) round($total * $dist['weight'] / $totalWeight);
                $assigned += $buckets[$k];
            }
        }

        return $buckets;
    }
}
