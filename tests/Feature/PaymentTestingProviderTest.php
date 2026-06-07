<?php

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'status' => 'active',
    ]);
});

test('it can initiate a payment using the test provider', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 100,
            'provider' => 'test',
            'description' => 'Normal test payment',
            'success_url' => 'http://localhost/success',
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'payment_url',
            'payment_reference',
            'payment_id',
        ]);
});

test('it fails to initiate a payment using the test provider if description contains test_failure', function () {
    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 100,
            'provider' => 'test',
            'description' => 'This is a test_failure payment',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'error' => 'Simulated test failure',
        ]);
});

test('it can verify a payment using the test provider', function () {
    // First initiate to get a reference
    $initResponse = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v1/payments/initiate', [
            'amount' => 100,
            'provider' => 'test',
        ]);

    $reference = $initResponse->json('payment_reference');

    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v1/payments/verify', [
            'payment_reference' => $reference,
        ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'status' => 'paid',
        ]);
});

test('it fails verification if reference contains fail', function () {
    // Create a payment record manually to avoid 404
    $payment = Payment::create([
        'member_id' => $this->member->id,
        'amount' => 100,
        'driver' => 'test',
        'status' => 'initiated',
        'payment_reference' => 'test_fail_123',
        'gateway_transaction_id' => 'test_fail_123',
    ]);

    $response = $this->actingAs($this->member, 'sanctum')
        ->postJson('/api/v1/payments/verify', [
            'payment_reference' => 'test_fail_123',
        ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'status' => 'failed',
        ]);
});
