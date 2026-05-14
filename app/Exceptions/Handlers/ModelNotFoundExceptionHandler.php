<?php

namespace App\Exceptions\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;

class ModelNotFoundExceptionHandler
{
    /**
     * Handle model not found exceptions (404 Not Found).
     *
     * Triggered when attempting to fetch a model by ID that doesn't exist.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found.',
                    'code' => 'MODEL_NOT_FOUND',
                ], 404);
            }
        });
    }
}
