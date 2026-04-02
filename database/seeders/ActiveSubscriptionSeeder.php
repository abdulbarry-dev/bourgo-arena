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

class ActiveSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Plan::query()->where('is_archived', false)->doesntExist()) {
            $this->call(PlanCatalogSeeder::class);
        }

        $manager = User::query()
            ->whereIn('email', ['manager@bourgoarena.com', 'seed.manager@bourgoarena.com'])
            ->orderBy('id')
            ->first()
            ?? User::factory()->manager()->create([
                'name' => 'Seed Manager',
                'email' => 'seed.manager@bourgoarena.com',
            ]);

        $entryTerminal = HikvisionTerminal::query()
            ->where('serial_number', 'MAIN-ENTRY-001')
            ->first();

        if ($entryTerminal === null) {
            $this->call(HikvisionTerminalSeeder::class);

            $entryTerminal = HikvisionTerminal::query()
                ->where('serial_number', 'MAIN-ENTRY-001')
                ->firstOrFail();
        }

        $plans = Plan::query()
            ->where('is_archived', false)
            ->orderBy('duration_days')
            ->get();

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
    }
}
