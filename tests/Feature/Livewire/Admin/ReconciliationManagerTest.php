<?php

use App\Livewire\Admin\Payments\ReconciliationManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders reconciliation manager and lists items', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'date' => now()->toDateString()]);
    $payment = Payment::factory()->create(['member_id' => $member->id]);

    PaymentReconciliation::query()->create(['payment_id' => $payment->id, 'admin_id' => $admin->id, 'type' => 'reconciled', 'metadata' => ['provider' => 'test']]);

    $this->actingAs($admin);

    Livewire::test(ReconciliationManager::class)
        ->assertSee('Reconciliations')
        ->assertSee('Verified')
        ->assertSee('Export CSV')
        ->assertSee('Export PDF')
        ->assertDontSee('Queue Export')
        ->assertDontSee('Recent Exports');
});

it('can view archive and delete reconciliation records following business rules', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $member = Member::factory()->create();
    $payment = Payment::factory()->create([
        'member_id' => $member->id,
        'payment_reference' => 'PAY-REF-001',
        'status' => 'paid',
    ]);

    $reconciliation = PaymentReconciliation::query()->create([
        'payment_id' => $payment->id,
        'admin_id' => $admin->id,
        'type' => 'reconciled',
        'amount' => 25.500,
        'metadata' => ['provider' => 'konnect', 'status' => 'completed'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ReconciliationManager::class)
        ->call('openDetailModal', $reconciliation->id)
        ->assertSet('selectedReconciliationId', $reconciliation->id)
        ->assertSee('PAY-REF-001')
        ->assertSee('konnect')
        ->call('confirmArchive', $reconciliation->id)
        ->call('archiveReconciliation')
        ->assertSet('showArchiveConfirmModal', false);

    $reconciliation->refresh();

    expect($reconciliation->archived_at)->not->toBeNull();

    Livewire::test(ReconciliationManager::class)
        ->set('archiveFilter', 'active')
        ->assertDontSee('PAY-REF-001');

    Livewire::test(ReconciliationManager::class)
        ->set('archiveFilter', 'archived')
        ->assertSee('PAY-REF-001')
        ->call('confirmDelete', $reconciliation->id)
        ->call('deleteReconciliation')
        ->assertSet('showDeleteConfirmModal', false);

    $this->assertDatabaseMissing('payment_reconciliations', ['id' => $reconciliation->id]);
});

it('prevents deleting active reconciliation records', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::factory()->create(['member_id' => Member::factory()->create()->id]);

    $reconciliation = PaymentReconciliation::query()->create([
        'payment_id' => $payment->id,
        'admin_id' => $admin->id,
        'type' => 'refunded',
        'metadata' => ['provider' => 'test'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ReconciliationManager::class)
        ->call('confirmDelete', $reconciliation->id)
        ->assertDispatched('toast')
        ->assertSet('showDeleteConfirmModal', false)
        ->assertSet('deletingReconciliationId', null);

    $this->assertDatabaseHas('payment_reconciliations', ['id' => $reconciliation->id]);
});

it('can restore an archived reconciliation record', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::factory()->create(['member_id' => Member::factory()->create()->id]);

    $reconciliation = PaymentReconciliation::query()->create([
        'payment_id' => $payment->id,
        'admin_id' => $admin->id,
        'type' => 'reconciled',
        'archived_at' => now(),
        'metadata' => ['provider' => 'test'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ReconciliationManager::class)
        ->set('archiveFilter', 'archived')
        ->call('restoreReconciliation', $reconciliation->id);

    $reconciliation->refresh();

    expect($reconciliation->archived_at)->toBeNull();
});

it('downloads reconciliation csv and pdf exports', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $member = Member::factory()->create();
    $activity = Activity::factory()->create();
    $slot = ActivitySlot::factory()->create(['activity_id' => $activity->id, 'date' => now()->toDateString()]);
    $payment = Payment::factory()->create(['member_id' => $member->id]);

    PaymentReconciliation::query()->create(['payment_id' => $payment->id, 'admin_id' => $admin->id, 'type' => 'reconciled', 'metadata' => ['provider' => 'test']]);

    $this->actingAs($admin);

    $csvResponse = $this->get(route('admin.reconciliations.export.csv'));
    $csvResponse->assertOk();
    expect($csvResponse->headers->get('content-type'))->toContain('text/csv');
    $csvResponse->assertHeader('content-disposition');

    $pdfResponse = $this->get(route('admin.reconciliations.export.pdf'));
    $pdfResponse->assertOk();
    $pdfResponse->assertHeader('content-disposition');
});
