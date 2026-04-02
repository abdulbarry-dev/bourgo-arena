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

class ExpiredSubscriptionSeeder extends Seeder
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

        $exitTerminal = HikvisionTerminal::query()
            ->where('serial_number', 'MAIN-EXIT-001')
            ->first();

        if ($exitTerminal === null) {
            $this->call(HikvisionTerminalSeeder::class);

            $exitTerminal = HikvisionTerminal::query()
                ->where('serial_number', 'MAIN-EXIT-001')
                ->firstOrFail();
        }

        $plans = Plan::query()
            ->where('is_archived', false)
            ->orderBy('duration_days')
            ->get();

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
    }
}
