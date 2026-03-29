<?php

use App\Models\User;

test('guests can not access member api routes', function () {
    $response = $this->getJson('/api/member/me');

    $response->assertUnauthorized();
});

test('members can access member api routes', function () {
    $user = User::factory()->member()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/member/me');

    $response->assertSuccessful()
        ->assertJsonPath('id', $user->id)
        ->assertJsonPath('role', 'member');
});

test('admins are forbidden from member api routes', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/member/me');

    $response->assertForbidden();
});

test('managers are forbidden from member api routes', function () {
    $user = User::factory()->manager()->create();
    $this->actingAs($user);

    $response = $this->getJson('/api/member/me');

    $response->assertForbidden();
});
