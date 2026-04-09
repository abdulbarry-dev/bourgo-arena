<?php

use App\Livewire\Admin\Analytics\Dashboard;
use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::redirect('admin', 'dashboard');

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('role:admin,manager')
        ->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
