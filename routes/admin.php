<?php

use App\Livewire\Admin\Courses\CourseManager;
use App\Livewire\Admin\CourseSessions\CourseSessionManager;
use App\Livewire\Admin\Terminals\Create;
use App\Livewire\Admin\Terminals\Index;
use App\Models\Member;
use App\Models\Subscription;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------
// Routes accessible by BOTH Admins & Managers 
// -------------------------------------------------------------
Route::middleware('role:admin,manager')->group(function () {

    Route::view('/members', 'livewire.admin.members.dashboard')
        ->name('admin.members');

    Route::get('/members/{member}', function (Member $member) {
        return view('livewire.admin.members.detail', [
            'member' => $member,
        ]);
    })->name('admin.members.show');

    Route::get('/members/{member}/assign-card', function (Member $member) {
        return view('livewire.admin.members.assign-card', [
            'member' => $member,
        ]);
    })->name('admin.members.assign-card');

    Route::view('/subscriptions', 'livewire.admin.subscriptions.dashboard')
        ->name('admin.subscriptions');

    Route::view('/subscriptions/expiring', 'livewire.admin.subscriptions.expiring')
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
    })->name('admin.subscriptions.show');

    Route::get('/subscriptions/{subscription}/actions', function (Subscription $subscription) {
        $subscription->load(['member', 'plan']);

        return view('livewire.admin.subscriptions.actions', [
            'subscription' => $subscription,
        ]);
    })->name('admin.subscriptions.actions');

    Route::get('/course-sessions', CourseSessionManager::class)
        ->name('admin.course-sessions.index');
});

// -------------------------------------------------------------
// Routes accessible ONLY by Admins
// Retains the /admin prefix. E.g. /admin/plans, /admin/access-control
// -------------------------------------------------------------
Route::prefix('admin')->middleware('role:admin')->group(function () {

    Route::view('/plans', 'livewire.admin.plans.dashboard')
        ->name('admin.plans');

    Route::view('/access-control', 'livewire.admin.access-control.check-in-monitor-page')
        ->name('admin.access-control.dashboard');

    Route::view('/access-control/alerts', 'livewire.admin.access-control.anti-passback-alerts-page')
        ->name('admin.access-control.alerts');

    Route::view('/access-control/logs', 'livewire.admin.access-control.audit-logs-page')
        ->name('admin.access-control.logs');

    Route::get('/courses', CourseManager::class)
        ->name('admin.courses.index');

    Route::get('/terminals', Index::class)
        ->name('admin.terminals.index');

    Route::get('/terminals/create', Create::class)
        ->name('admin.terminals.create');

    Route::get('/managers', App\Livewire\Admin\Managers\Index::class)
        ->name('admin.managers.index');

});
