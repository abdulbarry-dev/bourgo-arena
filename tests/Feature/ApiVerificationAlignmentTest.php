<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can skip additional verification when in pending_additional_verification state', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'status' => 'pending_additional_verification',
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->postJson(route('api.v1.auth.skip-additional-verification'));

    $response->assertStatus(200)
        ->assertJsonPath('data.state', 'pending_onboarding')
        ->assertJsonStructure(['data' => ['token']]);

    $member->refresh();
    expect($member->status)->toBe('pending_onboarding');
});

test('skipping additional verification fails if already fully verified', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'status' => 'pending_onboarding',
    ]);

    Sanctum::actingAs($member, ['onboarding']);

    $response = $this->postJson(route('api.v1.auth.skip-additional-verification'));

    $response->assertStatus(403);
});

test('verifying email returns standardized response with valid true and new token', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
        'status' => 'pending_verification',
    ]);

    Sanctum::actingAs($member, ['verification']);

    // Mock OTP verification
    $member->update(['otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)]);

    $response = $this->postJson(route('api.v1.auth.verify-email'), [
        'email' => 'test@example.com',
        'otp' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.state', 'pending_onboarding')
        ->assertJsonStructure(['data' => ['token', 'verification_status']]);

    $member->refresh();
    expect($member->email_verified_at)->not->toBeNull();
});

test('verifying phone returns standardized response with valid true and new token', function () {
    $member = Member::factory()->create([
        'phone' => '+1234567890',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
        'status' => 'pending_additional_verification',
    ]);

    Sanctum::actingAs($member, ['verification']);

    // Mock OTP verification
    $member->update(['otp_code' => Hash::make('123456'), 'otp_expires_at' => now()->addMinutes(10)]);

    $response = $this->postJson(route('api.v1.auth.verify-phone'), [
        'phone' => '+1234567890',
        'otp' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.valid', true)
        ->assertJsonPath('data.state', 'pending_onboarding')
        ->assertJsonStructure(['data' => ['token', 'verification_status']]);

    $member->refresh();
    expect($member->phone_verified_at)->not->toBeNull();
    expect($member->status)->toBe('pending_onboarding');
});

test('middleware returns correct code and state for restricted access', function () {
    $member = Member::factory()->create([
        'onboarding_completed_at' => now(),
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'status' => 'pending_onboarding',
    ]);

    Sanctum::actingAs($member, ['verification']);

    // Test EnsureAccountIsVerified
    $response = $this->getJson(route('api.v1.user.profile'));
    $response->assertStatus(200);

    // Test EnsureOnboardingIsCompleted
    $member->update(['phone_verified_at' => now(), 'status' => 'pending_onboarding', 'onboarding_completed_at' => null]);
    Sanctum::actingAs($member, ['onboarding']);

    $response = $this->getJson(route('api.v1.user.profile'));
    $response->assertStatus(403)
        ->assertJsonPath('code', 'ONBOARDING_INCOMPLETE')
        ->assertJsonPath('state', 'pending_onboarding');
});
