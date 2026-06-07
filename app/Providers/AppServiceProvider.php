<?php

namespace App\Providers;

use App\Events\EventCanceled;
use App\Events\EventDeleted;
use App\Listeners\HandleEventCancellation;
use App\Listeners\LogAdminAction;
use App\Models\ApiReservation;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->configureDefaults();
        $this->registerRateLimits();
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

    protected function registerRateLimits(): void
    {
        RateLimiter::for('payments', function (Request $request) {
            $perMinute = (int) config('payment.initiate_per_minute', 10);
            $key = optional($request->user())->id ?: $request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });
    }
}
