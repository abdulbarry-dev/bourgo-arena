<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // TODO: Enable rate limiting on production
        // Temporarily disabled for development
        RateLimiter::for('api.auth', fn () => Limit::none());
        RateLimiter::for('api.otp', fn () => Limit::none());
        RateLimiter::for('api.password', fn () => Limit::none());

        if (app()->isProduction()) {
            $this->configureProductionRateLimiting();
        }
    }

    /**
     * Configure production rate limiters.
     *
     * These limits should be enforced only in production.
     */
    protected function configureProductionRateLimiting(): void
    {
        RateLimiter::for('api.auth', function (Request $request) {
            return [
                Limit::perMinute(10)->by($request->ip())->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many login or registration attempts. Please try again shortly.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after_seconds' => $headers['Retry-After'] ?? 60,
                    ], 429);
                }),
                Limit::perMinute(10)->by($request->input('email') ?: $request->input('phone') ?: $request->ip()),
            ];
        });

        RateLimiter::for('api.otp', function (Request $request) {
            return [
                Limit::perMinutes(10, 5)->by($request->ip())->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Too many OTP attempts. Please wait before trying again.',
                        'code' => 'RATE_LIMIT_EXCEEDED',
                        'retry_after_seconds' => $headers['Retry-After'] ?? 600,
                    ], 429);
                }),
                Limit::perMinutes(10, 5)->by($request->input('identifier') ?: ($request->input('email') ?: ($request->user()?->id ?? $request->ip()))),
            ];
        });

        RateLimiter::for('api.password', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
            ];
        });
    }
}
