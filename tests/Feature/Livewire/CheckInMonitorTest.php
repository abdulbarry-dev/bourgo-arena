<?php

use App\Livewire\Admin\AccessControl\CheckInMonitor;
use App\Models\AdminAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the CheckInMonitor securely', function () {
    Livewire::test(CheckInMonitor::class)
        ->assertStatus(200);
});

it('calculates occupancy and alerts based on recent checkins', function () {
    // Mock Cache for occupancy
    $dateStr = now()->toDateString();
    Cache::shouldReceive('get')
        ->with("gym:occupancy:{$dateStr}", 0)
        ->andReturn(1);

    // Create an alert explicitly as the component loads them from the database
    AdminAlert::create([
        'alert_type' => 'HIGH_DENIAL_RATE',
        'description' => '1 denied events in the last 5 minutes',
        'is_dismissed' => false,
    ]);

    Livewire::test(CheckInMonitor::class)
        ->assertSet('occupancyCount', 1)
        ->assertSet('alertCount', 1)
        ->assertSee('1 denied events in the last 5 minutes');
});

it('can acknowledge alerts', function () {
    AdminAlert::create([
        'alert_type' => 'HIGH_DENIAL_RATE',
        'description' => 'Test alert',
        'is_dismissed' => false,
    ]);

    Livewire::test(CheckInMonitor::class)
        ->assertSet('alertCount', 1)
        ->call('acknowledgeAlert')
        ->assertSet('alertCount', 0);
});
