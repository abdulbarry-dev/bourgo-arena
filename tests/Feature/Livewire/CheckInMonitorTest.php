<?php

use App\Livewire\Admin\AccessControl\CheckInMonitor;
use App\Models\CheckInEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the CheckInMonitor securely', function () {
    Livewire::test(CheckInMonitor::class)
        ->assertStatus(200);
});

it('calculates occupancy and alerts based on recent checkins', function () {
    // Create an authorized event
    CheckInEvent::factory()->create([
        'result' => 'authorized',
        'checked_in_at' => now(),
    ]);

    // Create a denied event
    CheckInEvent::factory()->create([
        'result' => 'denied',
        'checked_in_at' => now(),
    ]);

    Livewire::test(CheckInMonitor::class)
        ->assertSet('occupancyCount', 1)
        ->assertSet('alertCount', 1)
        ->assertSee('1 denied events in the last 5 minutes');
});

it('can acknowledge alerts', function () {
    CheckInEvent::factory()->create([
        'result' => 'denied',
        'checked_in_at' => now(),
    ]);

    Livewire::test(CheckInMonitor::class)
        ->assertSet('alertCount', 1)
        ->call('acknowledgeAlert')
        ->assertSet('alertCount', 0);
});
