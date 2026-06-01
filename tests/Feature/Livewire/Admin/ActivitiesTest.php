<?php

use App\Livewire\Admin\Activities\ActivityManager;
use App\Livewire\Admin\Activities\ActivitySlotsManager;
use App\Models\Activity;
use App\Models\ActivitySlot;
use App\Models\User;
use App\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the activities manager page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin)
        ->get(route('admin.activities.index'))
        ->assertOk()
        ->assertSee('h-dvh overflow-hidden', false)
        ->assertSee('Activities & Courts');
});

it('shows read-only court details in the view flyout without slot forms', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create([
        'title' => 'Court Alpha',
        'description' => 'Indoor padel court',
        'features' => ['lights', 'covered'],
    ]);

    $this->actingAs($admin);

    Livewire::test(ActivityManager::class)
        ->call('openDetailFlyout', $activity->id)
        ->assertSee('Court Alpha')
        ->assertSee('Indoor padel court')
        ->assertSee('Manage Slots')
        ->assertDontSee('Save Slot')
        ->assertDontSee('Add Slot');
});

it('renders the dedicated slots management page for an activity', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create(['title' => 'Court Beta']);

    $this->actingAs($admin)
        ->get(route('admin.activities.slots', $activity))
        ->assertOk()
        ->assertSee('Manage Slots')
        ->assertSee('Court Beta')
        ->assertSee('Add Slot');
});

it('opens the activity edit modal from the slots page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create([
        'title' => 'Court Gamma',
        'base_price' => 60,
        'currency' => 'TND',
        'description' => 'Initial description',
        'features' => ['lights', 'covered'],
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openEditActivityModal')
        ->assertSet('showActivityModal', true)
        ->assertSee('Edit Activity')
        ->assertSee('Court Gamma');

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openEditActivityModal')
        ->set('activityTitle', 'Court Gamma Updated')
        ->set('activityBasePrice', '72.50')
        ->set('activityDescription', 'Updated description')
        ->call('saveActivity')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activities', [
        'id' => $activity->id,
        'title' => 'Court Gamma Updated',
        'base_price' => 72.5,
        'description' => 'Updated description',
    ]);
});

it('can create an activity and manage slots on the slots page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);

    $this->actingAs($admin);

    Livewire::test(ActivityManager::class)
        ->set('title', 'Stade Padel 1')
        ->set('category', 'padel')
        ->set('basePrice', '75.000')
        ->set('currency', 'TND')
        ->set('featuresInput', 'covered court, lights')
        ->set('description', 'Main padel court')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $activity = Activity::query()->where('title', 'Stade Padel 1')->firstOrFail();

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openCreateSlotModal')
        ->set('slotStartsAt', '10:00')
        ->set('slotEndsAt', '11:00')
        ->set('slotCapacity', 4)
        ->set('slotIsAvailable', true)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'activity_id' => $activity->id,
        'capacity' => 4,
        'starts_at' => '10:00:00',
        'ends_at' => '11:00:00',
        'is_available' => true,
    ]);

    expect(ActivitySlot::query()->where('activity_id', $activity->id)->count())->toBe(1);

    $slot = ActivitySlot::query()->where('activity_id', $activity->id)->first();

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('toggleSlotAvailability', $slot->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'id' => $slot->id,
        'is_available' => false,
    ]);

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('deleteSlot', $slot->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('activity_slots', ['id' => $slot->id]);

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openCreateSlotModal')
        ->set('slotStartsAt', '12:00')
        ->set('slotEndsAt', '13:00')
        ->set('slotCapacity', 2)
        ->set('slotIsAvailable', true)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $slot = ActivitySlot::query()->where('activity_id', $activity->id)->first();

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openEditSlotModal', $slot->id)
        ->set('slotCapacity', 6)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'id' => $slot->id,
        'capacity' => 6,
    ]);
});

it('allows managers to access the activities manager and slots page', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $activity = Activity::factory()->create(['title' => 'Stade Padel 2']);

    $this->actingAs($manager)
        ->get(route('admin.activities.index'))
        ->assertOk()
        ->assertSee('Activities & Courts');

    $this->actingAs($manager)
        ->get(route('admin.activities.slots', $activity))
        ->assertOk()
        ->assertSee('Manage Slots');

    Livewire::test(ActivitySlotsManager::class, ['activity' => $activity])
        ->call('openCreateSlotModal')
        ->set('slotStartsAt', '12:00')
        ->set('slotEndsAt', '13:00')
        ->set('slotCapacity', 6)
        ->set('slotIsAvailable', true)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'activity_id' => $activity->id,
        'capacity' => 6,
        'starts_at' => '12:00:00',
        'ends_at' => '13:00:00',
        'is_available' => true,
    ]);
});
