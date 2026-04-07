<?php

use App\Livewire\Admin\Plans\PlanTable;
use App\Models\Plan;
use App\Models\User;
use Livewire\Livewire;

test('plan table can search by plan name', function () {
    $this->actingAs(User::factory()->manager()->create());

    Plan::factory()->create(['name' => 'Alpha Recovery Plan']);
    Plan::factory()->create(['name' => 'Beta Strength Plan']);

    Livewire::test(PlanTable::class)
        ->set('search', 'Alpha')
        ->assertSee('Alpha Recovery Plan')
        ->assertDontSee('Beta Strength Plan');
});

test('plan table can filter archived plans', function () {
    $this->actingAs(User::factory()->manager()->create());

    Plan::factory()->create(['name' => 'Visible Active Plan', 'is_archived' => false]);
    Plan::factory()->create(['name' => 'Visible Archived Plan', 'is_archived' => true]);

    Livewire::test(PlanTable::class)
        ->set('statusFilter', 'archived')
        ->assertSee('Visible Archived Plan')
        ->assertDontSee('Visible Active Plan');
});

test('plan table toggles sorting direction on repeated column sort', function () {
    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(PlanTable::class)
        ->assertSet('sortBy', 'name')
        ->assertSet('sortDirection', 'asc')
        ->call('sort', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sort', 'price')
        ->assertSet('sortBy', 'price')
        ->assertSet('sortDirection', 'asc');
});

test('plan table hides create action for manager and shows it for admin', function () {
    Plan::factory()->create(['name' => 'Sample Plan']);

    $this->actingAs(User::factory()->manager()->create());

    Livewire::test(PlanTable::class)
        ->assertDontSee('Create Plan')
        ->assertDontSee('Edit');

    $this->actingAs(User::factory()->admin()->create());

    Livewire::test(PlanTable::class)
        ->assertSee('Create Plan')
        ->assertSee('Edit');
});

test('plan can be saved with specific courses', function () {
    $admin = User::factory()->admin()->create();
    
    $course1 = \App\Models\Course::factory()->create();
    $course2 = \App\Models\Course::factory()->create();

    $this->actingAs($admin);
    Livewire::test(PlanTable::class)
        ->set('name', 'Boxing Package')
        ->set('price', '150.000')
        ->set('durationDays', 30)
        ->set('hasAllCourses', false)
        ->set('selectedCourses', [(string) $course1->id, (string) $course2->id])
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('plans', [
        'name' => 'Boxing Package',
        'has_all_courses' => false,
    ]);

    $plan = \App\Models\Plan::where('name', 'Boxing Package')->first();
    
    expect($plan->courses)->toHaveCount(2)
        ->and($plan->courses->pluck('id')->toArray())->toContain($course1->id, $course2->id);
});

test('plan can be all inclusive', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);
    Livewire::test(PlanTable::class)
        ->set('name', 'V.I.P Gym & All Class Access')
        ->set('price', '500.000')
        ->set('durationDays', 365)
        ->set('hasAllCourses', true)
        ->call('save')
        ->assertDispatched('toast');

    $this->assertDatabaseHas('plans', [
        'name' => 'V.I.P Gym & All Class Access',
        'has_all_courses' => true,
    ]);

    $plan = \App\Models\Plan::where('name', 'V.I.P Gym & All Class Access')->first();
    expect($plan->courses)->toHaveCount(0); // Uses boolean flag
});
