<?php

use App\Http\Controllers\Api\TerminalCheckInController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('search', [SearchController::class, 'index'])->name('api.v1.search.index');
    Route::get('activities', [ActivityController::class, 'index'])->name('api.v1.activities.index');
    Route::get('activities/{activity}', [ActivityController::class, 'show'])->name('api.v1.activities.show');
    Route::get('activities/{activity}/slots', [ActivityController::class, 'slots'])->name('api.v1.activities.slots');
    Route::get('courses', [CourseController::class, 'index'])->name('api.v1.courses.index');

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->middleware('throttle:api.auth')->name('api.v1.auth.login');
        Route::post('register', [AuthController::class, 'register'])->middleware('throttle:api.auth')->name('api.v1.auth.register');
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:api.otp')->name('api.v1.auth.send-otp');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:api.otp')->name('api.v1.auth.verify-otp');
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:api.otp')->name('api.v1.auth.forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:api.otp')->name('api.v1.auth.reset-password');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('request-family-otp', [AuthController::class, 'requestFamilyOtp'])->middleware('throttle:api.otp')->name('api.v1.auth.request-family-otp');
            Route::post('complete-registration', [AuthController::class, 'completeRegistration'])->middleware('throttle:api.auth')->name('api.v1.auth.complete-registration');
        });
    });

    Route::middleware(['auth:sanctum', 'verified.account', 'onboarding.completed'])->group(function () {
        Route::get('member/profile', [MemberController::class, 'profile'])->name('api.v1.member.profile');
        Route::put('member/profile', [MemberController::class, 'updateProfile'])->name('api.v1.member.update-profile');

        // User Aliases
        Route::prefix('user')->group(function () {
            Route::get('profile', [MemberController::class, 'profile'])->name('api.v1.user.profile');
            Route::put('profile', [MemberController::class, 'updateProfile'])->name('api.v1.user.update-profile');
            Route::put('password', [AuthController::class, 'updatePassword'])->middleware('throttle:api.password')->name('api.v1.user.update-password');
            Route::get('access-history', [MemberController::class, 'accessHistory'])->name('api.v1.user.access-history');
        });

        Route::get('reservations', [ReservationController::class, 'index'])->name('api.v1.reservations.index');
        Route::post('reservations', [ReservationController::class, 'store'])->name('api.v1.reservations.store');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('api.v1.reservations.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('api.v1.notifications.mark-all-read');

        Route::get('family/children', [FamilyController::class, 'index'])->name('api.v1.family.children.index');
        Route::post('family/children', [FamilyController::class, 'store'])->name('api.v1.family.children.store');
        Route::delete('family/children/{member}', [FamilyController::class, 'destroy'])->name('api.v1.family.children.destroy');

        Route::post('device-token', [DeviceTokenController::class, 'store'])->name('api.v1.device-token.store');

        Route::get('member/subscription', [SubscriptionController::class, 'active'])->name('api.v1.member.subscription');
    });
});

Route::middleware(['terminal.auth'])
    ->post('checkin', [TerminalCheckInController::class, 'store'])
    ->name('api.terminals.checkin');

Route::middleware(['terminal.auth'])
    ->post('terminals/{terminal}/heartbeat', [TerminalCheckInController::class, 'heartbeat'])
    ->name('api.terminals.heartbeat');
