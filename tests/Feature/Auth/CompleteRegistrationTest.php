<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to complete registration', function () {
    $this->postJson('/api/v1/auth/complete-registration', [])->assertStatus(401);
});

it('validates required onboarding fields', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

    $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/auth/complete-registration', [])
        ->assertStatus(422)
        ->assertJsonStructure(['message', 'errors']);
});

it('completes onboarding and returns a full-access token', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    $token = $member->createToken('auth_token', ['onboarding'])->plainTextToken;

    $payload = [
        'name' => 'Jane Doe',
        'email' => $member->email,
        'phone' => $member->phone ?? '225555000',
        'date_of_birth' => '1990-01-01',
        'gender' => 'female',
        'is_parent_account' => false,
        'pin' => '1234',
    ];

    $response = $this->withHeaders(['Authorization' => "Bearer $token"])
        ->postJson('/api/v1/auth/complete-registration', $payload)
        ->assertStatus(201)
        ->assertJsonStructure(['data' => ['token', 'state', 'user', 'verification_status']]);

    expect($member->fresh()->onboarding_completed_at)->not->toBeNull();
    $responseData = $response->json('data');
    expect($responseData['state'])->toBe('active');
});
