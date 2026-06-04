<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can initiate a subscription to a plan', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $plan = Plan::factory()->create();

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.store'), [
        'plan_id' => $plan->id,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('message', 'Subscription initiated successfully');
});

it('can cancel an active subscription', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $subscription = Subscription::factory()->create([
        'member_id' => $member->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')->postJson(route('api.v1.subscriptions.cancel', $subscription));

    $response->assertStatus(200)
        ->assertJsonPath('message', 'Subscription cancelled successfully');
});

it('cannot cancel another users subscription', function () {
    $member1 = Member::factory()->create();
    $member2 = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $subscription = Subscription::factory()->create([
        'member_id' => $member1->id,
        'status' => 'active',
    ]);

    $response = $this->actingAs($member2, 'sanctum')->postJson(route('api.v1.subscriptions.cancel', $subscription));

    $response->assertStatus(403);
});
