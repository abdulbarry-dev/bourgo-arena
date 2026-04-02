<?php

use App\Models\Member;
use App\Models\User;

test('admin can view member assign card page', function () {
    $member = Member::factory()->create(['name' => 'Assign Card Member']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.members.assign-card', $member))
        ->assertOk()
        ->assertSee('Assign NFC Card')
        ->assertSee('Assign Card Member');
});

test('manager can view member assign card page', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->manager()->create())
        ->get(route('admin.members.assign-card', $member))
        ->assertOk();
});

test('member role is forbidden from member assign card page', function () {
    $member = Member::factory()->create();

    $this->actingAs(User::factory()->member()->create())
        ->get(route('admin.members.assign-card', $member))
        ->assertForbidden();
});

test('guest is redirected to login from member assign card page', function () {
    $member = Member::factory()->create();

    $this->get(route('admin.members.assign-card', $member))
        ->assertRedirect('/login');
});
