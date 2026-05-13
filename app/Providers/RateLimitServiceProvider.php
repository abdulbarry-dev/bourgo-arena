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
        RateLimiter::for('api.auth', function (Request $request) {
            return [
                Limit::perMinute(5)->by($request->ip())->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many login or registration attempts. Please try again in :seconds seconds.', [
                            'seconds' => $headers['Retry-After'] ?? 60,
                        ]),
                    ], 429);
                }),
                Limit::perMinute(5)->by($request->input('email') ?: $request->input('phone') ?: $request->ip()),
            ];
        });

        RateLimiter::for('api.otp', function (Request $request) {
            return [
                Limit::perMinutes(5, 3)->by($request->ip())->response(function (Request $request, array $headers) {
                    return response()->json([
                        'success' => false,
                        'message' => __('Too many OTP attempts. For security, please wait :minutes minutes before trying again.', [
                            'minutes' => ceil(($headers['Retry-After'] ?? 300) / 60),
                        ]),
                    ], 429);
                }),
                Limit::perMinutes(5, 3)->by($request->input('identifier') ?: ($request->user()?->id ?? $request->ip())),
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
