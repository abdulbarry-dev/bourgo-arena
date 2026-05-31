<?php

use App\Models\Member;
use App\Models\User;

test('admin can open member detail flyout from members dashboard query', function () {
    $member = Member::factory()->create(['name' => 'Detail Page Member']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.members', ['member' => $member->id]))
        ->assertOk()
        ->assertSee('Members')
        ->assertSee('Detail Page Member');
});

test('manager can open member detail flyout from members dashboard query', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.members', ['member' => $member->id]))
        ->assertOk();
});

test('member role is forbidden from member detail flyout entry point', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.members', ['member' => $member->id]))
        ->assertForbidden();
});

test('guest is redirected to login from member detail flyout entry point', function () {
    $member = Member::factory()->create();

    $this->get(route('admin.members', ['member' => $member->id]))
        ->assertRedirect('/login');
});
