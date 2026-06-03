<?php

use App\Livewire\Admin\Payments\AuditLogs;
use App\Livewire\Admin\Payments\ReconciliationManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\Member;
use App\Models\Payment;
use App\Models\PaymentReconciliation;
use App\Models\User;
use App\Services\PaymentAuditService;
use App\UserRole;
use Livewire\Livewire;

it('renders the payments audit page in the dashboard shell', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $payment = Payment::factory()->create();

    $this->actingAs($admin);

    app(PaymentAuditService::class)->logStandalone([
        'transaction_id' => 'audit_123',
        'user_id' => $admin->id,
        'amount' => 12.345,
        'currency' => 'TND',
        'payment_gateway' => 'konnect',
        'transaction_status' => 'success',
        'reservation_id' => null,
        'payment_id' => null,
        'request_payload' => ['amount' => 12.345, 'currency' => 'TND'],
        'response_payload' => ['payment_url' => 'https://gateway.example/pay'],
        'ip_address' => '10.10.10.10',
        'user_agent' => 'Pest-Test-Agent',
        'user_information' => [
            'id' => $admin->id,
            'name' => $admin->name,
            'email' => $admin->email,
        ],
    ]);

    Livewire::test(AuditLogs::class)
        ->assertSee('Payments Audit')
        ->assertSee('Export CSV')
        ->assertSee('User name or email')
        ->assertSee('Gateway')
        ->assertSee('Status')
        ->assertSee('audit_123')
        ->assertSee('konnect')
        ->assertSee('Success');
});

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
