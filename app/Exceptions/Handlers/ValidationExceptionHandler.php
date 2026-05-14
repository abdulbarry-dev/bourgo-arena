<?php

namespace App\Exceptions\Handlers;

use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ValidationExceptionHandler
{
    /**
     * Handle validation exceptions (422 Unprocessable Entity).
     *
     * Triggered when form validation fails, includes detailed error messages.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'code' => 'VALIDATION_FAILED',
                    'errors' => $e->errors(),
                ], 422);
            }
        });
    }
}
