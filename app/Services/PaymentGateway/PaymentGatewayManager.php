<?php

namespace App\Services\PaymentGateway;

use App\Services\PaymentGateway\Contracts\PaymentGatewayDriver;
use App\Services\PaymentGateway\Drivers\KonnectDriver;
use App\Services\PaymentGateway\Drivers\PaymeeDriver;
use InvalidArgumentException;

class PaymentGatewayManager
{
    private ?PaymentGatewayDriver $driver = null;

    private array $drivers = [
        'konnect' => KonnectDriver::class,
        'paymee' => PaymeeDriver::class,
    ];

    public function __construct(
        private string $defaultDriver = 'konnect'
    ) {}

    /**
     * Get the active payment gateway driver.
     */
    public function driver(?string $name = null): PaymentGatewayDriver
    {
        $driverName = $name ?? $this->defaultDriver ?? config('payment.driver', 'konnect');

        if (! isset($this->drivers[$driverName])) {
            throw new InvalidArgumentException("Payment gateway driver [$driverName] not found");
        }

        return $this->createDriver($driverName);
    }

    /**
     * Set the default driver.
     */
    public function setDefaultDriver(string $driver): self
    {
        if (! isset($this->drivers[$driver])) {
            throw new InvalidArgumentException("Payment gateway driver [$driver] not found");
        }

        $this->defaultDriver = $driver;

        return $this;
    }

    /**
     * Get all available drivers.
     */
    public function available(): array
    {
        return array_keys($this->drivers);
    }

    /**
     * Register a custom driver.
     */
    public function register(string $name, string $driverClass): self
    {
        $this->drivers[$name] = $driverClass;

        return $this;
    }

    /**
     * Create a driver instance.
     */
    private function createDriver(string $name): PaymentGatewayDriver
    {
        $driverClass = $this->drivers[$name];

        return app($driverClass);
    }

    /**
     * Initiate a payment with the default driver.
     */
    public function initiate(array $payload): array
    {
        return $this->driver()->initiate($payload);
    }

    /**
     * Verify a payment with the default driver.
     */
    public function verify(string $transactionId): array
    {
        return $this->driver()->verify($transactionId);
    }

    /**
     * Refund a payment with the default driver.
     */
    public function refund(string $transactionId, ?float $amount = null): array
    {
        return $this->driver()->refund($transactionId, $amount);
    }
}
