<?php

use App\Models\Member;
use App\Models\Payment;
use App\Services\PaymentGateway\KonnectPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('konnect payment service initiates payments against the sandbox network endpoint and audits the request', function () {
    Http::fake([
        'https://api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response([
            'payUrl' => 'https://pay.konnect.com/xyz',
            'paymentRef' => 'KONNECT-123',
        ], 200),
    ]);

    config([
        'payment.providers.konnect.api_key' => 'test-key',
        'payment.providers.konnect.api_secret' => 'test-secret',
        'payment.providers.konnect.sandbox' => true,
    ]);

    $member = Member::factory()->create();
    $payment = Payment::create([
        'member_id' => $member->id,
        'reservation_id' => null,
        'subscription_id' => null,
        'driver' => 'konnect',
        'type' => 'reservation_deposit',
        'amount' => 12.500,
        'currency' => 'TND',
        'status' => 'pending',
        'payment_reference' => 'order-123',
        'gateway_transaction_id' => null,
        'metadata' => null,
    ]);

    $result = app(KonnectPaymentService::class)->initiatePayment([
        'amount' => $payment->amount,
        'currency' => $payment->currency,
        'payment_reference' => $payment->payment_reference,
        'order_id' => $payment->payment_reference,
        'description' => 'Reservation deposit',
        'success_url' => 'https://example.test/success',
        'failure_url' => 'https://example.test/failure',
        'user' => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
        ],
        'user_id' => $member->id,
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['payment_id'])->toBe('KONNECT-123');
    expect($result['redirect_url'])->toBe('https://pay.konnect.com/xyz');
    expect($result['expires_at'])->not->toBeEmpty();

    Http::assertSent(function ($request): bool {
        return $request->url() === 'https://api.sandbox.konnect.network/api/v2/payments/init-payment'
            && $request['amount'] === 12500
            && $request['token'] === 'order-123'
            && $request['orderId'] === 'order-123';
    });

    $this->assertDatabaseHas('payment_transactions', [
        'transaction_id' => 'KONNECT-123',
        'payment_gateway' => 'konnect',
        'transaction_status' => 'initiated',
    ]);

    $raw = DB::table('payment_transactions')->where('transaction_id', 'KONNECT-123')->first();
    expect($raw)->not->toBeNull();
    expect((string) $raw->request_payload)->not->toContain('Reservation deposit');
    expect((string) $raw->response_payload)->not->toContain('KONNECT-123');
});
