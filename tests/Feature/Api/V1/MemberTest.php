<?php

/** @var \Tests\TestCase $this */

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

test('unauthenticated profile request returns 401', function () {
    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertUnauthorized();
});

test('authenticated returns correct field names', function () {
    $member = Member::factory()->create([
        'status' => 'active',
        'date_of_birth' => '1990-01-01',
        'avatar' => 'avatars/test.png',
    ]);

    Sanctum::actingAs($member, ['*'], 'api');

    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'name',
                'email',
                'birth_date',
                'avatar_url',
            ],
        ])
        ->assertJsonPath('data.birth_date', '1990-01-01')
        ->assertJsonFragment(['avatar_url' => asset('storage/avatars/test.png')]);
});
