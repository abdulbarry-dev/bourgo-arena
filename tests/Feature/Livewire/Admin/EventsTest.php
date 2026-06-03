<?php

use App\Livewire\Admin\Events\EventManager;
use App\Models\Event;
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

it('renders the event manager page with the locked dashboard shell', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.events.index'))
        ->assertOk()
        ->assertSee('h-dvh overflow-hidden', false)
        ->assertSee('Events Manager');
});

it('returns not found for the removed event participants page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create();

    $this->actingAs($admin)
        ->get('/admin/events/'.$event->id.'/participants')
        ->assertNotFound();
});

it('shows the empty state for the event list', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->assertSee('No events found');
});

it('shows row actions in a dropdown menu', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create(['name' => 'Winter Cup']);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->assertSee('Edit Event')
        ->assertSee(__('Open actions for :name', ['name' => 'Winter Cup']), false);
});

it('can create a new event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    $service = \App\Models\Service::factory()->create();

    Livewire::test(EventManager::class)
        ->set('name', 'Padel Championship')
        ->set('serviceId', $service->id)
        ->set('format', '1v1')
        ->set('max_participants', 16)
        ->set('registration_deadline', now()->subDay()->format('Y-m-d\TH:i'))
        ->set('start_date', now()->addDay()->format('Y-m-d\TH:i'))
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('events', [
        'name' => 'Padel Championship',
        'service_id' => $service->id,
    ]);
});
