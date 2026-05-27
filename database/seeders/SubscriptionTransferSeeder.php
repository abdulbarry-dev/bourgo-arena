<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SubscriptionTransferSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! Schema::hasTable('subscription_audit_logs')) {
            return;
        }

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

        $transferPlan = Plan::query()
            ->where('is_archived', false)
            ->orderBy('duration_days')
            ->first();

        if ($transferPlan === null) {
            return;
        }

        $sourceMember = Member::factory()->active()->create();
        $targetMember = Member::factory()->active()->create();

        $daysUntilEnd = 12;
        $startsAt = now()->subDays(max(1, (int) $transferPlan->duration_days - $daysUntilEnd))->toDateString();

        $sourceSubscription = Subscription::factory()->create([
            'member_id' => $sourceMember->id,
            'plan_id' => $transferPlan->id,
            'status' => 'active',
            'starts_at' => $startsAt,
            'ends_at' => Subscription::calculateEndDate($startsAt, (int) $transferPlan->duration_days),
            'payment_method' => 'konnect',
            'payment_reference' => 'TXN-DEMO-TRANSFER',
            'amount_paid' => $transferPlan->price,
            'enrolled_by' => $manager->id,
        ]);

        $sourceSubscription->transfer($targetMember->id, $manager->id);

    }
}
