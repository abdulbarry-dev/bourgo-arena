<?php

namespace Database\Seeders;

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates comprehensive member data for testing:
 * - Active members with current subscriptions
 * - Pending members (not yet verified)
 * - Members with expiring subscriptions (within 7 days)
 * - Suspended members and suspended cards
 * - Expired members
 * - Members with family accounts
 */
class ComprehensiveMembersSeeder extends Seeder
{
    // Online avatar URLs from various sources
    private array $maleAvatars = [
        'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517070213202-1cf4bc62ae7b?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=400&h=400&fit=crop',
    ];

    private array $femaleAvatars = [
        'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1517849845537-1d51a20414de?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1542838132-92c53300491e?w=400&h=400&fit=crop',
        'https://images.unsplash.com/photo-1539571696357-5a69c006ae4d?w=400&h=400&fit=crop',
    ];

    public function run(): void
    {
        $manager = User::query()
            ->whereIn('email', ['manager@bourgoarena.com', 'seed.manager@bourgoarena.com'])
            ->orderBy('id')
            ->first()
            ?? User::factory()->manager()->create([
                'name' => 'Seed Manager',
                'email' => 'seed.manager@bourgoarena.com',
            ]);

        $admin = User::query()
            ->whereIn('email', ['admin@bourgoarena.com', 'seed.admin@bourgoarena.com'])
            ->orderBy('id')
            ->first()
            ?? User::factory()->admin()->create([
                'name' => 'Seed Admin',
                'email' => 'seed.admin@bourgoarena.com',
            ]);

        $entryTerminal = HikvisionTerminal::query()
            ->where('serial_number', 'MAIN-ENTRY-001')
            ->first();

        $plans = Plan::query()->where('is_archived', false)->get();

        // =====================================================================
        // 1. ACTIVE MEMBERS WITH CURRENT SUBSCRIPTIONS (20 members)
        // =====================================================================
        $this->createActiveMembersWithSubscriptions($manager, $entryTerminal, $plans);

        // =====================================================================
        // 2. MEMBERS WITH EXPIRING SUBSCRIPTIONS (5 members - expiring within 7 days)
        // =====================================================================
        $this->createMembersWithExpiringSubscriptions($manager, $entryTerminal, $plans);

        // =====================================================================
        // 3. PENDING VERIFICATION MEMBERS (5 members)
        // =====================================================================
        $this->createPendingMembers();

        // =====================================================================
        // 4. SUSPENDED MEMBERS (3 members with suspended subscriptions)
        // =====================================================================
        $this->createSuspendedMembers($manager, $plans);

        // =====================================================================
        // 5. EXPIRED MEMBERS (3 members with expired subscriptions)
        // =====================================================================
        $this->createExpiredMembers($manager, $plans);

        // =====================================================================
        // 6. MEMBERS WITH DIFFERENT NFC CARD STATUSES (2 with lost/suspended cards)
        // =====================================================================
        $this->createMembersWithCardIssues($manager, $entryTerminal, $plans);
    }

    private function createActiveMembersWithSubscriptions(
        User $manager,
        HikvisionTerminal $entryTerminal,
        $plans
    ): void {
        $activeMembers = Member::factory()
            ->count(20)
            ->active()
            ->create()
            ->each(function (Member $member, int $index) use ($manager, $entryTerminal, $plans) {
                // Assign random avatar
                $avatarUrl = $member->gender === 'female'
                    ? fake()->randomElement($this->femaleAvatars)
                    : fake()->randomElement($this->maleAvatars);

                $member->update(['avatar' => $avatarUrl]);

                // Create NFC Card
                $card = NfcCard::factory()
                    ->for($member)
                    ->create([
                        'status' => 'active',
                        'assigned_by' => $manager->id,
                        'assigned_at' => now()->subDays(random_int(1, 120)),
                    ]);

                // Select a plan
                $plan = $plans->values()->get($index % $plans->count());

                // Vary subscription start dates
                $daysUntilEnd = $index < 8 ? random_int(15, 45) : random_int(45, 200);
                $startsAt = now()
                    ->subDays((int) $plan->duration_days - $daysUntilEnd)
                    ->toDateString();

                // Create subscription
                Subscription::factory()
                    ->create([
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'ends_at' => Subscription::calculateEndDate(
                            $startsAt,
                            (int) $plan->duration_days
                        ),
                        'payment_method' => fake()->randomElement(['cash', 'konnect']),
                        'payment_reference' => fake()->optional(0.7)->bothify('TXN-####-??'),
                        'amount_paid' => $plan->price,
                        'enrolled_by' => $manager->id,
                    ]);

                // Create check-in events for active members
                CheckInEvent::factory()
                    ->authorized()
                    ->count(random_int(2, 8))
                    ->create([
                        'member_id' => $member->id,
                        'card_uid' => $card->uid,
                        'terminal_id' => $entryTerminal->id,
                        'checked_in_at' => fake()->dateTimeBetween('-30 days', 'now'),
                    ]);
            });
    }

    private function createMembersWithExpiringSubscriptions(
        User $manager,
        HikvisionTerminal $entryTerminal,
        $plans
    ): void {
        $expiringMembers = Member::factory()
            ->count(5)
            ->active()
            ->create()
            ->each(function (Member $member, int $index) use ($manager, $entryTerminal, $plans) {
                $avatarUrl = $member->gender === 'female'
                    ? fake()->randomElement($this->femaleAvatars)
                    : fake()->randomElement($this->maleAvatars);

                $member->update(['avatar' => $avatarUrl]);

                $card = NfcCard::factory()
                    ->for($member)
                    ->create([
                        'status' => 'active',
                        'assigned_by' => $manager->id,
                    ]);

                $plan = $plans->values()->get($index % $plans->count());

                // Expiring within 1-7 days
                $daysUntilEnd = random_int(1, 7);
                $startsAt = now()
                    ->subDays((int) $plan->duration_days - $daysUntilEnd)
                    ->toDateString();

                Subscription::factory()
                    ->create([
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'ends_at' => Subscription::calculateEndDate(
                            $startsAt,
                            (int) $plan->duration_days
                        ),
                        'payment_method' => fake()->randomElement(['cash', 'konnect']),
                        'enrolled_by' => $manager->id,
                    ]);

                // Recent check-ins
                CheckInEvent::factory()
                    ->authorized()
                    ->count(2)
                    ->create([
                        'member_id' => $member->id,
                        'card_uid' => $card->uid,
                        'terminal_id' => $entryTerminal->id,
                        'checked_in_at' => fake()->dateTimeBetween('-7 days', 'now'),
                    ]);
            });
    }

    private function createPendingMembers(): void
    {
        Member::factory()
            ->count(5)
            ->create([
                'status' => 'pending',
                'state' => 'pending_verification',
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'onboarding_completed_at' => null,
            ])
            ->each(function (Member $member) {
                $avatarUrl = $member->gender === 'female'
                    ? fake()->randomElement($this->femaleAvatars)
                    : fake()->randomElement($this->maleAvatars);

                $member->update(['avatar' => $avatarUrl]);
            });
    }

    private function createSuspendedMembers(User $manager, $plans): void
    {
        $suspendedMembers = Member::factory()
            ->count(3)
            ->active()
            ->create()
            ->each(function (Member $member, int $index) use ($manager, $plans) {
                $avatarUrl = $member->gender === 'female'
                    ? fake()->randomElement($this->femaleAvatars)
                    : fake()->randomElement($this->maleAvatars);

                $member->update(['avatar' => $avatarUrl]);

                // Create suspended NFC card
                NfcCard::factory()
                    ->for($member)
                    ->create([
                        'status' => 'suspended',
                        'assigned_by' => $manager->id,
                    ]);

                $plan = $plans->values()->get($index % $plans->count());

                // Create suspended subscription
                Subscription::factory()
                    ->create([
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'status' => 'suspended',
                        'starts_at' => now()->subMonths(3)->toDateString(),
                        'ends_at' => now()->addMonths(2)->toDateString(),
                        'suspended_at' => now()->subDays(random_int(1, 15)),
                        'days_remaining' => random_int(10, 45),
                        'payment_method' => 'cash',
                        'enrolled_by' => $manager->id,
                    ]);
            });
    }

    private function createExpiredMembers(User $manager, $plans): void
    {
        Member::factory()
            ->count(3)
            ->active()
            ->create()
            ->each(function (Member $member, int $index) use ($manager, $plans) {
                $avatarUrl = $member->gender === 'female'
                    ? fake()->randomElement($this->femaleAvatars)
                    : fake()->randomElement($this->maleAvatars);

                $member->update(['avatar' => $avatarUrl]);

                NfcCard::factory()
                    ->for($member)
                    ->create([
                        'status' => 'active',
                        'assigned_by' => $manager->id,
                    ]);

                $plan = $plans->values()->get($index % $plans->count());

                // Expired subscription (ended 10-30 days ago)
                $endsAt = now()->subDays(random_int(10, 30));

                Subscription::factory()
                    ->create([
                        'member_id' => $member->id,
                        'plan_id' => $plan->id,
                        'status' => 'expired',
                        'starts_at' => $endsAt->clone()->subDays((int) $plan->duration_days)
                            ->toDateString(),
                        'ends_at' => $endsAt->toDateString(),
                        'payment_method' => fake()->randomElement(['cash', 'konnect']),
                        'enrolled_by' => $manager->id,
                    ]);
            });
    }

    private function createMembersWithCardIssues(
        User $manager,
        HikvisionTerminal $entryTerminal,
        $plans
    ): void {
        // Member with lost card
        $lostCardMember = Member::factory()->active()->create([
            'avatar' => fake()->randomElement($this->maleAvatars),
        ]);

        NfcCard::factory()
            ->for($lostCardMember)
            ->create([
                'status' => 'lost',
                'assigned_by' => $manager->id,
            ]);

        $plan = $plans->first();
        Subscription::factory()
            ->create([
                'member_id' => $lostCardMember->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now()->subMonths(1)->toDateString(),
                'ends_at' => now()->addMonths(5)->toDateString(),
                'enrolled_by' => $manager->id,
            ]);

        // Try to check in with lost card (denied)
        CheckInEvent::factory()
            ->denied('invalid_card')
            ->create([
                'member_id' => $lostCardMember->id,
                'card_uid' => $lostCardMember->nfcCard->uid,
                'terminal_id' => $entryTerminal->id,
                'checked_in_at' => now()->subHours(2),
            ]);
    }
}
