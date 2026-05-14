<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can register and needs dual verification', function () {
    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+33612345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.state', 'pending_verification')
        ->assertJsonPath('data.verification_status.email_verified', false)
        ->assertJsonPath('data.verification_status.phone_verified', false);

    $member = Member::where('email', 'john@example.com')->first();
    expect($member->email_verified_at)->toBeNull();
    expect($member->phone_verified_at)->toBeNull();
});

test('verifying one method transitions to pending_additional_verification', function () {
    $member = Member::factory()->create([
        'email' => 'john@example.com',
        'phone' => '+33612345678',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'status' => 'pending_verification',
    ]);

    Sanctum::actingAs($member, ['verification']);

    $member->update([
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.verify-email'), [
        'email' => 'john@example.com',
        'otp' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.state', 'pending_additional_verification')
        ->assertJsonPath('data.verification_status.email_verified', true)
        ->assertJsonPath('data.verification_status.phone_verified', false);

    $member->refresh();
    expect($member->email_verified_at)->not->toBeNull();
    expect($member->status)->toBe('pending_additional_verification');
});

test('verifying second method transitions to pending_onboarding', function () {
    $member = Member::factory()->create([
        'email' => 'john@example.com',
        'phone' => '+33612345678',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'status' => 'pending_additional_verification',
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['verification']);

    $member->update([
        'otp_code' => '123456',
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.verify-phone'), [
        'phone' => '+33612345678',
        'otp' => '123456',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.state', 'pending_onboarding')
        ->assertJsonPath('data.verification_status.phone_verified', true);

    $member->refresh();
    expect($member->phone_verified_at)->not->toBeNull();
    expect($member->status)->toBe('pending_onboarding');
});

test('middleware blocks access until fully verified', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'status' => 'pending_additional_verification',
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->getJson(route('api.v1.user.profile'));

    $response->assertStatus(403)
        ->assertJsonPath('code', 'ADDITIONAL_VERIFICATION_REQUIRED')
        ->assertJsonPath('state', 'pending_additional_verification');
});

test('verification status returns correct data', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => null,
    ]);

    Sanctum::actingAs($member, ['verification']);

    $response = $this->getJson(route('api.v1.user.verification-status'));

    $response->assertStatus(200)
        ->assertJsonPath('data.email_verified', true)
        ->assertJsonPath('data.phone_verified', false);
});
