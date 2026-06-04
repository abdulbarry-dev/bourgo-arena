<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Services\Payment\Providers\FlouciProvider;
use App\Services\Payment\Providers\KonnectProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private KonnectProvider $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = new KonnectProvider;
    }

    public function test_konnect_gateway_is_resolvable(): void
    {
        $this->assertInstanceOf(KonnectProvider::class, $this->gateway);
    }

    public function test_konnect_gateway_name_and_sandbox_flag(): void
    {
        $gateway = new KonnectProvider;

        $this->assertSame('konnect', $gateway->getName());
    }

    public function test_konnect_gateway_initiate_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.network/api/v2/payments/init-payment' => Http::response([
                'payUrl' => 'https://pay.konnect.com/123',
                'paymentRef' => 'TXNREF123',
                'requires3DS' => true,
            ]),
        ]);

        config([
            'payment.providers.konnect.api_key' => 'test-key',
            'payment.providers.konnect.api_secret' => 'test-secret',
            'payment.providers.konnect.sandbox' => true,
        ]);

        $payment = new Payment(['amount' => 50.00, 'payment_reference' => 'PAYREF123']);

        $result = (new KonnectProvider)->initiate($payment, [
            'description' => 'Test Payment',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://pay.konnect.com/123', $result['payment_url']);
        $this->assertSame('TXNREF123', $result['gateway_transaction_id']);
        $this->assertTrue($result['requires3DS']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.sandbox.konnect.network/api/v2/payments/init-payment';
        });
    }

    public function test_konnect_gateway_verify_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.network/api/v2/payments/TXNREF123' => Http::response([
                'status' => 'completed',
                'amount' => 50000,
                'paymentRef' => 'TXNREF123',
                'createdAt' => now()->toIso8601String(),
            ]),
        ]);

        config([
            'payment.providers.konnect.api_key' => 'test-key',
            'payment.providers.konnect.api_secret' => 'test-secret',
            'payment.providers.konnect.sandbox' => true,
        ]);

        $result = (new KonnectProvider)->verify('TXNREF123');

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertEquals(50.0, $result['amount']);
    }

    public function test_payment_initiation_fails_without_credentials(): void
    {
        config([
            'payment.providers.konnect.api_key' => null,
            'payment.providers.konnect.api_secret' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Konnect API credentials not configured');

        $payment = new Payment(['amount' => 50.00, 'payment_reference' => 'REF']);

        (new KonnectProvider)->initiate($payment, [
            'description' => 'Test',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);
    }

    public function test_flouci_gateway_initiate_payment_success(): void
    {
        Http::fake([
            'https://developers.flouci.com/api/v2/generate_payment' => Http::response([
                'result' => [
                    'success' => true,
                    'link' => 'https://pay.flouci.com/456',
                    'payment_id' => 'FLOUCI456',
                ],
            ]),
        ]);

        config([
            'payment.providers.flouci.app_token' => 'public-key',
            'payment.providers.flouci.app_secret' => 'private-key',
            'payment.providers.flouci.sandbox' => true,
        ]);

        $payment = new Payment(['amount' => 50.00, 'payment_reference' => 'PAYREF456']);

        $result = (new FlouciProvider)->initiate($payment, [
            'description' => 'Test Payment',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://pay.flouci.com/456', $result['payment_url']);
        $this->assertSame('FLOUCI456', $result['gateway_transaction_id']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://developers.flouci.com/api/v2/generate_payment'
                && $request->hasHeader('Authorization');
        });
    }
}
