<?php

use App\Models\Member;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('initiate payment that requires 3ds preserves metadata and completes after webhook', function () {
    Http::fake([
        'https://api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response([
            'payUrl' => 'https://pay.konnect.com/3ds',
            'paymentRef' => 'TXN3DS123',
            'requires3DS' => true,
        ], 200),
    ]);

    config([
        'payment.providers.konnect.api_key' => 'test-key',
        'payment.providers.konnect.api_secret' => 'test-secret',
        'payment.providers.konnect.sandbox' => true,
        'payment.providers.konnect.webhook_secret' => '3ds-secret',
    ]);

    $member = Member::factory()->create();

    $response = $this->postJson('/api/v1/payments/initiate', [
        'member_id' => $member->id,
        'amount' => 20.00,
        'description' => '3DS Test',
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $body = $response->json();
    $payment = Payment::find($body['payment_id']);

    // Metadata should include requires3DS flag as returned by driver
    expect($payment->metadata['requires3DS'] ?? $payment->metadata['requires3ds'] ?? null)->toBeTrue();

    // Simulate webhook marking as paid
    $payload = ['payment_reference' => $payment->payment_reference, 'status' => 'paid'];
    $sig = hash_hmac('sha256', json_encode($payload), config('payment.providers.konnect.webhook_secret'));

    $web = $this->postJson('/api/v1/payments/webhook/konnect', $payload, ['X-konnect-Signature' => $sig]);
    $web->assertOk()->assertJson(['success' => true]);

    expect($payment->fresh()->status)->toBe('paid');
});

