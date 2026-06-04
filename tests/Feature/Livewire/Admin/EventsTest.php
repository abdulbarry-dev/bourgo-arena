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

it('admin can cancel a draft or open event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create([
        'start_date' => now()->addDays(2),
        'end_date' => now()->addDays(3)
    ]); // draft status

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->call('openCancelModal', $event->id)
        ->assertSet('eventToCancel.id', $event->id)
        ->call('confirmCancel')->assertHasNoErrors();

    expect($event->fresh()->status)->toBe('canceled');
});

it('admin cannot cancel an in-progress or completed event', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create([
        'start_date' => now()->subDays(1),
        'end_date' => now()->addDays(1)
    ]); // in_progress status

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->call('openCancelModal', $event->id);

    expect($event->fresh()->status)->toBe('in_progress');
});

it('admin can delete an event by confirming the exact name', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create(['name' => 'Exact Match Name']);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->call('openDeleteModal', $event->id)
        ->set('deleteConfirmName', 'Exact Match Name')
        ->call('confirmDelete');

    $this->assertSoftDeleted('events', ['id' => $event->id]);
});

it('event deletion fails if confirmation name does not match', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $event = Event::factory()->create(['name' => 'Exact Match Name']);

    $this->actingAs($admin);

    Livewire::test(EventManager::class)
        ->call('openDeleteModal', $event->id)
        ->set('deleteConfirmName', 'Wrong Name')
        ->call('confirmDelete');

    $this->assertDatabaseHas('events', ['id' => $event->id, 'deleted_at' => null]);
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
        ->set('service_id', $service->id)
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
