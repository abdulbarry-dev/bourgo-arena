<?php

namespace App\Exceptions\Handlers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotFoundHttpExceptionHandler
{
    /**
     * Handle HTTP not found exceptions (404 Not Found).
     *
     * Triggered when an API endpoint doesn't exist or a route isn't found.
     * Distinguishes between a missing model vs. a missing endpoint.
     */
    public static function handle(Exceptions $exceptions): void
    {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getPrevious() instanceof ModelNotFoundException
                    ? 'Resource not found.'
                    : 'Endpoint not found.';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'code' => 'NOT_FOUND',
                ], 404);
            }
        });
    }
}
