<?php

namespace Tests\Feature;

use App\Services\PaymentGateway\Drivers\KonnectDriver;
use App\Services\PaymentGateway\Drivers\PaymeeDriver;
use App\Services\PaymentGateway\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    use RefreshDatabase;

    private PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = app(PaymentGatewayManager::class);
    }

    /**
     * Test Konnect driver initialization.
     */
    public function test_konnect_driver_can_be_resolved(): void
    {
        $driver = $this->manager->driver('konnect');
        $this->assertInstanceOf(KonnectDriver::class, $driver);
    }

    /**
     * Test Paymee driver initialization.
     */
    public function test_paymee_driver_can_be_resolved(): void
    {
        $driver = $this->manager->driver('paymee');
        $this->assertInstanceOf(PaymeeDriver::class, $driver);
    }

    /**
     * Test payment gateway manager default driver.
     */
    public function test_manager_returns_default_driver(): void
    {
        config(['payment.driver' => 'konnect']);
        $driver = $this->manager->driver();
        $this->assertInstanceOf(KonnectDriver::class, $driver);
    }

    /**
     * Test changing default driver.
     */
    public function test_can_change_default_driver(): void
    {
        $this->manager->setDefaultDriver('paymee');
        $driver = $this->manager->driver();
        $this->assertInstanceOf(PaymeeDriver::class, $driver);
    }

    /**
     * Test available drivers list.
     */
    public function test_available_drivers_list(): void
    {
        $available = $this->manager->available();
        $this->assertContains('konnect', $available);
        $this->assertContains('paymee', $available);
    }

    /**
     * Test Konnect driver validation.
     */
    public function test_konnect_driver_validates_credentials(): void
    {
        config([
            'payment.konnect.api_key' => null,
            'payment.konnect.api_secret' => null,
        ]);

        $driver = new KonnectDriver;
        $this->assertFalse($driver->validate());

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
        ]);
        $driver = new KonnectDriver;
        $this->assertTrue($driver->validate());
    }

    /**
     * Test Paymee driver validation.
     */
    public function test_paymee_driver_validates_credentials(): void
    {
        config([
            'payment.paymee.api_key' => null,
            'payment.paymee.api_secret' => null,
        ]);

        $driver = new PaymeeDriver;
        $this->assertFalse($driver->validate());

        config([
            'payment.paymee.api_key' => 'test-key',
            'payment.paymee.api_secret' => 'test-secret',
        ]);
        $driver = new PaymeeDriver;
        $this->assertTrue($driver->validate());
    }

    /**
     * Test Konnect driver names.
     */
    public function test_konnect_driver_name(): void
    {
        config(['payment.konnect.api_key' => 'test-key']);
        $driver = new KonnectDriver;
        $this->assertEquals('Konnect', $driver->getName());
    }

    /**
     * Test Paymee driver name.
     */
    public function test_paymee_driver_name(): void
    {
        config(['payment.paymee.api_key' => 'test-key']);
        $driver = new PaymeeDriver;
        $this->assertEquals('Paymee', $driver->getName());
    }

    /**
     * Test Konnect sandbox mode.
     */
    public function test_konnect_sandbox_mode(): void
    {
        config(['payment.konnect.api_key' => 'test-key', 'payment.konnect.sandbox' => true]);
        $driver = new KonnectDriver;
        $this->assertTrue($driver->isSandbox());

        config(['payment.konnect.sandbox' => false]);
        $driver = new KonnectDriver;
        $this->assertFalse($driver->isSandbox());
    }

    /**
     * Test Paymee sandbox mode.
     */
    public function test_paymee_sandbox_mode(): void
    {
        config(['payment.paymee.api_key' => 'test-key', 'payment.paymee.sandbox' => true]);
        $driver = new PaymeeDriver;
        $this->assertTrue($driver->isSandbox());

        config(['payment.paymee.sandbox' => false]);
        $driver = new PaymeeDriver;
        $this->assertFalse($driver->isSandbox());
    }

    /**
     * Test Konnect payment initiation with mock response.
     */
    public function test_konnect_initiate_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.com.tn/api/v2/payments/init-payment' => Http::response([
                'payUrl' => 'https://pay.konnect.com/123',
                'paymentRef' => 'TXNREF123',
            ]),
        ]);

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $driver = new KonnectDriver;
        $result = $driver->initiate([
            'amount' => 50.00,
            'description' => 'Test Payment',
            'payment_reference' => 'PAYREF123',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://pay.konnect.com/123', $result['payment_url']);
        $this->assertEquals('TXNREF123', $result['gateway_transaction_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.sandbox.konnect.com.tn/api/v2/payments/init-payment';
        });
    }

    /**
     * Test Paymee payment initiation with mock response.
     */
    public function test_paymee_initiate_payment_success(): void
    {
        Http::fake([
            'https://api-sandbox.paymee.tn/api/v1/payment/create' => Http::response([
                'checkout_url' => 'https://checkout.paymee.tn/123',
                'payment_id' => 'PAY123',
            ]),
        ]);

        config([
            'payment.paymee.api_key' => 'test-key',
            'payment.paymee.api_secret' => 'test-secret',
            'payment.paymee.sandbox' => true,
        ]);

        $driver = new PaymeeDriver;
        $result = $driver->initiate([
            'amount' => 50.00,
            'description' => 'Test Payment',
            'payment_reference' => 'PAYREF123',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('https://checkout.paymee.tn/123', $result['payment_url']);
        $this->assertEquals('PAY123', $result['gateway_transaction_id']);
    }

    /**
     * Test Konnect payment verification.
     */
    public function test_konnect_verify_payment_success(): void
    {
        Http::fake([
            'https://api.sandbox.konnect.com.tn/api/v2/payments/TXNREF123' => Http::response([
                'status' => 'completed',
                'amount' => 50000, // millimes
                'paymentRef' => 'TXNREF123',
                'createdAt' => now()->toIso8601String(),
            ]),
        ]);

        config([
            'payment.konnect.api_key' => 'test-key',
            'payment.konnect.api_secret' => 'test-secret',
            'payment.konnect.sandbox' => true,
        ]);

        $driver = new KonnectDriver;
        $result = $driver->verify('TXNREF123');

        $this->assertTrue($result['success']);
        $this->assertEquals('completed', $result['status']);
        $this->assertEquals(50.0, $result['amount']);
    }

    /**
     * Test Paymee payment verification.
     */
    public function test_paymee_verify_payment_success(): void
    {
        Http::fake([
            'https://api-sandbox.paymee.tn/api/v1/payment/PAY123' => Http::response([
                'status' => 'paid',
                'amount' => 50.0,
                'payment_id' => 'PAY123',
                'created_at' => now()->toIso8601String(),
            ]),
        ]);

        config([
            'payment.paymee.api_key' => 'test-key',
            'payment.paymee.api_secret' => 'test-secret',
            'payment.paymee.sandbox' => true,
        ]);

        $driver = new PaymeeDriver;
        $result = $driver->verify('PAY123');

        $this->assertTrue($result['success']);
        $this->assertEquals('paid', $result['status']);
        $this->assertEquals(50.0, $result['amount']);
    }

    /**
     * Test Konnect refund.
     */
    public function test_konnect_refund_success(): void
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

        $driver = new KonnectDriver;
        $result = $driver->refund('TXNREF123', 25.0);

        $this->assertTrue($result['success']);
        $this->assertEquals('REFUND123', $result['refund_id']);
    }

    /**
     * Test invalid driver error.
     */
    public function test_invalid_driver_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->manager->driver('invalid-driver');
    }

    /**
     * Test payment initiation fails without credentials.
     */
    public function test_payment_initiation_fails_without_credentials(): void
    {
        config([
            'payment.konnect.api_key' => null,
            'payment.konnect.api_secret' => null,
        ]);

        $driver = new KonnectDriver;
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Konnect API credentials not configured');

        $driver->initiate([
            'amount' => 50.0,
            'description' => 'Test',
            'payment_reference' => 'REF',
            'success_url' => 'https://example.com/success',
            'failure_url' => 'https://example.com/failure',
        ]);
    }
}
