<?php

use App\Livewire\Admin\CourseSessions\CourseSessionManager;
use App\Livewire\Admin\Terminals\Create;
use App\Livewire\Admin\Terminals\Index;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\Route;

Route::view('/members', 'livewire.admin.members.dashboard')
    ->role('admin', 'manager')
    ->name('admin.members');

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

Route::view('/plans', 'livewire.admin.plans.dashboard')
    ->role('admin', 'manager')
    ->name('admin.plans');

Route::view('/access-control', 'livewire.admin.access-control.check-in-monitor-page')
    ->role('admin', 'manager')
    ->name('admin.access-control.dashboard');

Route::view('/access-control/alerts', 'livewire.admin.access-control.anti-passback-alerts-page')
    ->role('admin', 'manager')
    ->name('admin.access-control.alerts');

Route::view('/access-control/logs', 'livewire.admin.access-control.audit-logs-page')
    ->role('admin', 'manager')
    ->name('admin.access-control.logs');

Route::get('/terminals', Index::class)
    ->role('admin')
    ->name('admin.terminals.index');

Route::get('/terminals/create', Create::class)
    ->role('admin')
    ->name('admin.terminals.create');

Route::get('/managers', App\Livewire\Admin\Managers\Index::class)
    ->role('admin')
    ->name('admin.managers.index');

Route::get('/courses', App\Livewire\Admin\Courses\CourseManager::class)
    ->role('admin', 'manager')
    ->name('admin.courses.index');

Route::get('/course-sessions', CourseSessionManager::class)
    ->role('admin', 'manager')
    ->name('admin.course-sessions.index');
