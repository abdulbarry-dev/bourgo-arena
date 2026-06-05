<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Providers\KonnectProvider;
use App\Services\Payment\Providers\TestingProvider;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    /**
     * Get the payment driver instance.
     *
     * @param  string|null  $driver
     * @return PaymentGatewayInterface
     */
    public function driver($driver = null)
    {
        $driver = $driver ?: $this->getDefaultDriver();

        if ($this->shouldFallbackToTest($driver)) {
            $driver = 'test';
        }

        return parent::driver($driver);
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        $default = $this->config->get('payment.default', 'konnect');

        if ($this->shouldFallbackToTest($default)) {
            return 'test';
        }

        return $default;
    }

    /**
     * Determine if the given driver should fallback to the test driver.
     */
    protected function shouldFallbackToTest(string $driver): bool
    {
        if (app()->environment('production')) {
            return false;
        }

        if ($driver === 'konnect') {
            $apiKey = config('payment.providers.konnect.api_key') ?: config('payment.konnect.api_key');
            $apiSecret = config('payment.providers.konnect.api_secret') ?: config('payment.konnect.api_secret');

            return empty($apiKey) || empty($apiSecret);
        }

        return false;
    }

    /**
     * Create an instance of the Konnect driver.
     */
    public function createKonnectDriver(): PaymentGatewayInterface
    {
        return new KonnectProvider;
    }

    /**
     * Create an instance of the Testing driver.
     */
    public function createTestDriver(): PaymentGatewayInterface
    {
        return new TestingProvider;
    }
}
