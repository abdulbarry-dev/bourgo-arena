<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuspendedSubscriptionSeeder extends Seeder
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

        $plans = Plan::query()
            ->where('is_archived', false)
            ->orderBy('duration_days')
            ->get();

        $suspendedMembers = Member::factory()->count(5)->create([
            'status' => 'suspended',
        ]);

        foreach ($suspendedMembers as $index => $member) {
            $plan = $plans->values()->get($index % $plans->count());
            $daysUntilEnd = random_int(10, 25);
            $startsAt = now()->subDays(max(1, (int) $plan->duration_days - $daysUntilEnd))->toDateString();

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

        }
    }
}
