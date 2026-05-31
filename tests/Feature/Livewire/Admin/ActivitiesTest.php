<?php

use App\Livewire\Admin\Activities\ActivityManager;
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

it('can create an activity and add a slot', function () {
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

    Livewire::test(ActivityManager::class)
        ->call('openDetailFlyout', $activity->id)
        ->set('slotDate', now()->addDay()->toDateString())
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

    Livewire::test(ActivityManager::class)
        ->call('toggleSlotAvailability', $slot->id)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'id' => $slot->id,
        'is_available' => false,
    ]);

    Livewire::test(ActivityManager::class)
        ->call('deleteSlot', $slot->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('activity_slots', ['id' => $slot->id]);

    // Recreate slot to test editing
    Livewire::test(ActivityManager::class)
        ->call('openDetailFlyout', $activity->id)
        ->set('slotDate', now()->addDays(2)->toDateString())
        ->set('slotStartsAt', '12:00')
        ->set('slotEndsAt', '13:00')
        ->set('slotCapacity', 2)
        ->set('slotIsAvailable', true)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $slot = ActivitySlot::query()->where('activity_id', $activity->id)->first();

    Livewire::test(ActivityManager::class)
        ->call('openDetailFlyout', $activity->id)
        ->call('openSlotEdit', $slot->id)
        ->set('slotCapacity', 6)
        ->call('saveSlot')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_slots', [
        'id' => $slot->id,
        'capacity' => 6,
    ]);
});

it('allows managers to access the activities manager and create courts', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);

    $this->actingAs($manager)
        ->get(route('admin.activities.index'))
        ->assertOk()
        ->assertSee('Activities & Courts');

    Livewire::test(ActivityManager::class)
        ->set('title', 'Stade Padel 2')
        ->set('category', 'padel')
        ->set('basePrice', '80.000')
        ->set('currency', 'TND')

        ->set('featuresInput', 'covered court, lights')
        ->set('description', 'Secondary padel court')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors();

    $activity = Activity::query()->where('title', 'Stade Padel 2')->firstOrFail();

    Livewire::test(ActivityManager::class)
        ->call('openDetailFlyout', $activity->id)
        ->set('slotDate', now()->addDay()->toDateString())
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
