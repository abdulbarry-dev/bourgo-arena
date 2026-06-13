<?php

namespace App\Exceptions\Handlers;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;

class ThrottleRequestsExceptionHandler
{
    /**
     * Handle throttle requests exceptions (429 Too Many Requests).
     *
     * Triggered when a user exceeds rate limits. Includes retry-after duration,
     * remaining time calculation, and helpful guidance for different scenarios.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*')) {
                $headers = $e->getHeaders();
                $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                $minutes = ceil($retryAfter / 60);
                $seconds = $retryAfter % 60;

                $message = self::formatRateLimitMessage($request, $retryAfter, $minutes, $seconds);

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'retry_after_seconds' => $retryAfter,
                    'retry_after_minutes' => $minutes,
                    'hint' => 'For security, please wait before trying again to prevent account lockout.',
                ], 429, $headers);
            }
        });
    }

    /**
     * Format rate limit message based on the endpoint being accessed.
     *
     * @param  int  $retryAfter  Total seconds to wait
     * @param  int  $minutes  Minutes component
     * @param  int  $seconds  Seconds component
     */
    protected static function formatRateLimitMessage(Request $request, int $retryAfter, int $minutes, int $seconds): string
    {
        $timeFormat = match (true) {
            $minutes > 0 => __('Too many attempts. Please try again in :minutes minute(s) and :seconds second(s).', [
                'minutes' => $minutes,
                'seconds' => $seconds,
            ]),
            default => __('Too many attempts. Please try again in :seconds second(s).', [
                'seconds' => $retryAfter,
            ]),
        };

        // Endpoint-specific messaging
        if ($request->is('api/*/auth/login') || $request->is('api/*/auth/register')) {
            return __('Too many login or registration attempts. '.$timeFormat);
        }

        if ($request->is('api/*/auth/forgot-password') || $request->is('api/*/auth/reset-password') || $request->is('api/*/auth/send-otp')) {
            return __('Too many password reset or verification attempts. '.$timeFormat.' For your security, please check your email for a reset link.');
        }

        if ($request->is('api/*/auth/verify-otp')) {
            return __('Too many OTP verification attempts. '.$timeFormat.' A new code will be sent to you. Please check your email or phone.');
        }

        if ($request->is('api/*/user/verify-email') || $request->is('api/*/user/verify-phone')) {
            return __('Too many verification attempts. '.$timeFormat.' Please try again later.');
        }

        if ($request->is('api/*/payments/*')) {
            return __('Too many payment attempts. '.$timeFormat.' Please try again later or contact support if the issue persists.');
        }

        return __('Too many requests. '.$timeFormat);
    }
}
