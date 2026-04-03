<?php

use App\Livewire\Admin\AccessControl\AntiPassbackAlerts;
use App\Models\CheckInEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the AntiPassbackAlerts securely', function () {
    Livewire::test(AntiPassbackAlerts::class)
        ->assertStatus(200);
});

it('lists suspicious events and can dismiss them', function () {
    $event = CheckInEvent::factory()->create([
        'result' => 'denied',
        'is_suspicious' => true,
    ]);

    Livewire::test(AntiPassbackAlerts::class)
        ->assertSee($event->card_uid)
        ->call('dismissAlert', $event->id)
        ->assertDontSee($event->card_uid);

    expect($event->fresh()->is_suspicious)->toBeFalse();
});
