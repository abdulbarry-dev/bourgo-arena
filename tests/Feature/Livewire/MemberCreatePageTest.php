<?php

use App\Models\User;

test('admin can view member create page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.members.create'))
        ->assertOk()
        ->assertSee('Add Member')
        ->assertSee('Create Member');
});

test('manager can view member create page', function () {
    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.members.create'))
        ->assertOk();
});

test('member role is forbidden from member create page', function () {
    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.members.create'))
        ->assertForbidden();
});

test('guest is redirected to login from member create page', function () {
    $this->get(route('admin.members.create'))
        ->assertRedirect('/login');
});
