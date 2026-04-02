<?php

use App\Models\CheckInEvent;
use App\Models\HikvisionTerminal;
use App\Models\Member;
use App\Models\NfcCard;
use App\Models\Plan;
use App\Models\Subscription;
use Database\Seeders\SubscriptionLifecycleSeeder;

test('subscription lifecycle seeder creates actionable dashboard data', function () {
    $this->seed(SubscriptionLifecycleSeeder::class);

    expect(Plan::query()->where('is_archived', false)->count())->toBeGreaterThanOrEqual(4)
        ->and(Member::query()->count())->toBeGreaterThanOrEqual(30)
        ->and(NfcCard::query()->count())->toBeGreaterThan(0)
        ->and(HikvisionTerminal::query()->count())->toBeGreaterThanOrEqual(2)
        ->and(CheckInEvent::query()->count())->toBeGreaterThan(0)
        ->and(Subscription::query()->where('status', 'active')->count())->toBeGreaterThan(0)
        ->and(Subscription::query()->where('status', 'suspended')->count())->toBeGreaterThan(0)
        ->and(Subscription::query()->where('status', 'expired')->count())->toBeGreaterThan(0)
        ->and(Subscription::query()->where('status', 'transferred')->count())->toBeGreaterThan(0)
        ->and(Subscription::query()->expiring()->count())->toBeGreaterThan(0);
});
