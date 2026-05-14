<?php

namespace App\Exceptions\Handlers;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

class AuthenticationExceptionHandler
{
    /**
     * Handle authentication exceptions (401 Unauthenticated).
     *
     * Triggered when a user attempts to access a protected API endpoint without authentication.
     * Provides helpful context for mobile apps to guide users appropriately.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required. Please log in to continue.',
                    'code' => 'UNAUTHENTICATED',
                    'hint' => 'Your session has expired or you are not logged in. Please authenticate with your credentials.',
                ], 401);
            }
        });
    }
}
