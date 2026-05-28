<?php

/** @var TestCase $this */

use App\Models\Member;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

test('unauthenticated profile request returns 401', function () {
    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertUnauthorized();
});

test('authenticated returns correct field names', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'date_of_birth' => '1990-01-01',
        'avatar' => 'avatars/test.png',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'first_name',
                'last_name',
                'email',
                'phone',
                'birth_date',
                'avatar_url',
                'loyalty_points',
                'is_parent_account',
            ],
        ])
        ->assertJsonPath('data.birth_date', '1990-01-01')
        ->assertJsonFragment(['avatar_url' => asset('storage/avatars/test.png')]);
});

test('user profile alias works', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.user.profile'));

    $response->assertSuccessful()
        ->assertJsonPath('data.id', (string) $member->id);
});
