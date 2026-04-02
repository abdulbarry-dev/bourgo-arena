<?php

use App\Models\Member;
use App\Models\User;

test('admin can view member detail page', function () {
    $member = Member::factory()->create(['name' => 'Detail Page Member']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.members.show', $member))
        ->assertOk()
        ->assertSee('Member Detail')
        ->assertSee('Detail Page Member');
});

test('manager can view member detail page', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.members.show', $member))
        ->assertOk();
});

test('member role is forbidden from member detail page', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.members.show', $member))
        ->assertForbidden();
});

test('guest is redirected to login from member detail page', function () {
    $member = Member::factory()->create();

    $this->get(route('admin.members.show', $member))
        ->assertRedirect('/login');
});
