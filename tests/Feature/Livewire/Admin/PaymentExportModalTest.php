<?php

use App\Livewire\Admin\Payments\AuditLogs;
use App\Models\Payment;
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
        'payment_gateway' => 'konnect',
        'transaction_status' => 'success',
        'reservation_id' => null,
        'request_payload' => ['amount' => 12.345],
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
