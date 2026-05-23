<?php

namespace App\Providers;

use App\Services\PaymentGateway\KonnectGateway;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(KonnectGateway::class, function (): KonnectGateway {
            return new KonnectGateway;
        });

        $this->app->alias(KonnectGateway::class, 'payment');
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
