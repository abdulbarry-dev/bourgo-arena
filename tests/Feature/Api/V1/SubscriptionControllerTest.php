<?php

use App\Models\Member;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated member can access active subscription endpoint', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(route('api.v1.member.subscription'));

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'No active subscriptions were found for your account.',
            'data' => [],
        ]);
});

test('member with multiple active subscriptions gets all detailed subscriptions', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan1 = Plan::factory()->create(['name' => 'Yoga Plan']);
    $plan2 = Plan::factory()->create(['name' => 'Gym Plan']);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan1->id,
        'status' => 'active',
        'starts_at' => now()->subDays(5),
        'ends_at' => now()->addDays(25),
    ]);

    Subscription::factory()->create([
        'member_id' => $member->id,
        'plan_id' => $plan2->id,
        'status' => 'active',
        'starts_at' => now()->subDays(10),
        'ends_at' => now()->addDays(20),
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(route('api.v1.member.subscription'));

    $response->assertSuccessful()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('message', 'Successfully retrieved 2 active subscriptions detailing your current planning access.')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'plan' => ['id', 'name', 'description', 'price', 'has_all_courses'],
                    'service' => ['id', 'name', 'slug', 'image_url'],
                    'status',
                    'starts_at',
                    'ends_at',
                    'days_remaining',
                    'payment_method',
                    'amount_paid',
                    'is_active',
                ],
            ],
        ]);

    $response->assertJsonFragment(['name' => 'Yoga Plan'])
        ->assertJsonFragment(['name' => 'Gym Plan']);
});

test('member can view subscription history', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'state' => 'active',
    ]);

    $plan = Plan::factory()->create(['name' => 'Legacy Plan']);

    // Create 3 historical subscriptions
    Subscription::factory()->count(3)->create([
        'member_id' => $member->id,
        'plan_id' => $plan->id,
        'status' => 'expired',
    ]);

    $response = $this->actingAs($member, 'sanctum')
        ->getJson(route('api.v1.member.subscriptions.history'));

    $response->assertSuccessful()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'plan', 'service', 'status', 'receipt_url'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});
