<?php

use App\Http\Controllers\Api\Admin\AdminAlertController;
use App\Http\Controllers\Api\Admin\AdminAuditLogController;
use App\Http\Controllers\Api\Admin\AdminCheckInController;
use App\Http\Controllers\Api\Admin\AdminMemberController;
use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Member\MemberActivityController;
use App\Http\Controllers\Api\Member\MemberDeviceTokenController;
use App\Http\Controllers\Api\Member\MemberNotificationController;
use App\Http\Controllers\Api\Member\MemberReservationController;
use App\Http\Controllers\Api\TerminalCheckInController;
use App\Http\Controllers\Api\TerminalProvisioningController;
use App\Http\Controllers\Api\V1\ActivityController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\FamilyController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ReservationController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('search', [SearchController::class, 'index'])->name('api.v1.search.index');
    Route::get('activities', [ActivityController::class, 'index'])->name('api.v1.activities.index');
    Route::get('activities/{activity}', [ActivityController::class, 'show'])->name('api.v1.activities.show');
    Route::get('activities/{activity}/slots', [ActivityController::class, 'slots'])->name('api.v1.activities.slots');
    Route::get('courses', [CourseController::class, 'index'])->name('api.v1.courses.index');

    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('api.v1.auth.register');
        Route::post('send-otp', [AuthController::class, 'sendOtp'])->name('api.v1.auth.send-otp');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->name('api.v1.auth.verify-otp');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
            Route::post('request-family-otp', [AuthController::class, 'requestFamilyOtp'])->name('api.v1.auth.request-family-otp');
            Route::patch('password/update', [AuthController::class, 'updatePassword'])->name('api.v1.auth.password.update');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('member/profile', [MemberController::class, 'profile'])->name('api.v1.member.profile');
        Route::put('member/profile', [MemberController::class, 'updateProfile'])->name('api.v1.member.update-profile');

        Route::get('reservations', [ReservationController::class, 'index'])->name('api.v1.reservations.index');
        Route::post('reservations', [ReservationController::class, 'store'])->name('api.v1.reservations.store');
        Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy'])->name('api.v1.reservations.destroy');

        Route::get('notifications', [NotificationController::class, 'index'])->name('api.v1.notifications.index');
        Route::post('notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('api.v1.notifications.mark-all-read');

        Route::get('family/children', [FamilyController::class, 'index'])->name('api.v1.family.children.index');
        Route::post('family/children', [FamilyController::class, 'store'])->name('api.v1.family.children.store');
        Route::delete('family/children/{member}', [FamilyController::class, 'destroy'])->name('api.v1.family.children.destroy');

        Route::post('device-token', [DeviceTokenController::class, 'store'])->name('api.v1.device-token.store');
    });
});

Route::post('auth/otp/request', [OtpAuthController::class, 'requestOtp'])->name('api.auth.otp.request');
Route::post('auth/otp/login', [OtpAuthController::class, 'login'])->name('api.auth.otp.login');

Route::middleware(['web', 'auth', 'verified', 'role:member'])

    ->prefix('member')
    ->group(function () {
        Route::get('me', function (Request $request) {
            $user = $request->user();

            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value ?? (string) $user->role,
            ]);
        })->name('api.member.me');

        Route::post('device-tokens', [MemberDeviceTokenController::class, 'store'])
            ->name('api.member.device-tokens.store');
        Route::delete('device-tokens/{token}', [MemberDeviceTokenController::class, 'destroy'])
            ->name('api.member.device-tokens.destroy');
        Route::get('notifications', [MemberNotificationController::class, 'index'])
            ->name('api.member.notifications.index');
    });

Route::middleware(['auth:sanctum', 'role:member'])
    ->prefix('member')
    ->group(function () {
        Route::get('activities', [MemberActivityController::class, 'index'])->name('api.member.activities.index');
        Route::get('activities/{id}', [MemberActivityController::class, 'show'])->name('api.member.activities.show');

        Route::get('reservations', [MemberReservationController::class, 'index'])->name('api.member.reservations.index');
        Route::post('reservations', [MemberReservationController::class, 'store'])->name('api.member.reservations.store');
        Route::post('reservations/{id}/cancel', [MemberReservationController::class, 'cancel'])->name('api.member.reservations.cancel');
    });

Route::middleware(['web', 'auth', 'verified', 'role:admin,manager'])
    ->prefix('admin')
    ->group(function () {
        // Dashboard & Monitoring
        Route::get('live-feed', [AdminCheckInController::class, 'live'])->name('api.admin.live-feed');
        Route::get('occupancy', [AdminCheckInController::class, 'occupancy'])->name('api.admin.occupancy');

        // Audit Logs
        Route::get('audit-logs', [AdminAuditLogController::class, 'index'])->name('api.admin.audit-logs.index');
        Route::get('members/{member}/audit-logs', [AdminAuditLogController::class, 'memberLogs'])->name('api.admin.members.audit-logs');

        // Alerts
        Route::get('alerts', [AdminAlertController::class, 'index'])->name('api.admin.alerts.index');
        Route::post('alerts/{alert}/dismiss', [AdminAlertController::class, 'dismiss'])->name('api.admin.alerts.dismiss');
        Route::post('alerts/{alert}/escalate', [AdminAlertController::class, 'escalate'])->name('api.admin.alerts.escalate');

        // Member Management
        Route::get('members', [AdminMemberController::class, 'index'])->name('api.admin.members.index');
        Route::get('members/{member}', [AdminMemberController::class, 'show'])->name('api.admin.members.show');
        Route::patch('members/{member}/status', [AdminMemberController::class, 'updateStatus'])->name('api.admin.members.update-status');
        Route::delete('members/{member}', [AdminMemberController::class, 'destroy'])->name('api.admin.members.destroy');

        // Terminals (Existing)
        Route::get('terminals', [TerminalProvisioningController::class, 'index'])->name('api.terminals.index');
        Route::post('terminal-provisioning', [TerminalProvisioningController::class, 'store'])->name('api.terminals.provision');
        Route::post('terminals/{terminal}/revoke-token', [TerminalProvisioningController::class, 'revokeToken'])->name('api.terminals.revoke-token');
        Route::delete('terminals/{terminal}', [TerminalProvisioningController::class, 'decommission'])->name('api.terminals.decommission');
    });

Route::middleware(['terminal.auth'])
    ->post('checkin', [TerminalCheckInController::class, 'store'])
    ->name('api.terminals.checkin');

Route::middleware(['terminal.auth'])
    ->post('terminals/{terminal}/heartbeat', [TerminalCheckInController::class, 'heartbeat'])
    ->name('api.terminals.heartbeat');
