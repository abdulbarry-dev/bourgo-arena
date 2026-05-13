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
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->configureRateLimiting();

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
