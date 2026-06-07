<?php

use App\Models\ApiReservation;
use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('konnect webhook verifies signature and reconciles payment', function () {
    config(['payment.providers.konnect.webhook_secret' => 'test-secret']);

    $member = Member::factory()->create();

    $reservation = ApiReservation::factory()->create([
        'member_id' => $member->id,
        'status' => 'confirmed',
        'payment_status' => 'pending',
    ]);

    $payment = Payment::create([
        'member_id' => $member->id,
        'reservation_id' => $reservation->id,
        'driver' => 'konnect',
        'type' => 'reservation_deposit',
        'amount' => 5.000,
        'status' => 'initiated',
        'payment_reference' => 'pay_test_123',
    ]);

    $payload = [
        'payment_reference' => $payment->payment_reference,
        'status' => 'paid',
        'payment_id' => 'TXN123',
    ];

    $payloadJson = json_encode($payload);
    $signature = hash_hmac('sha256', $payloadJson, config('payment.providers.konnect.webhook_secret'));

    $response = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $signature]);

    $response->assertOk()->assertJson(['success' => true]);

    expect($payment->fresh()->status)->toBe('paid');
    expect($reservation->fresh()->payment_status)->toBe('paid');
});
