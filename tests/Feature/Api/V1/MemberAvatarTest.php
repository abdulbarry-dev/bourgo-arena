<?php

/** @var TestCase $this */

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

beforeEach(function () {
    Storage::fake('public');
});

test('member can upload a profile avatar', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'avatar' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->post(route('api.v1.member.upload-avatar'), [
        'avatar' => UploadedFile::fake()->image('avatar.jpg', 400, 400),
    ], ['Accept' => 'application/json']);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $member->refresh();

    expect($member->avatar)->not->toBeNull()
        ->and($response->json('data.avatar_url'))->toContain('members/avatars');

    Storage::disk('public')->assertExists($member->avatar);
});

test('member can remove a profile avatar', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'avatar' => 'members/avatars/existing.jpg',
    ]);

    Storage::disk('public')->put('members/avatars/existing.jpg', 'fake-image');

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->deleteJson(route('api.v1.member.delete-avatar'))
        ->assertSuccessful()
        ->assertJsonPath('data.avatar_url', null);

    $member->refresh();

    expect($member->avatar)->toBeNull();
    Storage::disk('public')->assertMissing('members/avatars/existing.jpg');
});

test('profile returns null avatar url when member has no avatar', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'avatar' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->getJson(route('api.v1.member.profile'))
        ->assertSuccessful()
        ->assertJsonPath('data.avatar_url', null);
});

test('replacing avatar deletes the previous stored file', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'avatar' => 'members/avatars/old.jpg',
    ]);

    Storage::disk('public')->put('members/avatars/old.jpg', 'old-image');

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->post(route('api.v1.member.upload-avatar'), [
        'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
    ], ['Accept' => 'application/json'])->assertSuccessful();

    Storage::disk('public')->assertMissing('members/avatars/old.jpg');

    $member->refresh();

    Storage::disk('public')->assertExists($member->avatar);
});
