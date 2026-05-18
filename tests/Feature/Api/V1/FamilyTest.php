<?php

/** @var TestCase $this */

use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    /** @var TestCase $this */
    $this->member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($this->member, ['*'], 'sanctum');
});

test('add child creates member with correct parent_id', function () {
    /** @var TestCase $this */
    $response = $this->postJson(route('api.v1.family.children.store'), [
        'first_name' => 'Child',
        'last_name' => 'Name',
        'birth_date' => '2015-01-01',
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

test('update own child succeeds', function () {
    /** @var TestCase $this */
    $child = Member::factory()->create([
        'parent_id' => $this->member->id,
        'name' => 'Old Name',
        'date_of_birth' => '2016-01-01',
        'gender' => 'male',
        'status' => 'active',
    ]);

    $response = $this->putJson(route('api.v1.family.children.update', $child), [
        'first_name' => 'New',
        'last_name' => 'Name',
        'birth_date' => '2017-02-02',
        'gender' => 'female',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.gender', 'female')
        ->assertJsonPath('data.birth_date', '2017-02-02');

    $this->assertDatabaseHas('members', [
        'id' => $child->id,
        'parent_id' => $this->member->id,
        'name' => 'New Name',
        'gender' => 'female',
    ]);

    expect($child->fresh()->date_of_birth?->toDateString())->toBe('2017-02-02');
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

test('update another members child returns 403', function () {
    $otherParent = Member::factory()->create(['status' => 'active']);
    $otherChild = Member::factory()->create([
        'parent_id' => $otherParent->id,
        'status' => 'active',
    ]);

    $response = $this->putJson(route('api.v1.family.children.update', $otherChild), [
        'first_name' => 'Hacker',
        'last_name' => 'Attempt',
        'birth_date' => '2016-02-02',
        'gender' => 'male',
    ]);

    $response->assertForbidden();
});
