<?php

namespace App\Providers;

use App\Services\PaymentGateway\PaymentGatewayManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(PaymentGatewayManager::class, function ($app) {
            return new PaymentGatewayManager(
                config('payment.driver', 'konnect')
            );
        });

        // Register alias
        $this->app->alias(
            PaymentGatewayManager::class,
            'payment'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
