<?php

namespace App\Providers;

use App\Support\Scramble\SchemaDefinitions;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Router::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });

        RouteRegistrar::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });

        Route::macro('role', function (string ...$roles) {
            return $this->middleware('role:'.implode(',', $roles));
        });

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
            });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
