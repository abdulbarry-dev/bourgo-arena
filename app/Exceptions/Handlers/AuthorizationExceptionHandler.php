<?php

namespace App\Exceptions\Handlers;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

class AuthorizationExceptionHandler
{
    /**
     * Handle authorization exceptions (403 Forbidden).
     *
     * Triggered when an authenticated user attempts an action they lack permission for.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'This action is unauthorized.',
                    'code' => 'FORBIDDEN',
                ], 403);
            }
        });
    }
}
