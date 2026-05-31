<?php

namespace App\Providers;

use App\Services\Payment\PaymentManager;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('payment', function ($app) {
            return new PaymentManager($app);
        });

        $this->app->singleton(PaymentManager::class, function ($app) {
            return $app->make('payment');
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
