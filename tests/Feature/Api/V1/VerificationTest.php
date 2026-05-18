<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('verify email returns correct response structure', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
        'state' => 'pending_verification',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->postJson(route('api.v1.auth.verify-email'), [
        'email' => 'test@example.com',
        'otp' => '123456',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'valid' => true,
                'state' => 'pending_onboarding',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'state',
                'verification_status' => [
                    'email_verified',
                    'phone_verified',
                    'onboarding_completed',
                    'is_fully_verified',
                ],
            ],
        ]);
});

test('verify phone returns correct response structure', function () {
    $member = Member::factory()->create([
        'phone' => '1234567890',
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
        'state' => 'pending_additional_verification',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->postJson(route('api.v1.auth.verify-phone'), [
        'phone' => '1234567890',
        'otp' => '123456',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'valid' => true,
                'state' => 'pending_onboarding',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'state',
                'verification_status' => [
                    'email_verified',
                    'phone_verified',
                    'onboarding_completed',
                    'is_fully_verified',
                ],
            ],
        ]);
});

test('skip additional verification returns correct response structure', function () {
    $member = Member::factory()->create([
        'state' => 'pending_additional_verification',
        'email_verified_at' => now(),
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->postJson(route('api.v1.auth.skip-additional-verification'));

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_onboarding',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'state',
            ],
        ])
        ->assertJsonMissingPath('data.user'); // Ensure user is removed as per plan
});

test('verify otp returns correct response structure', function () {
    $member = Member::factory()->create([
        'email' => 'otp@example.com',
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
        'state' => 'pending_verification',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    $response = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'otp@example.com',
        'otp' => '123456',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'valid' => true,
                'state' => 'pending_onboarding',
            ],
        ])
        ->assertJsonStructure([
            'data' => [
                'token',
                'state',
                'user',
                'verification_status',
            ],
        ]);
});
