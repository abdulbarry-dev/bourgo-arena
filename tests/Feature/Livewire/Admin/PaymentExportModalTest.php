<?php

use App\Livewire\Admin\Payments\AuditLogs;
use App\Livewire\Admin\Payments\ReconciliationManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use App\UserRole;
use Livewire\Livewire;

it('opens and closes the audit export modal with livewire state', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(AuditLogs::class)
        ->call('openExportConfirmModal')
        ->assertSet('showExportConfirmModal', true)
        ->assertSee('Confirm export')
        ->assertSee('Start export')
        ->call('closeExportConfirmModal')
        ->assertSet('showExportConfirmModal', false);
});

it('opens the reconciliation export modal with entangled state', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create([
        'activity_id' => $activity->id,
        'date' => now()->toDateString(),
    ]);
    $payment = Payment::factory()->create(['member_id' => $member->id]);

    PaymentReconciliation::query()->create([
        'payment_id' => $payment->id,
        'admin_id' => $admin->id,
        'type' => 'reconciled',
        'metadata' => ['provider' => 'test'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ReconciliationManager::class)
        ->call('openExportConfirmModal', 'csv')
        ->assertSet('showExportConfirmModal', true)
        ->assertSet('exportFormat', 'csv')
        ->assertSee('Confirm export')
        ->assertSee('Start export')
        ->call('closeExportConfirmModal')
        ->assertSet('showExportConfirmModal', false);
});
