<?php

use App\Models\User;

test('admin can view subscriptions dashboard page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.subscriptions'))
        ->assertOk()
        ->assertSee('Subscriptions')
        ->assertSee('Enroll Subscription')
        ->assertSee('Expiring Subscriptions')
        ->assertSee('No subscriptions found');
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
