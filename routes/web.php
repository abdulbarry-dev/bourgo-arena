<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::redirect('dashboard', 'admin');

Route::get('/member/onboarding-password/{token}', function (string $token) {
    return view('livewire.auth.member-onboarding-password-page', [
        'token' => $token,
    ]);
})->name('member.onboarding-password');

Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin,manager'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
