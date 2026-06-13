<?php

namespace App\Providers;

use App\Events\EventCanceled;
use App\Events\EventDeleted;
use App\Events\PaymentPaid;
use App\Listeners\HandleEventCancellation;
use App\Listeners\LogAdminAction;
use App\Listeners\ProcessSuccessfulPayment;
use App\Models\ApiReservation;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Relation::morphMap([
            'subscription' => Subscription::class,
            'reservation' => ApiReservation::class,
        ]);

        Event::listen(
            EventCanceled::class,
            LogAdminAction::class,
        );

        Event::listen(
            EventCanceled::class,
            HandleEventCancellation::class,
        );

        Event::listen(
            EventDeleted::class,
            LogAdminAction::class,
        );

        Event::listen(
            PaymentPaid::class,
            ProcessSuccessfulPayment::class,
        );
        $this->configureDefaults();
        $this->registerMacros();
    }

    protected function registerMacros(): void
    {
        Router::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });

        RouteRegistrar::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });

        Route::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(app()->isProduction());
    }
}
