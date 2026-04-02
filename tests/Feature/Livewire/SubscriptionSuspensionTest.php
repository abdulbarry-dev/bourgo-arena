<?php

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SyncTerminalWhitelist;
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
    Queue::assertPushed(
        SyncTerminalWhitelist::class,
        fn (SyncTerminalWhitelist $job): bool => $job->memberId === $member->id
            && $job->subscriptionId === $subscription->id,
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
    Queue::assertPushed(
        SyncTerminalWhitelist::class,
        fn (SyncTerminalWhitelist $job): bool => $job->memberId === $subscription->member_id
            && $job->subscriptionId === $subscription->id,
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

test('admin can transfer subscription to another member with approval confirmation', function () {
    Carbon::setTestNow('2026-04-01 10:00:00');
    Queue::fake();

    $admin = User::factory()->admin()->create();
    $sourceMember = Member::factory()->create(['status' => 'active']);
    $targetMember = Member::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create(['duration_days' => 30]);
    $subscription = Subscription::factory()->create([
        'member_id' => $sourceMember->id,
        'plan_id' => $plan->id,
        'status' => 'active',
        'starts_at' => now()->subDays(10)->toDateString(),
        'ends_at' => now()->addDays(12)->toDateString(),
    ]);

    $this->actingAs($admin);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'transfer')
        ->set('transferToMemberId', $targetMember->id)
        ->set('requiresApproval', true)
        ->call('transfer')
        ->assertHasNoErrors();

    $subscription->refresh();

    $newSubscription = Subscription::query()
        ->where('member_id', $targetMember->id)
        ->where('status', 'active')
        ->latest('id')
        ->first();

    expect($newSubscription)->not->toBeNull();
    expect($subscription->status)->toBe('transferred');
    expect($subscription->days_remaining)->toBe(12);
    expect($newSubscription->plan_id)->toBe($plan->id);
    expect($newSubscription->starts_at->toDateString())->toBe('2026-04-01');
    expect($newSubscription->ends_at->toDateString())->toBe('2026-04-13');

    $this->assertDatabaseHas('subscription_audit_logs', [
        'subscription_id' => $subscription->id,
        'action' => 'transfer',
        'from_member_id' => $sourceMember->id,
        'to_member_id' => $targetMember->id,
        'performed_by' => $admin->id,
    ]);

    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $subscription->id
            && $job->notificationType === 'transferred-from'
            && $job->targetMemberId === $sourceMember->id,
    );
    Queue::assertPushed(
        SendSubscriptionNotification::class,
        fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $newSubscription->id
            && $job->notificationType === 'transferred-to'
            && $job->targetMemberId === $targetMember->id,
    );
    Queue::assertPushed(SyncTerminalWhitelist::class, 2);

    Carbon::setTestNow();
});

test('manager cannot transfer subscription', function () {
    $manager = User::factory()->manager()->create();
    $sourceMember = Member::factory()->create(['status' => 'active']);
    $targetMember = Member::factory()->create(['status' => 'active']);
    $subscription = Subscription::factory()->create([
        'member_id' => $sourceMember->id,
        'status' => 'active',
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'transfer')
        ->set('transferToMemberId', $targetMember->id)
        ->set('requiresApproval', true)
        ->call('transfer')
        ->assertForbidden();
});

test('cannot transfer subscription to a member with an active subscription', function () {
    $admin = User::factory()->admin()->create();
    $sourceMember = Member::factory()->create(['status' => 'active']);
    $targetMember = Member::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $sourceMember->id,
        'status' => 'active',
        'plan_id' => $plan->id,
    ]);

    Subscription::factory()->create([
        'member_id' => $targetMember->id,
        'status' => 'active',
        'plan_id' => $plan->id,
        'ends_at' => now()->addDays(15)->toDateString(),
    ]);

    $this->actingAs($admin);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'transfer')
        ->set('transferToMemberId', $targetMember->id)
        ->set('requiresApproval', true)
        ->call('transfer')
        ->assertHasErrors(['transferToMemberId']);
});

test('transfer requires explicit approval confirmation', function () {
    $admin = User::factory()->admin()->create();
    $sourceMember = Member::factory()->create(['status' => 'active']);
    $targetMember = Member::factory()->create(['status' => 'active']);
    $subscription = Subscription::factory()->create([
        'member_id' => $sourceMember->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin);

    Livewire::test(SubscriptionSuspension::class)
        ->set('subscriptionId', $subscription->id)
        ->set('action', 'transfer')
        ->set('transferToMemberId', $targetMember->id)
        ->set('requiresApproval', false)
        ->call('transfer')
        ->assertHasErrors(['requiresApproval']);
});
