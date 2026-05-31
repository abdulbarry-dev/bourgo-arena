<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $planIds = Plan::query()->whereIn('name', [
            'Starter Monthly',
            'Performance Monthly',
            'Quarterly Plus',
            'Annual Elite',
            'Legacy Promo',
        ])->get()->keyBy('name');

        $memberIds = Member::query()->whereIn('email', [
            'amira.elmansouri@example.com',
            'othman.bennis@example.com',
            'lina.chafik@example.com',
            'karim.aitali@example.com',
            'nadia.rachid@example.com',
            'yassine.elfassi@example.com',
            'siham.ziani@example.com',
            'mehdi.amrani@example.com',
        ])->get()->keyBy('email');

        $manager = User::query()->where('role', UserRole::Manager)->first();

        if ($manager === null) {
            return;
        }

        $subscriptions = [
            ['email' => 'amira.elmansouri@example.com', 'plan' => 'Starter Monthly', 'status' => 'active', 'starts_at' => now()->subDays(24)->toDateString(), 'ends_at' => now()->addDays(6)->toDateString(), 'payment_method' => 'konnect', 'payment_reference' => 'SUB-2026-0001', 'amount_paid' => 89.000],
            ['email' => 'othman.bennis@example.com', 'plan' => 'Performance Monthly', 'status' => 'active', 'starts_at' => now()->subDays(12)->toDateString(), 'ends_at' => now()->addDays(18)->toDateString(), 'payment_method' => 'cash', 'payment_reference' => 'SUB-2026-0002', 'amount_paid' => 129.000],
            ['email' => 'lina.chafik@example.com', 'plan' => 'Quarterly Plus', 'status' => 'suspended', 'starts_at' => now()->subDays(40)->toDateString(), 'ends_at' => now()->addDays(42)->toDateString(), 'suspended_at' => now()->subDays(5), 'days_remaining' => 42, 'payment_method' => 'konnect', 'payment_reference' => 'SUB-2026-0003', 'amount_paid' => 349.000],
            ['email' => 'karim.aitali@example.com', 'plan' => 'Legacy Promo', 'status' => 'expired', 'starts_at' => now()->subDays(75)->toDateString(), 'ends_at' => now()->subDays(15)->toDateString(), 'payment_method' => 'cash', 'payment_reference' => 'SUB-2026-0004', 'amount_paid' => 75.000],
            ['email' => 'nadia.rachid@example.com', 'plan' => 'Annual Elite', 'status' => 'transferred', 'starts_at' => now()->subDays(90)->toDateString(), 'ends_at' => now()->addDays(275)->toDateString(), 'payment_method' => 'konnect', 'payment_reference' => 'SUB-2026-0005', 'amount_paid' => 1199.000],
            ['email' => 'yassine.elfassi@example.com', 'plan' => 'Performance Monthly', 'status' => 'active', 'starts_at' => now()->subDays(8)->toDateString(), 'ends_at' => now()->addDays(22)->toDateString(), 'payment_method' => 'cash', 'payment_reference' => 'SUB-2026-0006', 'amount_paid' => 129.000],
            ['email' => 'siham.ziani@example.com', 'plan' => 'Starter Monthly', 'status' => 'active', 'starts_at' => now()->subDays(3)->toDateString(), 'ends_at' => now()->addDays(27)->toDateString(), 'payment_method' => 'konnect', 'payment_reference' => 'SUB-2026-0007', 'amount_paid' => 89.000],
            ['email' => 'mehdi.amrani@example.com', 'plan' => 'Annual Elite', 'status' => 'active', 'starts_at' => now()->subDays(32)->toDateString(), 'ends_at' => now()->addMonths(10)->toDateString(), 'payment_method' => 'konnect', 'payment_reference' => 'SUB-2026-0008', 'amount_paid' => 1199.000],
        ];

        foreach ($subscriptions as $subscriptionData) {
            $member = $memberIds[$subscriptionData['email']] ?? null;
            $plan = $planIds[$subscriptionData['plan']] ?? null;

            if ($member === null || $plan === null) {
                continue;
            }

            $payload = [
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'status' => $subscriptionData['status'],
                'starts_at' => $subscriptionData['starts_at'],
                'ends_at' => $subscriptionData['ends_at'],
                'suspended_at' => $subscriptionData['suspended_at'] ?? null,
                'days_remaining' => $subscriptionData['days_remaining'] ?? null,
                'resumed_at' => $subscriptionData['resumed_at'] ?? null,
                'payment_method' => $subscriptionData['payment_method'],
                'payment_reference' => $subscriptionData['payment_reference'],
                'amount_paid' => $subscriptionData['amount_paid'],
                'receipt_path' => null,
                'enrolled_by' => $manager->id,
            ];

            Subscription::query()->updateOrCreate(
                ['payment_reference' => $subscriptionData['payment_reference']],
                $payload,
            );
        }
    }
}
