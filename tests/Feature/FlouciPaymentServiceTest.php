<?php

use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Services\PaymentGateway\FlouciPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('flouci payment service signs initiation requests and logs the audit row', function () {
    Http::fake([
        'https://developers.flouci.com/api/v2/generate_payment' => Http::response([
            'result' => [
                'success' => true,
                'payment_id' => 'FLOUCI123',
                'link' => 'https://pay.flouci.com/FLOUCI123',
            ],
        ]),
    ]);

    config([
        'payment.providers.flouci.app_token' => 'public-key',
        'payment.providers.flouci.app_secret' => 'private-key',
        'payment.providers.flouci.sandbox' => true,
    ]);

    $member = Member::factory()->create();
    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'amount' => 50.00,
        'currency' => 'TND',
        'payment_reference' => 'PAYREF123',
    ]);

    $result = app(FlouciPaymentService::class)->initiatePayment([
        'amount' => $payment->amount,
        'payment_reference' => $payment->payment_reference,
        'success_url' => 'https://example.com/success',
        'failure_url' => 'https://example.com/failure',
        'user' => [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
        ],
    ]);

    expect($result['success'])->toBeTrue();
    expect($result['payment_id'])->toBe('FLOUCI123');
    expect($result['redirect_url'])->toBe('https://pay.flouci.com/FLOUCI123');

    Http::assertSent(function ($request): bool {
        $payload = $request->data();

        return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
            && $request->hasHeader('Authorization')
            && $payload['amount'] === 50000
            && $payload['signature'] === hash('sha256', implode('', [
                (string) $payload['amount'],
                (string) $payload['developer_tracking_id'],
                (string) $payload['accept_card'],
                (string) $payload['success_link'],
                (string) $payload['fail_link'],
                (string) $payload['session_timeout_secs'],
                (string) $payload['client_id'],
                (string) 'private-key',
            ]));
    });

    $raw = DB::table('payment_transactions')->where('transaction_id', 'FLOUCI123')->first();

    expect($raw)->not->toBeNull();
    expect((string) $raw->request_payload)->not->toContain('PAYREF123');

    $entry = PaymentTransaction::where('transaction_id', 'FLOUCI123')->firstOrFail();

    expect($entry->payment_gateway)->toBe('flouci');
    expect($entry->request_payload)->toMatchArray([
        'amount' => 50000,
        'developer_tracking_id' => 'PAYREF123',
    ]);
});

test('flouci payment service verifies through the sandbox endpoint', function () {
    Http::fake([
        'https://developers.flouci.com/api/v2/verify_payment/FLOUCI123' => Http::response([
            'success' => true,
            'result' => [
                'amount' => 50000,
                'status' => 'SUCCESS',
                'payment_id' => 'FLOUCI123',
                'developer_tracking_id' => 'PAYREF123',
                'settlement_status' => 'AVAILABLE',
            ],
        ]),
    ]);

    config([
        'payment.providers.flouci.app_token' => 'public-key',
        'payment.providers.flouci.app_secret' => 'private-key',
        'payment.providers.flouci.sandbox' => true,
    ]);

    $service = app(FlouciPaymentService::class);

    $verified = $service->verifyPayment('FLOUCI123');

    expect($verified['success'])->toBeTrue();
    expect($verified['status'])->toBe('paid');
    expect($verified['amount'])->toBe(50.0);
});
