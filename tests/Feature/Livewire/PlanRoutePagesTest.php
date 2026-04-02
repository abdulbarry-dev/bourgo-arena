<?php

use App\Models\Plan;
use App\Models\User;

test('admin can view all plan pages', function () {
    $plan = Plan::factory()->create();

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.plans'))
        ->assertOk()
        ->assertSee('Plans');

    $this->actingAs($admin)
        ->get(route('admin.plans.create'))
        ->assertOk()
        ->assertSee('Create Plan');

    $this->actingAs($admin)
        ->get(route('admin.plans.show', $plan))
        ->assertOk()
        ->assertSee($plan->name);

    $this->actingAs($admin)
        ->get(route('admin.plans.edit', $plan))
        ->assertOk()
        ->assertSee('Edit Plan');
});

test('manager can view plans index and detail but cannot access create or edit pages', function () {
    $plan = Plan::factory()->create();

    $manager = User::factory()->manager()->create();

    $this->actingAs($manager)
        ->get(route('admin.plans'))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.plans.show', $plan))
        ->assertOk();

    $this->actingAs($manager)
        ->get(route('admin.plans.create'))
        ->assertForbidden();

    $this->actingAs($manager)
        ->get(route('admin.plans.edit', $plan))
        ->assertForbidden();
});

test('member role is forbidden from all plan pages', function () {
    $plan = Plan::factory()->create();

    $member = User::factory()->member()->create();

    $this->actingAs($member)
        ->get(route('admin.plans'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.plans.create'))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.plans.show', $plan))
        ->assertForbidden();

    $this->actingAs($member)
        ->get(route('admin.plans.edit', $plan))
        ->assertForbidden();
});

test('guest is redirected to login from plan pages', function () {
    $plan = Plan::factory()->create();

    $this->get(route('admin.plans'))
        ->assertRedirect('/login');

    $this->get(route('admin.plans.create'))
        ->assertRedirect('/login');

    $this->get(route('admin.plans.show', $plan))
        ->assertRedirect('/login');

    $this->get(route('admin.plans.edit', $plan))
        ->assertRedirect('/login');
});
