<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api.general', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

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

        RateLimiter::for('api.verify', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by($request->user()?->id ?: $request->ip()),
            ];
        });

        RateLimiter::for('api.data', function (Request $request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('api.loyalty', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('payments', function (Request $request) {
            $perMinute = (int) config('payment.initiate_per_minute', 10);
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($perMinute)->by($key);
        });

        RateLimiter::for('api.webhook', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });
    }
}
