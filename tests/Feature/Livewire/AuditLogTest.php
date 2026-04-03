<?php

use App\Livewire\Admin\AccessControl\AuditLog;
use App\Models\CheckInEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the AuditLog securely', function () {
    Livewire::test(AuditLog::class)
        ->assertStatus(200);
});

it('can filter events', function () {
    $evAuth = CheckInEvent::factory()->create(['result' => 'authorized', 'card_uid' => 'AUTH123']);
    $evDeny = CheckInEvent::factory()->create(['result' => 'denied', 'card_uid' => 'DENY456']);

    Livewire::test(AuditLog::class)
        ->set('resultFilter', 'authorized')
        ->assertSee('AUTH123')
        ->assertDontSee('DENY456');
});
