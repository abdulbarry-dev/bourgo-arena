<?php

use App\Livewire\Admin\Analytics\Dashboard;
use Illuminate\Http\Request;
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

Route::get('/payments/mock-gateway', function (Request $request) {
    return view('payments.mock-gateway', [
        'description' => $request->query('description', 'Payment'),
        'amount' => (float) $request->query('amount', 0),
        'success_url' => $request->query('success_url'),
        'failure_url' => $request->query('failure_url'),
        'payment_id' => $request->query('payment_id'),
        'query_params' => collect($request->query())->except(['description', 'amount', 'success_url', 'failure_url', 'payment_id'])->toArray(),
    ]);
})->name('payments.mock-gateway');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware('role:admin,manager')
        ->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
