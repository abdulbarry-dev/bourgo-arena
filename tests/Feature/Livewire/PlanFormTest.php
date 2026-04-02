<?php

use App\Livewire\Admin\Plans\PlanForm;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Livewire\Livewire;

test('admin can create a plan manually with custom services', function () {
    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PlanForm::class)
        ->set('name', 'Customizable Plan')
        ->set('price', '145.750')
        ->set('durationDays', 45)
        ->set('includedServicesInput', "gym\nboxing\npilates")
        ->set('isArchived', false)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect();

    $plan = Plan::query()->where('name', 'Customizable Plan')->first();

    expect($plan)->not->toBeNull();
    expect($plan?->price)->toBe('145.750');
    expect($plan?->duration_days)->toBe(45);
    expect($plan?->included_services)->toBe(['gym', 'boxing', 'pilates']);
    expect($plan?->is_archived)->toBeFalse();
});

test('manager cannot create plans through plan form', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(PlanForm::class)
        ->assertForbidden();
});

test('admin can update an existing plan', function () {
    $this->actingAs(User::factory()->admin()->create());

    $plan = Plan::factory()->create([
        'name' => 'Update Target Plan',
        'price' => 99.000,
        'duration_days' => 30,
        'included_services' => ['gym'],
    ]);

    Livewire::test(PlanForm::class, ['planId' => $plan->id])
        ->set('name', 'Updated Plan Name')
        ->set('price', '199.500')
        ->set('durationDays', 60)
        ->set('includedServicesInput', 'gym, squash')
        ->set('isArchived', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.plans.show', $plan));

    $plan->refresh();

    expect($plan->name)->toBe('Updated Plan Name');
    expect($plan->price)->toBe('199.500');
    expect($plan->duration_days)->toBe(60);
    expect($plan->included_services)->toBe(['gym', 'squash']);
    expect($plan->is_archived)->toBeTrue();
});

test('admin can delete a plan with no subscriptions linked', function () {
    $this->actingAs(User::factory()->admin()->create());

    $plan = Plan::factory()->create();

    Livewire::test(PlanForm::class, ['planId' => $plan->id])
        ->call('delete')
        ->assertRedirect(route('admin.plans'));

    $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
});

test('plan deletion is blocked when subscriptions are linked', function () {
    $this->actingAs(User::factory()->admin()->create());

    $plan = Plan::factory()->create();

    Subscription::factory()->create([
        'plan_id' => $plan->id,
    ]);

    Livewire::test(PlanForm::class, ['planId' => $plan->id])
        ->call('delete')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: 'Plan cannot be deleted because subscriptions are linked to it.', type: 'info');

    $this->assertDatabaseHas('plans', ['id' => $plan->id]);
});
