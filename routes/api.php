<?php

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventParticipantController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\LoyaltyController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use App\Http\Controllers\Api\V1\TierController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('search', [SearchController::class, 'index'])->name('api.v1.search.index');
    Route::get('activities', [ActivityController::class, 'index'])->name('api.v1.activities.index');
    Route::get('activities/{activity}', [ActivityController::class, 'show'])->name('api.v1.activities.show');
    Route::get('activities/{activity}/slots', [ActivityController::class, 'slots'])->name('api.v1.activities.slots');
    Route::get('reservations/slots', [ActivityController::class, 'slots'])->name('api.v1.reservations.slots');
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
            Route::post('skip-additional-verification', [AuthController::class, 'skipAdditionalVerification'])->name('api.v1.auth.skip-additional-verification');
            Route::post('request-family-otp', [AuthController::class, 'requestFamilyOtp'])->middleware('throttle:api.otp')->name('api.v1.auth.request-family-otp');
            Route::post('complete-registration', [AuthController::class, 'completeRegistration'])->middleware(['verified.account', 'throttle:api.auth'])->name('api.v1.auth.complete-registration');
            Route::post('delete-account', [AuthController::class, 'deleteAccount'])->name('api.v1.auth.delete-account');
        });
    });

    Route::middleware('auth:sanctum')->prefix('user')->group(function () {
        Route::get('verification-status', [AuthController::class, 'verificationStatus'])->name('api.v1.user.verification-status');
        Route::post('verify-email', [AuthController::class, 'verifyEmail'])->name('api.v1.auth.verify-email');
        Route::post('verify-phone', [AuthController::class, 'verifyPhone'])->name('api.v1.auth.verify-phone');
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('api.v1.notifications.mark-all-read');
    });

    Route::middleware(['auth:sanctum', 'verified.account', 'onboarding.completed'])->group(function () {
        Route::get('member/profile', [MemberController::class, 'profile'])->name('api.v1.member.profile');
        Route::put('member/profile', [MemberController::class, 'updateProfile'])->name('api.v1.member.update-profile');

        Route::prefix('user')->group(function () {
            Route::get('profile', [MemberController::class, 'profile'])->name('api.v1.user.profile');
            Route::put('profile', [MemberController::class, 'updateProfile'])->name('api.v1.user.update-profile');
            Route::put('password', [AuthController::class, 'updatePassword'])->middleware('throttle:api.password')->name('api.v1.user.update-password');
        });

        Route::get('reservations', [ReservationController::class, 'index'])->name('api.v1.reservations.index');
        Route::post('reservations', [ReservationController::class, 'store'])->name('api.v1.reservations.store');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('api.v1.reservations.destroy');
        Route::post('reservations/{reservation}/payment/initiate', [ReservationController::class, 'initiatePayment'])->name('api.v1.reservations.payment.initiate');
        Route::get('reservations/{reservation}/payment/verify', [ReservationController::class, 'verifyPayment'])->name('api.v1.reservations.payment.verify');
        Route::delete('reservations/{reservation}/cancel', [ReservationController::class, 'cancelWithRefund'])->name('api.v1.reservations.cancel');

        Route::get('member/tier', [TierController::class, 'show'])->name('api.v1.member.tier');
        Route::get('loyalty/balance', [LoyaltyController::class, 'balance'])->name('api.v1.loyalty.balance');

        Route::prefix('family')->group(function () {
            Route::get('children', [FamilyController::class, 'index'])->name('api.v1.family.children.index');
            Route::get('members', [FamilyController::class, 'index'])->name('api.v1.family.members.index');
            Route::post('children', [FamilyController::class, 'store'])->name('api.v1.family.children.store');
            Route::post('members', [FamilyController::class, 'store'])->name('api.v1.family.members.store');
            Route::post('enable-feature', [FamilyController::class, 'enableFamilyFeature'])->name('api.v1.family.enable-feature');
            Route::post('disable-feature', [FamilyController::class, 'disableFamilyFeature'])->name('api.v1.family.disable-feature');
            Route::put('children/{member}', [FamilyController::class, 'update'])->name('api.v1.family.children.update');
            Route::put('members/{member}', [FamilyController::class, 'update'])->name('api.v1.family.members.update');
            Route::delete('children/{member}', [FamilyController::class, 'destroy'])->name('api.v1.family.children.destroy');
            Route::delete('members/{member}', [FamilyController::class, 'destroy'])->name('api.v1.family.members.destroy');
        });

        Route::post('device-token', [DeviceTokenController::class, 'store'])->name('api.v1.device-token.store');

        Route::get('member/subscription', [SubscriptionController::class, 'active'])->name('api.v1.member.subscription');
    });

    Route::prefix('payments')->group(function () {
        Route::post('initiate', [PaymentController::class, 'initiate'])->middleware('throttle:payments')->name('api.v1.payments.initiate');
        Route::post('verify', [PaymentController::class, 'verify'])->name('api.v1.payments.verify');
        Route::post('webhook/{provider}', [PaymentController::class, 'webhook'])->name('api.v1.payments.webhook');
    });
});

// Events API
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}', [EventController::class, 'show']);
Route::get('/events/{event}/bracket', [EventController::class, 'bracket']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user/events', [EventParticipantController::class, 'myEvents']);
    Route::post('/events/{event}/register', [EventParticipantController::class, 'register']);
    Route::post('/events/{event}/withdraw', [EventParticipantController::class, 'withdraw']);
    Route::post('/events/{event}/check-in', [EventParticipantController::class, 'checkIn']);
});
