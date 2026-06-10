<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\ApiReservation;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Event;
use App\Models\EventParticipant;
use App\Models\Member;
use App\Models\OccupancyHourlyAggregate;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\RevenueSnapshot;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DashboardAnalyticsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('Seeding full analytics dashboard data...');

        $this->seedServices();
        $this->seedPlans();
        $this->seedUsers();

        $plans = Plan::where('is_archived', false)->get();
        $service = Service::first();

        $this->seedMembers($plans);
        $members = Member::all();
        $activeMembers = Member::where('status', 'active')->get();

        $this->seedSubscriptions($activeMembers, $plans);
        $this->seedPayments($members);
        $this->seedRevenueSnapshots();
        $this->seedEvents();
        $this->seedActivitiesAndReservations($service, $members);
        $this->seedOccupancyData();
        $this->seedCourses($service);

        $this->command?->info(
            sprintf(
                'Done! %d members, %d subs, %d snapshots, %d events, %d activities, %d reservations, %d occupancy records, %d courses.',
                Member::count(),
                Subscription::count(),
                RevenueSnapshot::count(),
                Event::count(),
                Activity::count(),
                ApiReservation::count(),
                OccupancyHourlyAggregate::count(),
                Course::count(),
            )
        );
    }

    protected function seedServices(): void
    {
        if (Service::count() > 0) {
            return;
        }

        $services = [
            ['name' => 'Fitness & Gym', 'slug' => 'fitness-gym', 'description' => 'Full access to our state-of-the-art gym and fitness equipment.', 'status' => 'active'],
            ['name' => 'Padel Courts', 'slug' => 'padel-courts', 'description' => 'Premium padel courts available for booking and competitive play.', 'status' => 'active'],
            ['name' => 'Tennis Academy', 'slug' => 'tennis-academy', 'description' => 'Professional tennis coaching and court rentals for all skill levels.', 'status' => 'active'],
            ['name' => 'Wellness Center', 'slug' => 'wellness-center', 'description' => 'Relax and recover with our spa, sauna, and specialized wellness programs.', 'status' => 'active'],
        ];

        foreach ($services as $data) {
            Service::updateOrCreate(['slug' => $data['slug']], $data);
        }
    }

    protected function seedPlans(): void
    {
        if (Plan::count() > 0) {
            return;
        }

        $fitness = Service::where('slug', 'fitness-gym')->first();
        $tennis = Service::where('slug', 'tennis-academy')->first();
        $padel = Service::where('slug', 'padel-courts')->first();

        $plans = [
            ['name' => 'Starter Monthly', 'price' => 89.000, 'duration_days' => 30, 'service_id' => $fitness?->id],
            ['name' => 'Performance Monthly', 'price' => 129.000, 'duration_days' => 30, 'service_id' => $fitness?->id],
            ['name' => 'Quarterly Plus', 'price' => 349.000, 'duration_days' => 90, 'service_id' => $tennis?->id],
            ['name' => 'Annual Elite', 'price' => 1199.000, 'duration_days' => 365, 'has_all_courses' => true, 'service_id' => $padel?->id],
            ['name' => 'Legacy Promo', 'price' => 75.000, 'duration_days' => 30, 'service_id' => $fitness?->id, 'is_archived' => true],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['name' => $data['name']], $data);
        }
    }

    protected function seedUsers(): void
    {
        if (User::where('role', UserRole::Admin)->exists()) {
            return;
        }

        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@bourgo.tn',
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@bourgo.tn',
        ]);
    }

    protected function seedMembers($plans): void
    {
        if (Member::count() > 20) {
            return;
        }

        $statusDistributions = [
            ['status' => 'active', 'state' => 'active', 'verified' => true, 'onboarded' => true, 'count' => 80],
            ['status' => 'active', 'state' => 'pending_verification', 'verified' => false, 'onboarded' => false, 'count' => 15],
            ['status' => 'pending_onboarding', 'state' => 'pending_onboarding', 'verified' => true, 'onboarded' => false, 'count' => 12],
            ['status' => 'pending_additional_verification', 'state' => 'pending_additional_verification', 'verified' => false, 'onboarded' => false, 'count' => 8],
            ['status' => 'inactive', 'state' => 'inactive', 'verified' => true, 'onboarded' => true, 'count' => 5],
        ];

        $maleNames = ['Adam', 'Youssef', 'Karim', 'Mehdi', 'Hicham', 'Bilal', 'Othman', 'Rami', 'Amine', 'Nassim', 'Mohamed', 'Sami', 'Walid', 'Fares', 'Zied', 'Houssem', 'Anis', 'Marouane', 'Skander', 'Aziz'];
        $femaleNames = ['Amira', 'Sara', 'Nadia', 'Rania', 'Siham', 'Lina', 'Meriem', 'Ines', 'Nora', 'Hela', 'Yasmine', 'Selma', 'Dorra', 'Khaoula', 'Fatma', 'Aicha', 'Mouna', 'Leila', 'Salma', 'Asma'];
        $lastNames = ['El Mansouri', 'Bennis', 'Chafik', 'Ait Ali', 'Rachid', 'El Fassi', 'Ziani', 'Amrani', 'Boulahya', 'El Ouardi', 'Berrada', 'Hajar', 'Ben Salem', 'Fassi', 'Cherif', 'Haddad', 'Khelifi', 'Jaziri', 'Toumi', 'Mansouri'];

        $memberIndex = 0;

        foreach ($statusDistributions as $dist) {
            for ($i = 0; $i < $dist['count']; $i++) {
                $isMale = $i % 2 === 0;
                $namePool = $isMale ? $maleNames : $femaleNames;
                $firstName = $namePool[array_rand($namePool)];
                $lastName = $lastNames[array_rand($lastNames)];
                $fullName = $firstName.' '.$lastName;

                $daysAgo = match (true) {
                    $dist['status'] === 'active' && $dist['state'] === 'active' => rand(0, 60),
                    $dist['status'] === 'active' => rand(0, 14),
                    $dist['status'] === 'pending_onboarding' => rand(0, 21),
                    default => rand(0, 30),
                };

                $member = Member::create([
                    'name' => $fullName,
                    'email' => strtolower($firstName.'.'.$lastName.$memberIndex).'@example.com',
                    'phone' => '5000'.str_pad((string) (10000 + $memberIndex), 5, '0', STR_PAD_LEFT),
                    'date_of_birth' => now()->subYears(rand(18, 65))->subDays(rand(0, 364))->toDateString(),
                    'gender' => $isMale ? 'male' : 'female',
                    'emergency_contact' => '9000'.str_pad((string) (10000 + $memberIndex), 5, '0', STR_PAD_LEFT),
                    'status' => $dist['status'],
                    'state' => $dist['state'],
                    'rgpd_consented_at' => now()->subDays($daysAgo),
                    'email_verified_at' => $dist['verified'] ? now()->subDays($daysAgo) : null,
                    'phone_verified_at' => $dist['verified'] ? now()->subDays($daysAgo) : null,
                    'onboarding_completed_at' => $dist['onboarded'] ? now()->subDays($daysAgo) : null,
                    'password' => bcrypt('Test@12345'),
                    'created_at' => now()->subDays($daysAgo),
                    'updated_at' => now()->subDays($daysAgo),
                ]);

                $memberIndex++;
            }
        }

        $this->createFamilyAccounts($memberIndex);
    }

    protected function createFamilyAccounts(int &$memberIndex): void
    {
        $activeParents = Member::where('status', 'active')
            ->where('state', 'active')
            ->whereNull('parent_id')
            ->take(5)
            ->get();

        foreach ($activeParents as $parent) {
            $parent->update(['is_family_account' => true]);

            $childCount = rand(1, 2);
            for ($i = 0; $i < $childCount; $i++) {
                $childName = fake()->firstName().' '.explode(' ', $parent->name)[1];
                Member::create([
                    'name' => $childName,
                    'email' => 'child.'.strtolower(str_replace(' ', '.', $childName)).$memberIndex.'@example.com',
                    'phone' => '5000'.str_pad((string) (10000 + $memberIndex), 5, '0', STR_PAD_LEFT),
                    'parent_id' => $parent->id,
                    'date_of_birth' => now()->subYears(rand(5, 17))->toDateString(),
                    'gender' => $i % 2 === 0 ? 'male' : 'female',
                    'emergency_contact' => $parent->phone,
                    'status' => 'active',
                    'state' => 'active',
                    'rgpd_consented_at' => now(),
                    'password' => bcrypt('Test@12345'),
                    'created_at' => now()->subDays(rand(0, 30)),
                ]);
                $memberIndex++;
            }
        }
    }

    protected function seedSubscriptions($members, $plans): void
    {
        if (Subscription::count() > 5) {
            return;
        }

        foreach ($members as $member) {
            if ($member->status !== 'active') {
                continue;
            }

            $plan = $plans->random();
            $roll = rand(1, 100);

            if ($roll <= 55) {
                $startDaysAgo = rand(10, 90);
                $duration = $plan->duration_days;

                Subscription::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now()->subDays($startDaysAgo)->toDateString(),
                    'ends_at' => now()->addDays($duration - $startDaysAgo)->toDateString(),
                    'payment_method' => rand(0, 2) === 0 ? 'cash' : 'konnect',
                    'amount_paid' => $plan->price,
                    'enrolled_by' => User::where('role', UserRole::Manager)->inRandomOrder()->first()?->id,
                    'created_at' => now()->subDays($startDaysAgo),
                ]);
            } elseif ($roll <= 75) {
                Subscription::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'expired',
                    'starts_at' => now()->subDays($plan->duration_days + rand(5, 30))->toDateString(),
                    'ends_at' => now()->subDays(rand(1, 10))->toDateString(),
                    'payment_method' => rand(0, 2) === 0 ? 'cash' : 'konnect',
                    'amount_paid' => $plan->price,
                    'enrolled_by' => User::where('role', UserRole::Manager)->inRandomOrder()->first()?->id,
                    'created_at' => now()->subDays($plan->duration_days + rand(5, 30)),
                ]);
            } elseif ($roll <= 90) {
                Subscription::create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'suspended',
                    'starts_at' => now()->subDays(rand(20, 60))->toDateString(),
                    'ends_at' => now()->addDays(rand(5, 30))->toDateString(),
                    'suspended_at' => now()->subDays(rand(1, 10)),
                    'payment_method' => 'konnect',
                    'amount_paid' => $plan->price,
                    'enrolled_by' => User::where('role', UserRole::Manager)->inRandomOrder()->first()?->id,
                    'created_at' => now()->subDays(rand(20, 60)),
                ]);
            }
        }

        $expiringMembers = Member::where('status', 'active')
            ->whereDoesntHave('subscriptions', fn ($q) => $q->where('status', 'active'))
            ->take(5)
            ->get();

        foreach ($expiringMembers as $member) {
            $plan = $plans->random();
            Subscription::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now()->subDays($plan->duration_days - rand(1, 6))->toDateString(),
                'ends_at' => now()->addDays(rand(1, 6))->toDateString(),
                'payment_method' => 'konnect',
                'amount_paid' => $plan->price,
                'enrolled_by' => User::where('role', UserRole::Manager)->inRandomOrder()->first()?->id,
                'created_at' => now()->subDays($plan->duration_days - rand(1, 6)),
            ]);
        }
    }

    protected function seedPayments($members): void
    {
        if (Payment::count() > 3) {
            return;
        }

        $gateways = ['konnect', 'cash', 'stripe'];
        $types = ['subscription', 'reservation_deposit'];

        for ($i = 0; $i < 60; $i++) {
            $daysAgo = rand(0, 30);
            $gateway = $gateways[array_rand($gateways)];

            Payment::create([
                'member_id' => $members->random()->id,
                'driver' => $gateway,
                'gateway' => $gateway === 'stripe' ? 'stripe' : null,
                'type' => $types[array_rand($types)],
                'amount' => round(rand(1000, 50000) / 100, 3),
                'status' => 'completed',
                'payment_reference' => 'pay_'.Str::random(12),
                'verified_at' => now()->subDays($daysAgo),
                'created_at' => now()->subDays($daysAgo),
            ]);
        }
    }

    protected function seedRevenueSnapshots(): void
    {
        $existingCount = RevenueSnapshot::count();
        if ($existingCount >= 25) {
            return;
        }

        $baseRevenue = 1200;
        $baseActive = 35;
        $baseExpired = 5;

        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $dailyRevenue = $baseRevenue + rand(-200, 300) + ($i === 0 ? rand(100, 400) : 0);
            $dailyActive = $baseActive + rand(-3, 5) + (30 - $i) / 5;
            $dailyExpired = $baseExpired + rand(-1, 2);
            $dailyNewMembers = rand(1, 6);

            $konnectAmount = round($dailyRevenue * rand(50, 80) / 100, 2);
            $cashAmount = round($dailyRevenue * rand(10, 25) / 100, 2);
            $stripeAmount = round($dailyRevenue - $konnectAmount - $cashAmount, 2);

            $plans = Plan::where('is_archived', false)->get();
            $planMetrics = [];
            foreach ($plans as $plan) {
                $planMetrics[$plan->name] = rand(2, 15);
            }

            RevenueSnapshot::updateOrCreate(
                ['date' => $date->toDateString()],
                [
                    'total_revenue' => $dailyRevenue,
                    'active_subscriptions' => max(0, (int) round($dailyActive)),
                    'expired_subscriptions' => max(0, $dailyExpired),
                    'churn_rate' => round(rand(300, 1500) / 100, 2),
                    'revenue_by_method' => [
                        'konnect' => $konnectAmount,
                        'cash' => $cashAmount,
                        'stripe' => $stripeAmount,
                    ],
                    'plan_metrics' => $planMetrics,
                    'member_metrics' => [
                        'total' => 100 + (30 - $i) * 2 + rand(-3, 5),
                        'active' => 70 + (30 - $i) + rand(-2, 4),
                        'new_today' => $dailyNewMembers,
                        'pending_verification' => rand(5, 12),
                        'pending_onboarding' => rand(3, 8),
                        'family_accounts' => rand(3, 6),
                    ],
                    'event_metrics' => [
                        'upcoming' => rand(1, 3),
                        'in_progress' => rand(0, 1),
                        'completed' => rand(5, 15) + (30 - $i) / 5,
                        'canceled' => rand(0, 2),
                        'total_participants' => rand(10, 40) + (30 - $i),
                    ],
                    'activity_metrics' => [
                        'active_activities' => rand(3, 6),
                        'reservations_today' => rand(3, 15),
                        'revenue_from_reservations' => round(rand(100, 500) + rand(0, 100), 2),
                    ],
                ]
            );

            $baseRevenue += rand(-50, 80);
            $baseActive += rand(-1, 2);
        }
    }

    protected function seedEvents(): void
    {
        if (Event::count() > 2) {
            return;
        }

        $padel = Service::where('slug', 'padel-courts')->first();
        $tennis = Service::where('slug', 'tennis-academy')->first();
        $fitness = Service::where('slug', 'fitness-gym')->first();

        $eventsData = [
            [
                'name' => 'Summer Padel Cup',
                'description' => 'A compact doubles bracket for the summer competitive block.',
                'format' => '2v2',
                'max_participants' => 16,
                'registration_deadline' => now()->addDays(12),
                'start_date' => now()->addDays(16),
                'end_date' => now()->addDays(17),
                'requires_check_in' => true,
                'service_id' => $padel?->id,
            ],
            [
                'name' => 'Autumn Tennis Ladder',
                'description' => 'A progressive tennis ladder with seeded rounds and weekly updates.',
                'format' => '1v1',
                'max_participants' => 12,
                'registration_deadline' => now()->addDays(20),
                'start_date' => now()->addDays(23),
                'end_date' => now()->addDays(30),
                'requires_check_in' => false,
                'service_id' => $tennis?->id,
            ],
            [
                'name' => 'Community Fun Run',
                'description' => 'A non-competitive 5k run for all members.',
                'format' => 'group',
                'max_participants' => 100,
                'registration_deadline' => now()->addDays(5),
                'start_date' => now()->addDays(7),
                'end_date' => now()->addDays(7),
                'requires_check_in' => true,
                'service_id' => $fitness?->id,
            ],
            [
                'name' => 'Spring Championship',
                'description' => 'Completed spring tournament.',
                'format' => '1v1',
                'max_participants' => 8,
                'registration_deadline' => now()->subDays(20),
                'start_date' => now()->subDays(15),
                'end_date' => now()->subDays(10),
                'requires_check_in' => true,
                'service_id' => $tennis?->id,
                'created_at' => now()->subDays(40),
            ],
            [
                'name' => 'Winter Indoor Cup',
                'description' => 'Cancelled winter tournament.',
                'format' => '2v2',
                'max_participants' => 16,
                'registration_deadline' => now()->subDays(10),
                'start_date' => now()->subDays(5),
                'end_date' => now()->subDays(3),
                'requires_check_in' => false,
                'service_id' => $padel?->id,
                'canceled_at' => now()->subDays(8),
                'created_at' => now()->subDays(30),
            ],
        ];

        foreach ($eventsData as $data) {
            Event::updateOrCreate(['name' => $data['name']], $data);
        }

        $this->seedEventParticipants();
    }

    protected function seedEventParticipants(): void
    {
        $summerEvent = Event::where('name', 'Summer Padel Cup')->first();
        $springEvent = Event::where('name', 'Spring Championship')->first();
        $funRun = Event::where('name', 'Community Fun Run')->first();

        if ($summerEvent && $summerEvent->participants()->count() === 0) {
            for ($i = 1; $i <= 8; $i++) {
                $user = User::factory()->create(['role' => UserRole::Member]);
                EventParticipant::create([
                    'event_id' => $summerEvent->id,
                    'user_id' => $user->id,
                    'seed_number' => $i,
                    'has_checked_in' => $i <= 4,
                    'status' => 'approved',
                ]);
            }
        }

        if ($springEvent && $springEvent->participants()->count() === 0) {
            for ($i = 1; $i <= 8; $i++) {
                $user = User::factory()->create(['role' => UserRole::Member]);
                EventParticipant::create([
                    'event_id' => $springEvent->id,
                    'user_id' => $user->id,
                    'seed_number' => $i,
                    'has_checked_in' => true,
                    'status' => 'approved',
                ]);
            }
        }

        if ($funRun && $funRun->participants()->count() === 0) {
            $members = Member::where('status', 'active')->inRandomOrder()->take(12)->get();
            foreach ($members as $member) {
                $user = User::factory()->create(['role' => UserRole::Member]);
                EventParticipant::create([
                    'event_id' => $funRun->id,
                    'user_id' => $user->id,
                    'status' => 'approved',
                ]);
            }
        }
    }

    protected function seedActivitiesAndReservations($service, $members): void
    {
        $activities = Activity::count();
        if ($activities > 0) {
            return;
        }

        $activitiesData = [
            ['title' => 'Tennis Court A', 'base_price' => 25.00, 'capacity' => 4, 'is_active' => true, 'service_id' => Service::where('slug', 'tennis-academy')->first()?->id],
            ['title' => 'Padel Court 1', 'base_price' => 30.00, 'capacity' => 4, 'is_active' => true, 'service_id' => Service::where('slug', 'padel-courts')->first()?->id],
            ['title' => 'Group Fitness Class', 'base_price' => 15.00, 'capacity' => 20, 'is_active' => true, 'service_id' => Service::where('slug', 'fitness-gym')->first()?->id],
            ['title' => 'Spa Session', 'base_price' => 50.00, 'capacity' => 2, 'is_active' => true, 'service_id' => Service::where('slug', 'wellness-center')->first()?->id],
            ['title' => 'Padel Court 2', 'base_price' => 30.00, 'capacity' => 4, 'is_active' => false, 'service_id' => Service::where('slug', 'padel-courts')->first()?->id],
        ];

        foreach ($activitiesData as $data) {
            Activity::create($data);
        }

        $this->seedReservations($members);
    }

    protected function seedReservations($members): void
    {
        if (ApiReservation::count() > 2) {
            return;
        }

        $activeActivities = Activity::where('is_active', true)->get();

        for ($i = 0; $i < 25; $i++) {
            $daysAgo = rand(0, 20);
            $activity = $activeActivities->random();
            $isCancelled = $i >= 20;

            ApiReservation::create([
                'member_id' => $members->random()->id,
                'activity_id' => $activity->id,
                'date' => now()->subDays($daysAgo)->toDateString(),
                'price' => $activity->base_price + rand(-5, 10),
                'status' => $isCancelled ? 'cancelled' : 'confirmed',
                'payment_status' => $isCancelled ? 'refunded' : 'paid',
                'cancelled_at' => $isCancelled ? now()->subDays($daysAgo) : null,
                'created_at' => now()->subDays($daysAgo + rand(0, 3)),
            ]);
        }
    }

    protected function seedOccupancyData(): void
    {
        if (OccupancyHourlyAggregate::count() > 5) {
            return;
        }

        for ($day = 6; $day >= 0; $day--) {
            $date = now()->subDays($day);

            for ($hour = 8; $hour <= 20; $hour++) {
                $isWeekend = in_array($date->dayOfWeek, [0, 6]);
                $baseOccupancy = $isWeekend ? rand(15, 40) : rand(8, 25);
                $peakMultiplier = match (true) {
                    $hour >= 8 && $hour <= 10 => rand(12, 22),
                    $hour >= 17 && $hour <= 20 => rand(18, 30),
                    default => rand(5, 15),
                };

                $entries = max(0, $peakMultiplier + rand(-3, 5));
                $exits = max(0, $peakMultiplier + rand(-5, 3));
                $avgOccupancy = max(0, (int) round(($baseOccupancy + $peakMultiplier) / 2));

                OccupancyHourlyAggregate::create([
                    'date' => $date->toDateString(),
                    'hour' => $hour,
                    'entries_count' => $entries,
                    'exits_count' => $exits,
                    'avg_occupancy' => $avgOccupancy,
                ]);
            }
        }
    }

    protected function seedCourses($service): void
    {
        if (Course::count() > 0) {
            return;
        }

        $courses = [
            ['name' => 'Beginner Tennis', 'description' => 'Introductory tennis for all ages.', 'status' => 'active', 'service_id' => Service::where('slug', 'tennis-academy')->first()?->id],
            ['name' => 'Advanced Fitness', 'description' => 'High-intensity training program.', 'status' => 'active', 'service_id' => Service::where('slug', 'fitness-gym')->first()?->id],
            ['name' => 'Yoga & Flexibility', 'description' => 'Weekly yoga sessions for recovery.', 'status' => 'active', 'service_id' => Service::where('slug', 'wellness-center')->first()?->id],
        ];

        foreach ($courses as $data) {
            Course::create($data);
        }

        $this->seedCourseSessions();
    }

    protected function seedCourseSessions(): void
    {
        $courses = Course::all();
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($courses as $course) {
            $dayOfWeek = array_rand($dayNames);

            CourseSession::create([
                'course_id' => $course->id,
                'day_of_week' => $dayOfWeek,
                'starts_at' => sprintf('%02d:00', rand(8, 18)),
                'duration_minutes' => 60,
                'capacity' => rand(10, 25),
                'is_cancelled' => false,
            ]);

            CourseSession::create([
                'course_id' => $course->id,
                'day_of_week' => ($dayOfWeek + 2) % 6,
                'starts_at' => sprintf('%02d:00', rand(8, 18)),
                'duration_minutes' => rand(2, 3) * 30,
                'capacity' => rand(10, 25),
                'is_cancelled' => false,
            ]);
        }
    }
}
