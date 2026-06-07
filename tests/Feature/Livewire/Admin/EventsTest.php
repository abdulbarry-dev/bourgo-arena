<?php

use App\Livewire\Admin\Events\EventManager;
use App\Models\Service;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the event manager component', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->assertStatus(200);
});

it('can create a new event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $service = \App\Models\Service::factory()->create(['slug' => 'padel']);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->set('name', 'Padel Championship')
        ->set('service_id', $service->id)
        ->set('format', '1v1')
        ->set('max_participants', 16)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('events', [
        'name' => 'Padel Championship',
    ]);
});
