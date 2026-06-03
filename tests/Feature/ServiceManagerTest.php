<?php

use App\Livewire\Admin\Services\ServiceManager;
use App\Models\Plan;
use App\Models\Service;
use Livewire\Livewire;

it('can view service details', function () {
    $service = Service::factory()->create(['name' => 'Gym']);

    Livewire::test(ServiceManager::class)
        ->call('openViewFlyout', $service->id)
        ->assertSet('showViewFlyout', true)
        ->assertSet('viewingService.id', $service->id)
        ->assertSee('Gym');
});

it('can archive a service', function () {
    $service = Service::factory()->create(['status' => 'active']);

    Livewire::test(ServiceManager::class)
        ->call('archive', $service->id)
        ->assertDispatched('toast', message: __('Service archived successfully.'));

    expect($service->fresh()->status)->toBe('archived')
        ->and($service->fresh()->archived_at)->not->toBeNull();
});

it('can delete a service with no offerings', function () {
    $service = Service::factory()->create();

    Livewire::test(ServiceManager::class)
        ->call('delete', $service->id)
        ->assertDispatched('toast', message: __('Service deleted successfully.'));

    expect(Service::count())->toBe(0);
});

it('cannot delete a service with offerings', function () {
    $service = Service::factory()->create();
    Plan::factory()->create(['service_id' => $service->id]);

    Livewire::test(ServiceManager::class)
        ->call('delete', $service->id)
        ->assertDispatched('toast', message: __('Cannot delete service with attached offerings. Please archive it instead.'));

    expect(Service::count())->toBe(1);
});

it('can restore an archived service', function () {
    $service = Service::factory()->archived()->create();

    Livewire::test(ServiceManager::class)
        ->call('restore', $service->id)
        ->assertDispatched('toast', message: __('Service restored to active status.'));

    expect($service->fresh()->status)->toBe('active')
        ->and($service->fresh()->archived_at)->toBeNull();
});

it('can filter services by status', function () {
    Service::factory()->count(2)->active()->create();
    Service::factory()->count(3)->archived()->create();

    Livewire::test(ServiceManager::class)
        ->set('statusFilter', 'archived')
        ->assertCount('services', 3)
        ->set('statusFilter', 'active')
        ->assertCount('services', 2)
        ->set('statusFilter', '')
        ->assertCount('services', 5);
});

it('can search services', function () {
    Service::factory()->create(['name' => 'Yoga Class']);
    Service::factory()->create(['name' => 'Weight Lifting']);

    Livewire::test(ServiceManager::class)
        ->set('search', 'Yoga')
        ->assertCount('services', 1)
        ->assertSee('Yoga Class')
        ->assertDontSee('Weight Lifting');
});
