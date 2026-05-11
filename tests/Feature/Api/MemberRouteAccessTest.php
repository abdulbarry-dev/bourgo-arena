<?php

use App\Models\Member;
use App\Models\User;

test('guests can not access member api routes', function () {
    $response = $this->getJson('/api/v1/member/profile');

    $response->assertUnauthorized();
});

test('members can access member api routes', function () {
    $member = Member::factory()->active()->create();
    $this->actingAs($member, 'api');

    $response = $this->getJson('/api/v1/member/profile');

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $member->id);
});

test('admins are forbidden from member api routes', function () {
    $user = User::factory()->admin()->create();
    $this->actingAs($user, 'api');

    $response = $this->getJson('/api/v1/member/profile');

    $response->assertForbidden();
});

test('managers are forbidden from member api routes', function () {
    $user = User::factory()->manager()->create();
    $this->actingAs($user, 'api');

    $response = $this->getJson('/api/v1/member/profile');

    $response->assertForbidden();
});
