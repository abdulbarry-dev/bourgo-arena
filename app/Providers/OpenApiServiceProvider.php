<?php

namespace App\Providers;

use App\Support\Scramble\SchemaDefinitions;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;

class OpenApiServiceProvider extends ServiceProvider
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
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer')
                );

                // Register reusable components
                $openApi->components->addSchema('SuccessResponse', SchemaDefinitions::successResponse());
                $openApi->components->addSchema('ErrorResponse', SchemaDefinitions::errorResponse());
                $openApi->components->addSchema('ValidationErrorResponse', SchemaDefinitions::validationErrorResponse());
                $openApi->components->addSchema('PaginationMeta', SchemaDefinitions::paginationMeta());
                $openApi->components->addSchema('PaginationLinks', SchemaDefinitions::paginationLinks());
                $openApi->components->addSchema('AuthTokenResponse', SchemaDefinitions::authTokenResponse($openApi->components));
                $openApi->components->addSchema('User', SchemaDefinitions::user());
                $openApi->components->addSchema('Member', SchemaDefinitions::member($openApi->components));
                $openApi->components->addSchema('Reservation', SchemaDefinitions::reservation());
                $openApi->components->addSchema('Notification', SchemaDefinitions::notification());
                $openApi->components->addSchema('Subscription', SchemaDefinitions::subscription());
                $openApi->components->addSchema('Activity', SchemaDefinitions::activity());
                $openApi->components->addSchema('ActivitySlot', SchemaDefinitions::activitySlot());
                $openApi->components->addSchema('Course', SchemaDefinitions::course());
                $openApi->components->addSchema('SearchResult', SchemaDefinitions::searchResult());
                $openApi->components->addSchema('TerminalCheckIn', SchemaDefinitions::terminalCheckIn());
                $openApi->components->addSchema('TooManyRequestsResponse', SchemaDefinitions::tooManyRequestsResponse());
            });
    }
}
