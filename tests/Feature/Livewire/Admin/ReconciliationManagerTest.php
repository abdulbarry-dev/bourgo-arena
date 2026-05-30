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
