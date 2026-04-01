<?php

use Illuminate\Support\Facades\Route;

Route::view('/members', 'livewire.admin.members.dashboard')
    ->role('admin', 'manager')
    ->name('admin.members');

Route::view('/subscriptions', 'livewire.admin.subscriptions.dashboard')
    ->role('admin', 'manager')
    ->name('admin.subscriptions');
