<?php

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can list user payments', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 100.50,
        'status' => 'paid',
    ]);

    $otherMember = Member::factory()->create();

    Payment::factory()->create([
        'member_id' => $otherMember->id,
        'amount' => 50.00,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($member, 'sanctum')->getJson(route('api.v1.user.payments.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', 100.5);
});

it('excludes loyalty payments from index', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'konnect',
        'amount' => 100.00,
        'status' => 'paid',
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'loyalty',
        'gateway' => 'loyalty_points',
        'amount' => 50.00,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($member, 'sanctum')->getJson(route('api.v1.user.payments.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', 100);
});

it('includes payment_method field in response', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'driver' => 'konnect',
        'amount' => 75.00,
        'status' => 'paid',
    ]);

    $response = $this->actingAs($member, 'sanctum')->getJson(route('api.v1.user.payments.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.payment_method', 'konnect');
});
