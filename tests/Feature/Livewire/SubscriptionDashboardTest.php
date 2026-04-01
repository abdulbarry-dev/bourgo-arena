<?php

use App\Models\User;

test('admin can view subscriptions dashboard page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions'))
        ->assertOk()
        ->assertSee('Subscription Management')
        ->assertSee('Subscription Enrollment')
        ->assertSee('Expiring Subscriptions')
        ->assertSee('Member Detail');
});

test('manager can view subscriptions dashboard page', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.subscriptions'))
        ->assertOk();
});

test('member role is forbidden from subscriptions dashboard page', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.subscriptions'))
        ->assertForbidden();
});

test('guest is redirected to login from subscriptions dashboard page', function () {
    $this->get(route('admin.subscriptions'))
        ->assertRedirect('/login');
});
