<?php

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('webhook rejects invalid signature', function () {
    config(['payment.konnect.webhook_secret' => 's3cret']);

    $member = Member::factory()->create();
    $reservation = ApiReservation::factory()->create(['member_id' => $member->id, 'payment_status' => 'pending']);
    $payment = Payment::factory()->create(['reservation_id' => $reservation->id, 'driver' => 'konnect', 'status' => 'initiated']);

    $payload = ['payment_reference' => $payment->payment_reference, 'status' => 'paid'];

    // Send with incorrect signature
    $badSignature = hash_hmac('sha256', json_encode($payload), 'wrong-secret');

    $response = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $badSignature]);

    $response->assertStatus(403)->assertJson(['success' => false, 'error' => 'invalid_signature']);
    expect($payment->fresh()->status)->toBe('initiated');
    expect($reservation->fresh()->payment_status)->toBe('pending');
});

test('webhook returns 404 for unknown payment reference', function () {
    config(['payment.konnect.webhook_secret' => 'test-secret']);

    $payload = ['payment_reference' => 'nonexistent_ref', 'status' => 'paid'];
    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, config('payment.konnect.webhook_secret'));

    $response = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $signature]);

    $response->assertStatus(404)->assertJson(['success' => false, 'error' => 'payment_not_found']);
});

test('duplicate webhook calls are idempotent', function () {
    config(['payment.konnect.webhook_secret' => 'dup-secret']);

    $member = Member::factory()->create();
    $reservation = ApiReservation::factory()->create(['member_id' => $member->id, 'payment_status' => 'pending']);
    $payment = Payment::factory()->create(['reservation_id' => $reservation->id, 'driver' => 'konnect', 'status' => 'initiated']);

    $payload = ['payment_reference' => $payment->payment_reference, 'status' => 'paid', 'payment_id' => 'TXN_DUP_1'];
    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, config('payment.konnect.webhook_secret'));

    // First call
    $r1 = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $signature]);
    $r1->assertOk()->assertJson(['success' => true]);

    $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'paid']);

    // Second call (duplicate)
    $r2 = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $signature]);
    $r2->assertOk()->assertJson(['success' => true, 'message' => 'already_processed']);
});
