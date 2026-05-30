<?php

use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\PaymentAuditService;
use Illuminate\Support\Facades\DB;

test('payment audit service logs transaction details with sensible defaults', function () {
    $payment = Payment::factory()->create([
        'amount' => 12.345,
        'currency' => 'TND',
        'driver' => 'konnect',
        'status' => 'initiated',
        'payment_reference' => 'audit_ref_123',
    ]);

    $service = app(PaymentAuditService::class);

    $entry = $service->log($payment, [
        'transaction_status' => 'initiated',
        'request_payload' => ['amount' => 12.345, 'currency' => 'TND'],
        'response_payload' => ['payment_url' => 'https://gateway.example/pay'],
        'ip_address' => '10.10.10.10',
        'user_agent' => 'Pest-Test-Agent',
    ]);

    expect($entry)->toBeInstanceOf(PaymentTransaction::class);
    expect($entry->transaction_id)->toBe('audit_ref_123');
    expect($entry->currency)->toBe('TND');
    expect($entry->payment_gateway)->toBe('konnect');
    expect($entry->transaction_status)->toBe('initiated');
    expect($entry->ip_address)->toBe('10.10.10.10');
    expect($entry->user_agent)->toBe('Pest-Test-Agent');
    expect($entry->request_payload)->toMatchArray(['amount' => 12.345, 'currency' => 'TND']);
    expect($entry->response_payload)->toMatchArray(['payment_url' => 'https://gateway.example/pay']);
});

test('payment audit service encrypts sensitive payloads at rest', function () {
    $user = User::factory()->create([
        'name' => 'Audit User',
        'email' => 'audit@example.test',
    ]);

    $payment = Payment::factory()->create([
        'amount' => 20.100,
        'currency' => 'TND',
        'driver' => 'flouci',
        'status' => 'paid',
        'payment_reference' => 'flouci_ref_987',
    ]);

    $entry = app(PaymentAuditService::class)->log($payment, [
        'user_id' => $user->id,
        'user_information' => [
            'name' => 'Audit User',
            'email' => 'audit@example.test',
            'phone' => '+21600000000',
        ],
        'reservation_details' => [
            'id' => 77,
            'activity_id' => 8,
            'date' => '2026-06-01',
        ],
        'request_payload' => [
            'card_last4' => '4242',
            'token' => 'tok_secret_example',
        ],
        'response_payload' => [
            'gateway_status' => 'ok',
            'authorization_code' => 'AUTH123',
        ],
        'refund_status' => 'completed',
        'refund_amount' => 3.000,
        'refund_reference' => 'refund_123',
        'refund_details' => [
            'processed_by' => 'system',
            'reason' => 'customer_request',
        ],
    ]);

    $raw = DB::table('payment_transactions')->where('id', $entry->id)->first();

    expect($raw)->not->toBeNull();
    expect((string) $raw->request_payload)->not->toContain('tok_secret_example');
    expect((string) $raw->response_payload)->not->toContain('AUTH123');
    expect((string) $raw->user_information)->not->toContain('audit@example.test');
    expect((string) $raw->reservation_details)->not->toContain('2026-06-01');
    expect((string) $raw->refund_details)->not->toContain('customer_request');

    $fresh = PaymentTransaction::findOrFail($entry->id);

    expect($fresh->user_information['email'])->toBe('audit@example.test');
    expect($fresh->request_payload['token'])->toBe('tok_secret_example');
    expect($fresh->response_payload['authorization_code'])->toBe('AUTH123');
    expect($fresh->reservation_details['date'])->toBe('2026-06-01');
    expect($fresh->refund_details['reason'])->toBe('customer_request');
});
