<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('full mobile auth flow strictly follows state machine', function () {
    // 1. Registration
    $regResponse = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'State Machine User',
        'email' => 'state@example.com',
        'phone' => '12345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ]);

    $regResponse->assertStatus(201);
    $regResponse->assertJsonPath('data.state', 'pending_verification');

    $member = Member::where('email', 'state@example.com')->first();
    expect($member->status)->toBe('pending_verification');

    // 2. Login while unverified
    $loginResponse = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'state@example.com',
        'password' => 'password123',
    ]);

    $loginResponse->assertStatus(200);
    $loginResponse->assertJsonPath('data.code', 'EMAIL_NOT_VERIFIED');
    $loginResponse->assertJsonPath('data.state', 'pending_verification');

    // 3. OTP Verification
    $otp = '123456';
    $member->update([
        'otp_code' => $otp,
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $verifyResponse = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'state@example.com',
        'otp' => $otp,
    ]);

    $verifyResponse->assertStatus(200);
    $verifyResponse->assertJsonPath('data.state', 'pending_onboarding');
    $verifyResponse->assertJsonStructure(['data' => ['token']]);

    $token = $verifyResponse->json('data.token');
    $member->refresh();
    expect($member->status)->toBe('pending_onboarding');

    // 4. Access protected route with limited token
    $profileResponse = $this->withToken($token)->getJson(route('api.v1.member.profile'));
    $profileResponse->assertStatus(403);
    $profileResponse->assertJsonPath('state', 'pending_onboarding');

    // 5. Complete Onboarding
    $onboardResponse = $this->withToken($token)->postJson(route('api.v1.auth.complete-onboarding'), [
        'emergency_contact' => '123456789',
    ]);

    $onboardResponse->assertStatus(200);
    $onboardResponse->assertJsonPath('data.state', 'active');

    $member->refresh();
    expect($member->status)->toBe('active');
    expect($member->isOnboardingCompleted())->toBeTrue();
});

test('complete-registration endpoint also follows state machine', function () {
    $response = $this->postJson(route('api.v1.auth.complete-registration'), [
        'name' => 'Complete Reg User',
        'email' => 'complete_reg@example.com',
        'phone' => '11223344',
        'date_of_birth' => '1995-01-01',
        'gender' => 'female',
        'is_parent_account' => true,
    ]);

    $response->assertStatus(201);

    $member = Member::where('email', 'complete_reg@example.com')->first();
    expect($member->status)->toBe('active');
});
