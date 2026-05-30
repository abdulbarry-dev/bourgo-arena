<?php

namespace Database\Seeders\Dashboard\Catalog;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\SubscriptionAuditLog;
use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;

class SubscriptionAuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $manager = User::query()->where('role', UserRole::Manager)->first();

        if ($manager === null) {
            return;
        }

        $suspendedSubscription = Subscription::query()->where('payment_reference', 'SUB-2026-0003')->first();
        $transferredSubscription = Subscription::query()->where('payment_reference', 'SUB-2026-0005')->first();
        $activeSubscription = Subscription::query()->where('payment_reference', 'SUB-2026-0001')->first();

        if ($suspendedSubscription !== null) {
            SubscriptionAuditLog::query()->updateOrCreate(
                ['subscription_id' => $suspendedSubscription->id, 'action' => 'suspend'],
                [
                    'reason' => 'medical',
                    'from_member_id' => $suspendedSubscription->member_id,
                    'to_member_id' => null,
                    'performed_by' => $manager->id,
                    'performed_at' => now()->subDays(5),
                    'metadata' => ['source' => 'seed'],
                ],
            );
        }

        if ($transferredSubscription !== null) {
            $targetMember = Member::query()->where('email', 'sara.berrada@example.com')->first();

            SubscriptionAuditLog::query()->updateOrCreate(
                ['subscription_id' => $transferredSubscription->id, 'action' => 'transfer'],
                [
                    'reason' => 'family_transfer',
                    'from_member_id' => $transferredSubscription->member_id,
                    'to_member_id' => $targetMember?->id,
                    'performed_by' => $manager->id,
                    'performed_at' => now()->subDays(2),
                    'metadata' => ['source' => 'seed'],
                ],
            );
        }

        if ($activeSubscription !== null) {
            SubscriptionAuditLog::query()->updateOrCreate(
                ['subscription_id' => $activeSubscription->id, 'action' => 'resume'],
                [
                    'reason' => 'plan_renewed',
                    'from_member_id' => $activeSubscription->member_id,
                    'to_member_id' => null,
                    'performed_by' => $manager->id,
                    'performed_at' => now()->subDay(),
                    'metadata' => ['source' => 'seed'],
                ],
            );
        }
    }
}
