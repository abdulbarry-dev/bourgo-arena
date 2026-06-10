<?php

use App\Http\Controllers\Admin\PaymentAuditController;
use App\Livewire\Admin\Activities\ActivityManager;
use App\Livewire\Admin\Activities\ActivitySessionManager;
use App\Livewire\Admin\Courses\CourseManager;
use App\Livewire\Admin\CourseSessions\CourseSessionManager;
use App\Livewire\Admin\Events\EventBracketManager;
use App\Livewire\Admin\Events\EventManager;
use App\Livewire\Admin\Events\EventParticipants;
use App\Livewire\Admin\Managers\Index;
use App\Livewire\Admin\Notifications\Dashboard as NotificationDashboard;
use App\Livewire\Admin\Payments\AuditLogs;
use App\Livewire\Admin\Reservations\ReservationManager;
use App\Livewire\Admin\Search\SearchResults;
use App\Livewire\Admin\Services\ServiceManager;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------
// Routes accessible by BOTH Admins & Managers
// -------------------------------------------------------------
Route::middleware('role:admin,manager')->group(function () {

    Route::get('/search', SearchResults::class)
        ->name('admin.search');

    Route::view('/members', 'livewire.admin.members.dashboard')
        ->name('admin.members');

    Route::get('/reservations', ReservationManager::class)
        ->name('admin.reservations.index');

    Route::view('/subscriptions', 'livewire.admin.subscriptions.dashboard')
        ->name('admin.subscriptions');

    Route::view('/subscriptions/expiring', 'livewire.admin.subscriptions.expiring')
        ->name('admin.subscriptions.expiring');

    Route::get('/course-sessions', CourseSessionManager::class)
        ->name('admin.course-sessions.index');

    Route::get('/activities', ActivityManager::class)
        ->name('admin.activities.index');

    Route::get('/activities/{activity}/sessions', ActivitySessionManager::class)
        ->name('admin.activities.sessions');
});

// -------------------------------------------------------------
// Routes accessible ONLY by Admins
// Retains the /admin prefix. E.g. /admin/plans, /admin/courses
// -------------------------------------------------------------
Route::prefix('admin')->middleware('role:admin')->group(function () {

    Route::get('/payments/audit', AuditLogs::class)
        ->name('admin.payments.audit');

    Route::get('/payments/audit/export', [PaymentAuditController::class, 'exportCsv'])
        ->name('admin.payments.audit.export');

    Route::view('/plans', 'livewire.admin.plans.dashboard')
        ->name('admin.plans');

    Route::get('/courses', CourseManager::class)
        ->name('admin.courses.index');

    Route::get('/managers', Index::class)
        ->name('admin.managers.index');

    Route::get('/events', EventManager::class)
        ->name('admin.events.index');

    Route::get('/events/{event}/participants', EventParticipants::class)
        ->name('admin.events.participants');

    Route::get('/events/{event}/bracket', EventBracketManager::class)
        ->name('admin.events.bracket');

    Route::get('/services', ServiceManager::class)
        ->name('admin.services.index');

    Route::get('/notifications', NotificationDashboard::class)
        ->name('admin.notifications');
});
