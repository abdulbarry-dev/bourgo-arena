<?php

use App\Livewire\Admin\Activities\ActivityManager;
use App\Livewire\Admin\Activities\ActivitySessionManager;
use App\Livewire\Admin\Activities\CreateActivitySessionForm;
use App\Models\Activity;
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
        ->assertSee('Manage Sessions')
        ->assertDontSee('Save Slot')
        ->assertDontSee('Add Slot');
});

it('renders the dedicated sessions management page for an activity', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create(['title' => 'Court Beta']);

    $this->actingAs($admin)
        ->get(route('admin.activities.sessions', $activity))
        ->assertOk()
        ->assertSee('Court Beta')
        ->assertSee('New Session');
});

it('can open session create modal from the sessions page', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create([
        'title' => 'Court Gamma',
        'base_price' => 60,
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(ActivitySessionManager::class, ['activity' => $activity])
        ->assertSee('Weekly Activity Schedule')
        ->assertSee('Court Gamma')
        ->assertSee('New Session')
        ->assertSee($activity->title);
});

it('can create a session via the create form component', function () {
    $admin = User::factory()->create(['role' => UserRole::Admin]);
    $activity = Activity::factory()->create([
        'title' => 'Court Alpha',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateActivitySessionForm::class)
        ->set('activity_id', $activity->id)
        ->set('day_of_week', 1)
        ->set('starts_at', '09:00')
        ->set('duration_minutes', 60)
        ->set('starts_at_date', now()->toDateString())
        ->set('ends_at_date', now()->addYear()->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activity_sessions', [
        'activity_id' => $activity->id,
        'day_of_week' => 1,
        'starts_at' => '09:00:00',
        'duration_minutes' => 60,
    ]);
});

it('allows managers to access the activities manager and sessions page', function () {
    $manager = User::factory()->create(['role' => UserRole::Manager]);
    $activity = Activity::factory()->create(['title' => 'Stade Padel 2']);

    $this->actingAs($manager)
        ->get(route('admin.activities.index'))
        ->assertOk()
        ->assertSee('Activities & Courts');

    $this->actingAs($manager)
        ->get(route('admin.activities.sessions', $activity))
        ->assertOk()
        ->assertSee($activity->title);
});
