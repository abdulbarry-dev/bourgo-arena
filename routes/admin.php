<?php

use App\Models\Member;
use App\Models\Subscription;
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

Route::view('/subscriptions/enroll', 'livewire.admin.subscriptions.enroll')
    ->role('admin', 'manager')
    ->name('admin.subscriptions.enroll');

Route::view('/subscriptions/expiring', 'livewire.admin.subscriptions.expiring')
    ->role('admin', 'manager')
    ->name('admin.subscriptions.expiring');

Route::get('/subscriptions/{subscription}', function (Subscription $subscription) {
    $subscription->load([
        'member',
        'plan',
        'auditLogs' => function ($query): void {
            $query->with('performedBy')->limit(8);
        },
    ]);

    return view('livewire.admin.subscriptions.detail', [
        'subscription' => $subscription,
    ]);
})
    ->role('admin', 'manager')
    ->name('admin.subscriptions.show');

Route::get('/subscriptions/{subscription}/actions', function (Subscription $subscription) {
    $subscription->load(['member', 'plan']);

    return view('livewire.admin.subscriptions.actions', [
        'subscription' => $subscription,
    ]);
})
    ->role('admin', 'manager')
    ->name('admin.subscriptions.actions');
