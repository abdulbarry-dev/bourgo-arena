<?php

namespace App\Providers;

use App\Http\Middleware\EnsureAccountIsVerified;
use App\Http\Middleware\EnsureOnboardingIsCompleted;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TerminalAuthMiddleware;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\ServiceProvider;

class MiddlewareServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // This method is called by the middleware configuration closure in bootstrap/app.php
    }

    /**
     * Register and configure all application middleware.
     *
     * This method should be called from bootstrap/app.php's middleware configuration.
     */
    public static function registerMiddleware(Middleware $middleware): void
    {
        self::configureWebMiddleware($middleware);
        self::configureApiMiddleware($middleware);
        self::registerMiddlewareAliases($middleware);
    }

    /**
     * Configure middleware for web routes.
     */
    protected static function configureWebMiddleware(Middleware $middleware): void
    {
        $middleware->web(append: [
            EnsureUserIsNotBanned::class,
            SetLocale::class,
        ]);
    }

    /**
     * Configure middleware for API routes.
     */
    protected static function configureApiMiddleware(Middleware $middleware): void
    {
        $middleware->api(append: [
            ForceJsonResponse::class,
            SetLocale::class,
        ]);
    }

    /**
     * Register middleware aliases for convenient route middleware usage.
     */
    protected static function registerMiddlewareAliases(Middleware $middleware): void
    {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'terminal.auth' => TerminalAuthMiddleware::class,
            'verified.account' => EnsureAccountIsVerified::class,
            'onboarding.completed' => EnsureOnboardingIsCompleted::class,
        ]);
    }
}
