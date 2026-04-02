<?php

use App\Models\Member;
use Illuminate\Support\Facades\Route;

Route::view('/members', 'livewire.admin.members.dashboard')
    ->role('admin', 'manager')
    ->name('admin.members');

Route::view('/members/create', 'livewire.admin.members.create')
    ->role('admin', 'manager')
    ->name('admin.members.create');

Route::get('/members/{member}', function (Member $member) {
    return view('livewire.admin.members.detail', [
        'member' => $member,
    ]);
})
    ->role('admin', 'manager')
    ->name('admin.members.show');

Route::get('/members/{member}/assign-card', function (Member $member) {
    return view('livewire.admin.members.assign-card', [
        'member' => $member,
    ]);
})
    ->role('admin', 'manager')
    ->name('admin.members.assign-card');

Route::view('/subscriptions', 'livewire.admin.subscriptions.dashboard')
    ->role('admin', 'manager')
    ->name('admin.subscriptions');
