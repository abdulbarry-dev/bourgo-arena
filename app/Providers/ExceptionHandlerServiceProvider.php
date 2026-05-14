<?php

namespace App\Providers;

use App\Exceptions\Handlers\AuthenticationExceptionHandler;
use App\Exceptions\Handlers\AuthorizationExceptionHandler;
use App\Exceptions\Handlers\ModelNotFoundExceptionHandler;
use App\Exceptions\Handlers\NotFoundHttpExceptionHandler;
use App\Exceptions\Handlers\ThrottleRequestsExceptionHandler;
use App\Exceptions\Handlers\ValidationExceptionHandler;
use Illuminate\Foundation\Configuration\Exceptions;

class ExceptionHandlerServiceProvider
{
    /**
     * Register exception handlers for the application.
     *
     * This method orchestrates all exception handlers by delegating to
     * individual handler classes for better code organization and maintainability.
     *
     * This method should be called from bootstrap/app.php's exception configuration.
     */
    public static function registerExceptionHandlers(Exceptions $exceptions): void
    {
        AuthenticationExceptionHandler::handle($exceptions);
        AuthorizationExceptionHandler::handle($exceptions);
        ModelNotFoundExceptionHandler::handle($exceptions);
        ValidationExceptionHandler::handle($exceptions);
        ThrottleRequestsExceptionHandler::handle($exceptions);
        NotFoundHttpExceptionHandler::handle($exceptions);
    }
}
