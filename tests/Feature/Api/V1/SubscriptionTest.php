<?php

use App\Events\PaymentPaid;
use App\Models\Course;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('can initiate a subscription to a plan as pending', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Subscription initiated successfully. Please proceed to payment.')
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('subscriptions', [
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);
});

it('blocks initiation if there is already a pending subscription for the exact same plan', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    // Create existing pending subscription
    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You already have a pending payment for this exact plan. Please complete it before trying again.');
});

it('activates a pending subscription when payment is paid', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'type' => 'subscription',
        'status' => 'initiated',
    ]);

    // Simulate successful payment
    $payment->update(['status' => 'paid']);
    PaymentPaid::dispatch($payment);

    expect($subscription->fresh()->status)->toBe('active');
    expect($subscription->fresh()->receipt_path)->not->toBeNull();
});

it('handles upgrade during activation when payment is paid', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $service = Service::factory()->create();
    $oldPlan = Plan::factory()->create(['service_id' => $service->id, 'level' => 2, 'duration_days' => 30]);
    $newPlan = Plan::factory()->create(['service_id' => $service->id, 'level' => 3, 'duration_days' => 90]);

    $oldSubscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $oldPlan->id,
        'status' => 'active',
        'ends_at' => now()->addDays(10),
    ]);

    $pendingSubscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $newPlan->id,
        'status' => 'pending',
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'subscription_id' => $pendingSubscription->id,
        'type' => 'subscription',
        'status' => 'initiated',
    ]);

    // Simulate successful payment for upgrade
    $payment->update(['status' => 'paid']);
    PaymentPaid::dispatch($payment);

    // Old subscription should be updated (extended)
    expect($oldSubscription->fresh()->plan_id)->toBe($newPlan->id);
    expect($oldSubscription->fresh()->ends_at->toDateString())
        ->toBe(now()->addDays(10)->addDays(90)->toDateString());

    // Pending subscription should be discarded (deleted)
    $this->assertDatabaseMissing('subscriptions', ['id' => $pendingSubscription->id]);
});

it('supports stacking of different plans in the same service', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $service = Service::factory()->create();
    $course1 = Course::factory()->create();
    $course2 = Course::factory()->create();

    $plan1 = Plan::factory()->create(['service_id' => $service->id, 'level' => 2]);
    $plan1->courses()->attach($course1);

    $plan2 = Plan::factory()->create(['service_id' => $service->id, 'level' => 2]);
    $plan2->courses()->attach($course2);

    // Active subscription for Plan 1
    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan1->id,
        'status' => 'active',
    ]);

    // Purchase Plan 2 (should be allowed to stack)
    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan2->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.plan.id', $plan2->id);

    $this->assertDatabaseCount('subscriptions', 2);
});

it('blocks redundant plan purchases', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $service = Service::factory()->create();
    $elitePlan = Plan::factory()->create(['service_id' => $service->id, 'level' => 3, 'has_all_courses' => true]);
    $basicPlan = Plan::factory()->create(['service_id' => $service->id, 'level' => 1]);

    // Active ELITE subscription
    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $elitePlan->id,
        'status' => 'active',
    ]);

    // Attempt to buy BASIC plan (redundant)
    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $basicPlan->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Your current plan already provides full access to this service. This new plan would be redundant.');
});

it('auto-cancels stale pending subscription and allows new attempt', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $oldSubscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    DB::table('subscriptions')->where('id', $oldSubscription->id)->update([
        'created_at' => now()->subMinutes(31),
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.status', 'pending');

    $this->assertDatabaseHas('subscriptions', [
        'id' => $oldSubscription->id,
        'status' => 'cancelled',
    ]);
});

it('blocks when pending subscription has active initiated payment within timeout', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    DB::table('subscriptions')->where('id', $subscription->id)->update([
        'created_at' => now()->subMinutes(31),
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'type' => 'subscription',
        'status' => 'initiated',
        'updated_at' => now()->subMinutes(5),
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'You already have a pending payment for this exact plan. Please complete it before trying again.');
});

it('allows member to cancel own pending subscription', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'subscription_id' => $subscription->id,
        'type' => 'subscription',
        'status' => 'initiated',
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(
        route('api.v1.subscriptions.cancel', $subscription)
    );

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Pending subscription cancelled successfully.')
        ->assertJsonPath('data.id', $subscription->id)
        ->assertJsonPath('data.status', 'cancelled');

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'failed',
    ]);
});

it('rejects cancel of non-pending subscription', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(
        route('api.v1.subscriptions.cancel', $subscription)
    );

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Only pending subscriptions can be cancelled.');
});

it('returns 404 when cancelling another members subscription', function () {
    $memberA = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $memberB = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->create([
        'member_id' => $memberB->id,
        'plan_id' => $plan->id,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($memberA, 'sanctum')->postJson(
        route('api.v1.subscriptions.cancel', $subscription)
    );

    $response->assertStatus(404);
});
