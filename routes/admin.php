<?php

use App\Http\Controllers\Admin\PaymentAuditController;
use App\Http\Controllers\Admin\ReconciliationController;
use App\Livewire\Admin\Activities\ActivityManager;
use App\Livewire\Admin\Activities\ActivitySlotsManager;
use App\Livewire\Admin\Courses\CourseManager;
use App\Livewire\Admin\CourseSessions\CourseSessionManager;
use App\Livewire\Admin\Events\EventManager;
use App\Livewire\Admin\Managers\Index;
use App\Livewire\Admin\Payments\AuditLogs;
use App\Livewire\Admin\Payments\ReconciliationManager;
use App\Livewire\Admin\Reservations\ReservationManager;
use App\Models\Subscription;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------
// Routes accessible by BOTH Admins & Managers
// -------------------------------------------------------------
Route::middleware('role:admin,manager')->group(function () {

    Route::view('/members', 'livewire.admin.members.dashboard')
        ->name('admin.members');

    Route::get('/reservations', ReservationManager::class)
        ->name('admin.reservations.index');

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

    Route::get('/course-sessions', CourseSessionManager::class)
        ->name('admin.course-sessions.index');

    Route::get('/activities', ActivityManager::class)
        ->name('admin.activities.index');

    Route::get('/activities/{activity}/slots', ActivitySlotsManager::class)
        ->name('admin.activities.slots');
});

// -------------------------------------------------------------
// Routes accessible ONLY by Admins
// Retains the /admin prefix. E.g. /admin/plans, /admin/courses
// -------------------------------------------------------------
Route::prefix('admin')->middleware('role:admin')->group(function () {

    Route::get('/reconciliations', ReconciliationManager::class)
        ->name('admin.reconciliations.index');

    Route::get('/payments/audit', AuditLogs::class)
        ->name('admin.payments.audit');

    Route::get('/payments/audit/export', [PaymentAuditController::class, 'exportCsv'])
        ->name('admin.payments.audit.export');

    Route::get('/reconciliations/export/csv', [ReconciliationController::class, 'exportCsv'])
        ->name('admin.reconciliations.export.csv');
    Route::get('/reconciliations/export/pdf', [ReconciliationController::class, 'exportPdf'])
        ->name('admin.reconciliations.export.pdf');

    Route::view('/plans', 'livewire.admin.plans.dashboard')
        ->name('admin.plans');

    Route::get('/courses', CourseManager::class)
        ->name('admin.courses.index');

    Route::get('/managers', Index::class)
        ->name('admin.managers.index');

    Route::get('/events', EventManager::class)
        ->name('admin.events.index');

});
