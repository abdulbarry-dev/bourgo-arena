<?php

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use App\Services\Payment\Providers\FlouciProvider;
use App\Services\Payment\Providers\KonnectProvider;
use Illuminate\Support\Facades\Http;

test('konnect and flouci providers implement the shared gateway contract', function () {
    expect(new KonnectProvider)->toBeInstanceOf(PaymentGatewayInterface::class);
    expect(new FlouciProvider)->toBeInstanceOf(PaymentGatewayInterface::class);

    expect(method_exists(new KonnectProvider, 'initiatePayment'))->toBeTrue();
    expect(method_exists(new KonnectProvider, 'verifyPayment'))->toBeTrue();
    expect(method_exists(new FlouciProvider, 'initiatePayment'))->toBeTrue();
    expect(method_exists(new FlouciProvider, 'verifyPayment'))->toBeTrue();
});

test('konnect initiatePayment returns normalized payment identifiers and expiration', function () {
    Http::fake([
        'https://api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response([
            'payUrl' => 'https://pay.konnect.com/123',
            'paymentRef' => 'TXNREF123',
        ]),
    ]);

    config([
        'payment.providers.konnect.api_key' => 'test-key',
        'payment.providers.konnect.api_secret' => 'test-secret',
        'payment.providers.konnect.sandbox' => true,
    ]);

    $result = (new KonnectProvider)->initiatePayment(
        new Payment(['amount' => 50.00, 'payment_reference' => 'PAYREF123']),
        ['expires_in_minutes' => 15]
    );

    expect($result['success'])->toBeTrue();
    expect($result['payment_id'])->toBe('TXNREF123');
    expect($result['redirect_url'])->toBe('https://pay.konnect.com/123');
    expect($result['expires_at'])->not->toBeEmpty();
});

test('flouci initiatePayment returns normalized payment identifiers and expiration', function () {
    Http::fake([
        'https://developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'link' => 'https://pay.flouci.com/456',
                'payment_id' => 'FLOUCI456',
            ],
        ]),
    ]);

    config([
        'payment.providers.flouci.app_token' => 'test-token',
        'payment.providers.flouci.app_secret' => 'test-secret',
        'payment.providers.flouci.sandbox' => true,
    ]);

    $result = (new FlouciProvider)->initiatePayment(
        new Payment(['amount' => 50.00, 'payment_reference' => 'PAYREF456']),
        ['expires_in_seconds' => 600]
    );

    expect($result['success'])->toBeTrue();
    expect($result['payment_id'])->toBe('FLOUCI456');
    expect($result['redirect_url'])->toBe('https://pay.flouci.com/456');
    expect($result['expires_at'])->not->toBeEmpty();
});
