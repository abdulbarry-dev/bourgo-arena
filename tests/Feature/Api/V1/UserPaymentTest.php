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
        'status' => 'completed',
    ]);

    $otherMember = Member::factory()->create();

    Payment::factory()->create([
        'member_id' => $otherMember->id,
        'amount' => 50.00,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($member, 'sanctum')->getJson(route('api.v1.user.payments.index'));

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', 100.5);
});
