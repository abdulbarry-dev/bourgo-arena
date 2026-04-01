<?php

namespace Tests\Unit;

use App\Models\Member;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

it('active scope returns only active subscriptions with future end dates', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $activeFuture = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-04',
    ]);

    $activeToday = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-01',
    ]);

    $suspendedFuture = Subscription::factory()->create([
        'status' => 'suspended',
        'ends_at' => '2026-04-05',
    ]);

    $results = Subscription::query()->active()->pluck('id');

    expect($results)
        ->toContain($activeFuture->id)
        ->not->toContain($activeToday->id)
        ->not->toContain($suspendedFuture->id);

    Carbon::setTestNow();
});

it('expiring scope includes active subscriptions ending within seven days only', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $inThreeDays = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-04',
    ]);

    $inSevenDays = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-08',
    ]);

    $inTenDays = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-11',
    ]);

    $today = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-01',
    ]);

    $suspended = Subscription::factory()->create([
        'status' => 'suspended',
        'ends_at' => '2026-04-03',
    ]);

    $results = Subscription::query()->expiring()->pluck('id');

    expect($results)
        ->toContain($inThreeDays->id)
        ->toContain($inSevenDays->id)
        ->not->toContain($inTenDays->id)
        ->not->toContain($today->id)
        ->not->toContain($suspended->id);

    Carbon::setTestNow();
});

it('scope sql contains expected status and date filters', function () {
    $activeSql = strtolower(Subscription::query()->active()->toSql());
    $expiringSql = strtolower(Subscription::query()->expiring()->toSql());

    expect($activeSql)->toContain('status')->toContain('ends_at');
    expect($expiringSql)->toContain('status')->toContain('ends_at');
});

it('calculates end dates with safe day arithmetic', function () {
    expect(Subscription::calculateEndDate('2026-04-01', 30))->toBe('2026-05-01');
    expect(Subscription::calculateEndDate('2026-04-01', 0))->toBe('2026-04-01');
    expect(Subscription::calculateEndDate('2026-04-01', -3))->toBe('2026-04-01');
});

it('computes days remaining without negative values', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $future = Subscription::factory()->create(['ends_at' => '2026-04-06']);
    $today = Subscription::factory()->create(['ends_at' => '2026-04-01']);
    $past = Subscription::factory()->create(['ends_at' => '2026-03-30']);

    expect($future->daysRemaining())->toBe(5);
    expect($today->daysRemaining())->toBe(0);
    expect($past->daysRemaining())->toBe(0);

    Carbon::setTestNow();
});

it('suspend freezes remaining days and writes an audit log', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => '2026-04-11',
    ]);

    $subscription->suspend('medical', $manager->id);
    $subscription->refresh();

    expect($subscription->status)->toBe('suspended');
    expect($subscription->days_remaining)->toBe(10);
    expect($subscription->suspended_at)->not->toBeNull();

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'suspend',
        'reason' => 'medical',
        'from_member_id' => $subscription->member_id,
        'performed_by' => $manager->id,
    ]);

    Carbon::setTestNow();
});

it('resume restores subscription and writes an audit log', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()->suspendedWithRemaining(8)->create([
        'status' => 'suspended',
        'ends_at' => '2026-04-30',
    ]);

    $subscription->resume($manager->id);
    $subscription->refresh();

    expect($subscription->status)->toBe('active');
    expect($subscription->ends_at->toDateString())->toBe('2026-04-09');
    expect($subscription->days_remaining)->toBeNull();
    expect($subscription->suspended_at)->toBeNull();
    expect($subscription->resumed_at)->not->toBeNull();

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'resume',
        'from_member_id' => $subscription->member_id,
        'performed_by' => $manager->id,
    ]);

    Carbon::setTestNow();
});

it('transfer creates a new active subscription, updates source status, and logs audit', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $admin = User::factory()->admin()->create();
    $sourceMember = Member::factory()->active()->create();
    $targetMember = Member::factory()->active()->create();

    $source = Subscription::factory()->create([
        'member_id' => $sourceMember->id,
        'status' => 'active',
        'ends_at' => '2026-04-13',
        'amount_paid' => 120.000,
    ]);

    $newSubscription = $source->transfer($targetMember->id, $admin->id);
    $source->refresh();

    expect($source->status)->toBe('transferred');
    expect($source->member_id)->toBe($sourceMember->id);
    expect($source->days_remaining)->toBe(12);
    expect($source->ends_at->toDateString())->toBe('2026-04-01');

    expect($newSubscription->member_id)->toBe($targetMember->id);
    expect($newSubscription->status)->toBe('active');
    expect($newSubscription->starts_at->toDateString())->toBe('2026-04-01');
    expect($newSubscription->ends_at->toDateString())->toBe('2026-04-13');

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $source->id,
        'action' => 'transfer',
        'from_member_id' => $sourceMember->id,
        'to_member_id' => $targetMember->id,
        'performed_by' => $admin->id,
    ]);

    Carbon::setTestNow();
});

it('transfer for suspended subscriptions uses frozen remaining days', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $admin = User::factory()->admin()->create();
    $targetMember = Member::factory()->active()->create();
    $source = Subscription::factory()->suspendedWithRemaining(6)->create([
        'status' => 'suspended',
        'ends_at' => '2026-05-01',
    ]);

    $newSubscription = $source->transfer($targetMember->id, $admin->id);

    expect($newSubscription->ends_at->toDateString())->toBe('2026-04-07');

    Carbon::setTestNow();
});

it('transfer validates target member and source state', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');

    $admin = User::factory()->admin()->create();
    $targetMember = Member::factory()->active()->create();
    $active = Subscription::factory()->create(['status' => 'active', 'ends_at' => '2026-04-20']);
    $expired = Subscription::factory()->expired()->create();

    expect(fn () => $active->transfer($active->member_id, $admin->id))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $active->transfer(999999, $admin->id))
        ->toThrow(InvalidArgumentException::class);

    expect(fn () => $expired->transfer($targetMember->id, $admin->id))
        ->toThrow(InvalidArgumentException::class);

    Carbon::setTestNow();
});
