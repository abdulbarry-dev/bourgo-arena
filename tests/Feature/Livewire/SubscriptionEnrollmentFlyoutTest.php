<?php

use App\Jobs\SendSubscriptionNotification;
use App\Jobs\SendSubscriptionReceiptEmail;
use App\Livewire\Admin\Subscriptions\SubscriptionEnrollmentFlyout;
use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('manager can enroll pending member and activate access', function () {
    Storage::fake('local');
    config(['payment.receipts.disk' => 'local']);
    Queue::fake();
    Notification::fake();

    $manager = User::factory()->manager()->create();
    $member = Member::factory()->create(['status' => 'pending']);
    $plan = Plan::factory()->create([
        'duration_days' => 30,
        'price' => 150.000,
        'is_archived' => false,
    ]);

    $this->actingAs($manager);

    Livewire::test(SubscriptionEnrollmentFlyout::class)
        ->set('memberId', $member->id)
        ->set('planId', $plan->id)
        ->set('startsAt', '2026-04-02')
        ->set('paymentMethod', 'cash')
        ->call('enroll')
        ->assertHasNoErrors()
        ->assertDispatched('subscription-created', memberId: $member->id);

    $subscription = Subscription::query()
        ->where('member_id', $member->id)
        ->latest('id')
        ->first();

    expect($subscription)->not->toBeNull();
    expect($subscription->status)->toBe('active');
    expect($subscription->starts_at->toDateString())->toBe('2026-04-02');
    expect($subscription->ends_at->toDateString())->toBe('2026-05-02');
    expect($subscription->payment_method)->toBe('cash');
    expect($subscription->payment_reference)->toBeNull();
    expect($subscription->amount_paid)->toBe('150.000');
    expect($subscription->receipt_path)->not->toBeNull();

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'status' => 'active',
    ]);

    Queue::assertPushed(SendSubscriptionReceiptEmail::class, fn (SendSubscriptionReceiptEmail $job): bool => $job->subscriptionId === $subscription->id);
    Queue::assertPushed(SendSubscriptionNotification::class, fn (SendSubscriptionNotification $job): bool => $job->subscriptionId === $subscription->id && $job->notificationType === 'enrolled');
});

test('gateway payment method requires payment reference', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create(['status' => 'pending']);
    $plan = Plan::factory()->create(['is_archived' => false]);

    Livewire::test(SubscriptionEnrollmentFlyout::class)
        ->set('memberId', $member->id)
        ->set('planId', $plan->id)
        ->set('startsAt', '2026-04-01')
        ->set('paymentMethod', 'tpe')
        ->set('paymentReference', null)
        ->call('enroll')
        ->assertHasErrors(['paymentReference']);
});

test('archived plans are rejected by enrollment validation', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create(['status' => 'pending']);
    $archivedPlan = Plan::factory()->archived()->create();

    Livewire::test(SubscriptionEnrollmentFlyout::class)
        ->set('memberId', $member->id)
        ->set('planId', $archivedPlan->id)
        ->set('startsAt', '2026-04-01')
        ->set('paymentMethod', 'cash')
        ->call('enroll')
        ->assertHasErrors(['planId']);
});

test('member role cannot enroll subscriptions', function () {
    $this->actingAs(User::factory()->member()->create());

    $member = Member::factory()->create(['status' => 'pending']);
    $plan = Plan::factory()->create(['is_archived' => false]);

    Livewire::test(SubscriptionEnrollmentFlyout::class)
        ->set('memberId', $member->id)
        ->set('planId', $plan->id)
        ->set('startsAt', '2026-04-01')
        ->set('paymentMethod', 'cash')
        ->call('enroll')
        ->assertForbidden();
});

test('cannot enroll member who already has active subscription', function () {
    $this->actingAs(User::factory()->manager()->create());

    $member = Member::factory()->create(['status' => 'active']);
    $plan = Plan::factory()->create(['is_archived' => false]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
        'ends_at' => now()->addDays(20)->toDateString(),
        'plan_id' => $plan->id,
    ]);

    Livewire::test(SubscriptionEnrollmentFlyout::class)
        ->set('memberId', $member->id)
        ->set('planId', $plan->id)
        ->set('startsAt', '2026-04-01')
        ->set('paymentMethod', 'cash')
        ->call('enroll')
        ->assertHasErrors(['planId']);
});
