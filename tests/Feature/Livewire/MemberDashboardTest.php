<?php

use App\Models\User;

test('admin can view members dashboard page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.members'))
        ->assertOk()
        ->assertSee('h-dvh overflow-y-auto', false)
        ->assertSee('Members')
        ->assertSee('Add Member')
        ->assertSee('Search, filter, and manage member records');
});

test('manager can view members dashboard page', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.members'))
        ->assertOk();
});

test('member role is forbidden from members dashboard page', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.members'))
        ->assertNotFound();
});

test('guest is redirected to login from members dashboard page', function () {
    $this->get(route('admin.members'))
        ->assertRedirect('/login');
});
