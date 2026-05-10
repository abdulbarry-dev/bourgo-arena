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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
