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
    $evAuth = CheckInEvent::factory()->create(['result' => 'authorized']);
    $evDeny = CheckInEvent::factory()->create(['result' => 'denied']);

    Livewire::test(AuditLog::class)
        ->set('resultFilter', 'authorized')
        ->assertSee($evAuth->member->email)
        ->assertDontSee($evDeny->member->email);
});
