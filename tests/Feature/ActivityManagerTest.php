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

it('can create an activity', function () {
    Livewire::test(ActivityManager::class)
        ->call('openCreateFlyout')
        ->set('title', 'Test Activity')
        ->set('serviceId', $this->service->id)
        ->set('basePrice', '50.00')
        ->set('isActive', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast');

    $this->assertDatabaseHas('activities', [
        'title' => 'Test Activity',
        'service_id' => $this->service->id,
    ]);
});

it('can filter activities by service group', function () {
    $service1 = Service::factory()->create(['name' => 'Sport Group']);
    $service2 = Service::factory()->create(['name' => 'Fitness Group']);

    Activity::factory()->create(['title' => 'Sport Activity', 'service_id' => $service1->id]);
    Activity::factory()->create(['title' => 'Fitness Activity', 'service_id' => $service2->id]);

    Livewire::test(ActivityManager::class)
        ->set('serviceFilter', $service1->id)
        ->assertSee('Sport Activity')
        ->assertDontSee('Fitness Activity');
});
