<?php

use App\Livewire\Admin\Activities\ActivityManager;
use App\Models\Activity;
use App\Models\Service;
use App\Models\User;
use App\UserRole;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => UserRole::Admin]);
    $this->service = Service::factory()->create(['status' => 'active']);
    $this->actingAs($this->user);
});

it('can create an activity with a category', function () {
    Livewire::test(ActivityManager::class)
        ->call('openCreateFlyout')
        ->set('title', 'Test Activity')
        ->set('category', 'Sport')
        ->set('serviceId', $this->service->id)
        ->set('basePrice', '50.00')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $this->assertDatabaseHas('activities', [
        'title' => 'Test Activity',
        'category' => 'Sport',
        'service_id' => $this->service->id,
    ]);
});

it('can filter activities by category', function () {
    Activity::factory()->create(['title' => 'Sport Activity', 'category' => 'Sport', 'service_id' => $this->service->id]);
    Activity::factory()->create(['title' => 'Fitness Activity', 'category' => 'Fitness', 'service_id' => $this->service->id]);

    Livewire::test(ActivityManager::class)
        ->set('categoryFilter', 'Sport')
        ->assertSee('Sport Activity')
        ->assertDontSee('Fitness Activity');
});
