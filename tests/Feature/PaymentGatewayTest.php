<?php

namespace Tests\Feature;

use App\Services\PaymentGateway\KonnectGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private KonnectGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->gateway = app(KonnectGateway::class);
    }

    public function test_konnect_gateway_is_resolvable(): void
    {
        $this->assertInstanceOf(KonnectGateway::class, $this->gateway);
    }

    public function test_konnect_gateway_validates_credentials(): void
    {
        config([
            'payment.konnect.api_key' => null,
            'payment.konnect.api_secret' => null,
        ]);

        $this->assertFalse((new KonnectGateway)->validate());

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
        ]);

        $this->assertTrue((new KonnectGateway)->validate());
    }

    public function test_konnect_gateway_name_and_sandbox_flag(): void
    {
        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $gateway = new KonnectGateway;

        $this->assertSame('Konnect', $gateway->getName());
        $this->assertTrue($gateway->isSandbox());

        config(['payment.konnect.sandbox' => false]);

        $this->assertFalse((new KonnectGateway)->isSandbox());
    }

    public function test_konnect_gateway_initiate_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.com.tn/api/v2/payments/init-payment' => Http::response([
                'payUrl' => 'https://pay.konnect.com/123',
                'paymentRef' => 'TXNREF123',
                'requires3DS' => true,
            ]),
        ]);

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $result = (new KonnectGateway)->initiate([
            'amount' => 50.00,
            'description' => 'Test Payment',
            'payment_reference' => 'PAYREF123',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('https://pay.konnect.com/123', $result['payment_url']);
        $this->assertSame('TXNREF123', $result['gateway_transaction_id']);
        $this->assertTrue($result['requires3DS']);

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://api.sandbox.konnect.com.tn/api/v2/payments/init-payment';
        });
    }

    public function test_konnect_gateway_verify_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.com.tn/api/v2/payments/TXNREF123' => Http::response([
                'status' => 'completed',
                'amount' => 50000,
                'paymentRef' => 'TXNREF123',
                'createdAt' => now()->toIso8601String(),
            ]),
        ]);

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $result = (new KonnectGateway)->verify('TXNREF123');

        $this->assertTrue($result['success']);
        $this->assertSame('completed', $result['status']);
        $this->assertEquals(50.0, $result['amount']);
    }

    public function test_konnect_gateway_refund_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.com.tn/api/v2/payments/TXNREF123/refund' => Http::response([
                'refundRef' => 'REFUND123',
            ]),
        ]);

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $result = (new KonnectGateway)->refund('TXNREF123', 25.0);

        $this->assertTrue($result['success']);
        $this->assertSame('REFUND123', $result['refund_id']);
    }

    public function test_payment_initiation_fails_without_credentials(): void
    {
        config([
            'payment.konnect.api_key' => null,
            'payment.konnect.api_secret' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Konnect API credentials not configured');

        (new KonnectGateway)->initiate([
            'amount' => 50.0,
            'description' => 'Test',
            'payment_reference' => 'REF',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);
    }
}
