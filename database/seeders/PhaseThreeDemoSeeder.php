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
use Illuminate\Support\Facades\Schema;

class PhaseThreeDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Plan::query()->where('is_archived', false)->doesntExist()) {
            $this->call(PlanCatalogSeeder::class);
        }

        $manager = User::query()->where('email', 'manager@bourgoarena.com')->first()
            ?? User::factory()->manager()->create([
                'name' => 'Seed Manager',
                'email' => 'seed.manager@bourgoarena.com',
            ]);

        $plans = Plan::query()
            ->where('is_archived', false)
            ->orderBy('duration_days')
            ->get();

        $entryTerminal = HikvisionTerminal::query()->updateOrCreate(
            ['serial_number' => 'PH3-ENTRY-001'],
            [
                'name' => 'Main Entry Terminal',
                'ip_address' => '10.10.0.21',
                'location' => 'Main Entrance',
                'terminal_type' => 'entry',
                'api_token' => hash('sha256', 'phase3-entry-terminal'),
                'status' => 'online',
                'last_seen_at' => now(),
            ],
        );

        $exitTerminal = HikvisionTerminal::query()->updateOrCreate(
            ['serial_number' => 'PH3-EXIT-001'],
            [
                'name' => 'Main Exit Terminal',
                'ip_address' => '10.10.0.22',
                'location' => 'Exit Gate',
                'terminal_type' => 'exit',
                'api_token' => hash('sha256', 'phase3-exit-terminal'),
                'status' => 'online',
                'last_seen_at' => now(),
            ],
        );

        Member::factory()->count(8)->create([
            'status' => 'pending',
        ]);

        $activeMembers = Member::factory()->count(14)->active()->create();
        foreach ($activeMembers as $index => $member) {
            $plan = $plans->values()->get($index % $plans->count());
            $daysUntilEnd = $index < 5 ? random_int(1, 6) : random_int(14, 45);
            $startsAt = now()->subDays(max(1, (int) $plan->duration_days - $daysUntilEnd))->toDateString();

            $card = NfcCard::factory()->for($member)->create([
                'status' => 'active',
                'assigned_by' => $manager->id,
            ]);

            Subscription::factory()->create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => Subscription::calculateEndDate($startsAt, (int) $plan->duration_days),
                'payment_method' => fake()->randomElement(['cash', 'konnect', 'paymee']),
                'payment_reference' => fake()->optional()->bothify('TXN-####-??'),
                'amount_paid' => $plan->price,
                'enrolled_by' => $manager->id,
            ]);

            CheckInEvent::factory()->authorized()->count(2)->create([
                'member_id' => $member->id,
                'card_uid' => $card->uid,
                'terminal_id' => $entryTerminal->id,
            ]);
        }

        $suspendedMembers = Member::factory()->count(5)->create([
            'status' => 'suspended',
        ]);
        foreach ($suspendedMembers as $index => $member) {
            $plan = $plans->values()->get($index % $plans->count());
            $daysUntilEnd = random_int(10, 25);
            $startsAt = now()->subDays(max(1, (int) $plan->duration_days - $daysUntilEnd))->toDateString();

            $card = NfcCard::factory()->suspended()->for($member)->create([
                'assigned_by' => $manager->id,
            ]);

            Subscription::factory()
                ->suspendedWithRemaining($daysUntilEnd)
                ->create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'suspended',
                    'starts_at' => $startsAt,
                    'ends_at' => Subscription::calculateEndDate($startsAt, (int) $plan->duration_days),
                    'payment_method' => 'cash',
                    'payment_reference' => null,
                    'amount_paid' => $plan->price,
                    'enrolled_by' => $manager->id,
                ]);

            CheckInEvent::factory()->denied('suspended_card')->create([
                'member_id' => $member->id,
                'card_uid' => $card->uid,
                'terminal_id' => $entryTerminal->id,
            ]);
        }

        $expiredMembers = Member::factory()->count(5)->create([
            'status' => 'expired',
        ]);
        foreach ($expiredMembers as $index => $member) {
            $plan = $plans->values()->get($index % $plans->count());
            $endedDaysAgo = random_int(2, 40);
            $startsAt = now()->subDays((int) $plan->duration_days + $endedDaysAgo)->toDateString();

            $card = NfcCard::factory()->for($member)->create([
                'status' => 'active',
                'assigned_by' => $manager->id,
            ]);

            Subscription::factory()
                ->expired()
                ->create([
                    'member_id' => $member->id,
                    'plan_id' => $plan->id,
                    'status' => 'expired',
                    'starts_at' => $startsAt,
                    'ends_at' => Subscription::calculateEndDate($startsAt, (int) $plan->duration_days),
                    'payment_method' => 'cash',
                    'payment_reference' => null,
                    'amount_paid' => $plan->price,
                    'enrolled_by' => $manager->id,
                ]);

            CheckInEvent::factory()->denied('expired_subscription')->create([
                'member_id' => $member->id,
                'card_uid' => $card->uid,
                'terminal_id' => $exitTerminal->id,
            ]);
        }

        if (Schema::hasTable('subscription_audit_logs')) {
            $sourceMember = Member::factory()->active()->create();
            $targetMember = Member::factory()->active()->create();

            $sourceCard = NfcCard::factory()->for($sourceMember)->create([
                'status' => 'active',
                'assigned_by' => $manager->id,
            ]);
            $targetCard = NfcCard::factory()->for($targetMember)->create([
                'status' => 'active',
                'assigned_by' => $manager->id,
            ]);

            $transferPlan = $plans->first();
            $daysUntilEnd = 12;
            $startsAt = now()->subDays(max(1, (int) $transferPlan->duration_days - $daysUntilEnd))->toDateString();

            $sourceSubscription = Subscription::factory()->create([
                'member_id' => $sourceMember->id,
                'plan_id' => $transferPlan->id,
                'status' => 'active',
                'starts_at' => $startsAt,
                'ends_at' => Subscription::calculateEndDate($startsAt, (int) $transferPlan->duration_days),
                'payment_method' => 'konnect',
                'payment_reference' => 'TXN-PHASE3-DEMO',
                'amount_paid' => $transferPlan->price,
                'enrolled_by' => $manager->id,
            ]);

            $sourceSubscription->transfer($targetMember->id, $manager->id);

            CheckInEvent::factory()->denied('invalid_card')->create([
                'member_id' => $sourceMember->id,
                'card_uid' => $sourceCard->uid,
                'terminal_id' => $entryTerminal->id,
            ]);

            CheckInEvent::factory()->authorized()->create([
                'member_id' => $targetMember->id,
                'card_uid' => $targetCard->uid,
                'terminal_id' => $entryTerminal->id,
                'checked_in_at' => now()->subMinute(),
                'created_at' => now()->subMinute(),
            ]);

            CheckInEvent::factory()->suspicious()->create([
                'member_id' => $targetMember->id,
                'card_uid' => $targetCard->uid,
                'terminal_id' => $entryTerminal->id,
            ]);

            CheckInEvent::factory()->authorized()->create([
                'member_id' => $targetMember->id,
                'card_uid' => $targetCard->uid,
                'terminal_id' => $exitTerminal->id,
            ]);
        }
    }
}
