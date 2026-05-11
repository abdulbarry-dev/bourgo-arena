<?php

/** @var TestCase $this */

use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create(['status' => 'active']);
    Sanctum::actingAs($this->member, ['*'], 'api');
});

test('add child creates member with correct parent_id', function () {
    /** @var TestCase $this */
    $response = $this->postJson(route('api.v1.family.children.store'), [
        'name' => 'Child Name',
        'date_of_birth' => '2015-01-01',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', 'Child Name');

    $this->assertDatabaseHas('members', [
        'parent_id' => $this->member->id,
        'name' => 'Child Name',
    ]);
});

test('delete own child succeeds', function () {
    /** @var TestCase $this */
    $child = Member::factory()->create([
        'parent_id' => $this->member->id,
        'status' => 'active',
    ]);

    $response = $this->deleteJson(route('api.v1.family.children.destroy', $child));

    $response->assertSuccessful();

    $this->assertSoftDeleted('members', ['id' => $child->id]);
});

test('delete another members child returns 403', function () {
    $otherParent = Member::factory()->create(['status' => 'active']);
    $otherChild = Member::factory()->create([
        'parent_id' => $otherParent->id,
        'status' => 'active',
    ]);

    $response = $this->deleteJson(route('api.v1.family.children.destroy', $otherChild));

    $response->assertForbidden();
});
