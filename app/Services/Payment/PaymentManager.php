<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\Services\Payment\Providers\FlouciProvider;
use App\Services\Payment\Providers\KonnectProvider;
use Illuminate\Support\Manager;

class PaymentManager extends Manager
{
    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('payment.default', 'konnect');
    }

    /**
     * Create an instance of the Konnect driver.
     */
    public function createKonnectDriver(): PaymentProviderInterface
    {
        return new KonnectProvider;
    }

    /**
     * Create an instance of the Flouci driver.
     */
    public function createFlouciDriver(): PaymentProviderInterface
    {
        return new FlouciProvider;
    }
}
