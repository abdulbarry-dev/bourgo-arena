<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/', 'login')->name('home');

Route::redirect('dashboard', 'admin');

Route::prefix('admin')->middleware(['auth', 'verified', 'role:admin,manager'])->group(function () {
    Route::view('/', 'dashboard')->name('dashboard');

    require __DIR__.'/admin.php';
});

require __DIR__.'/settings.php';
