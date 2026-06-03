<?php

use App\Jobs\SendSubscriptionNotification;
use App\Livewire\Admin\Subscriptions\SubscriptionSuspension;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('manager can suspend an active subscription and queue notifications', function () {
    Carbon::setTestNow('2026-04-01 08:00:00');
    Queue::fake();

    $manager = User::factory()->manager()->create();
    $member = Member::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create();
    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDays(5)->toDateString(),
        'ends_at' => now()->addDays(20)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'suspend')
        ->set('suspensionReason', 'medical')
        ->set('confirmSuspension', true)
        ->call('suspend')
        ->assertHasNoErrors()
        ->assertDispatched('subscription-updated', subscriptionId: $subscription->id);

    $subscription->refresh();

    expect($subscription->status)->toBe('suspended');
    expect($subscription->suspended_at)->not->toBeNull();
    expect($subscription->days_remaining)->toBeGreaterThan(0);

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'suspend',
        'reason' => 'medical',
        'performed_by' => $manager->id,
    ]);

    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $subscription->id
            && $job->notificationType === 'suspended'
            && ($job->metadata['reason'] ?? null) === 'medical',
    );

    Carbon::setTestNow();
});

test('manager can resume a suspended subscription and restore remaining days', function () {
    Carbon::setTestNow('2026-04-01 09:00:00');
    Queue::fake();

    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()
        ->suspendedWithRemaining(9)
        ->create([
            'status' => 'suspended',
            'ends_at' => now()->addDays(1)->toDateString(),
        ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'resume')
        ->call('resume')
        ->assertHasNoErrors();

    $subscription->refresh();

    expect($subscription->status)->toBe('active');
    expect($subscription->resumed_at)->not->toBeNull();
    expect($subscription->suspended_at)->toBeNull();
    expect($subscription->days_remaining)->toBeNull();
    expect($subscription->ends_at->toDateString())->toBe('2026-04-10');

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'resume',
        'performed_by' => $manager->id,
    ]);

    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $subscription->id
            && $job->notificationType === 'resumed',
    );

    Carbon::setTestNow();
});

test('suspension requires explicit confirmation checkbox', function () {
    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'suspend')
        ->set('suspensionReason', 'medical')
        ->set('confirmSuspension', false)
        ->call('suspend')
        ->assertHasErrors(['confirmSuspension']);
});

test('member selection loads active subscription context for suspension actions', function () {
    $manager = User::factory()->manager()->create();
    $member = Member::factory()->create(['status' => 'active']);

    $activeSubscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(20)->toDateString(),
    ]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'suspended',
        'days_remaining' => 5,
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->call('setSubscriptionFromMember', $member->id)
        ->assertSet('subscriptionId', $activeSubscription->id);
});

test('subscription suspension page no longer exposes transfer controls', function () {
    $manager = User::factory()->manager()->create();
    $subscription = Subscription::factory()->create([
        'status' => 'active',
        'ends_at' => now()->addDays(10)->toDateString(),
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->assertDontSee('Transfer Subscription')
        ->assertDontSee('Transfer To Member')
        ->assertDontSee('transfer approval');
});
