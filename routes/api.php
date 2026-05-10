<?php

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
    ->group(function () {
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
