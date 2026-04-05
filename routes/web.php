<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::redirect('dashboard', 'admin');

Route::get('/member/onboarding-password/{token}', function (string $token) {
    return view('livewire.auth.member-onboarding-password-page', [
        'token' => $token,
    ]);
})->name('member.onboarding-password');

Route::get('/lang/{locale}', function (string $locale) {
    if (in_array($locale, ['en', 'fr'])) {
        session(['locale' => $locale]);
    }

    return back();
})->name('lang.switch');

Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin,manager'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
