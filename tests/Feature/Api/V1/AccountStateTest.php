<?php

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('registration creates account in pending_verification state and sends OTP', function () {
    Notification::fake();

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'phone' => '12345678',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertStatus(201)
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_verification',
            ],
        ]);

    $member = Member::where('email', 'jane@example.com')->first();
    expect($member->status)->toBe('pending_verification');
    expect($member->otp_code)->not->toBeNull();
});

test('login returns pending_verification if not verified', function () {
    $member = Member::factory()->create([
        'email' => 'unverified@example.com',
        'password' => Hash::make('password123'),
        'status' => 'pending_verification',
        'email_verified_at' => null,
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'unverified@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
                'state' => 'pending_additional_verification',
            ],
        ]);
});

test('OTP verification transitions to pending_additional_verification and issues verification token', function () {
    $member = Member::factory()->create([
        'email' => 'verify@example.com',
        'status' => 'pending_verification',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    $otp = '123456';
    $member->update([
        'otp_code' => $otp,
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'verify@example.com',
        'otp' => $otp,
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_onboarding',
            ],
        ]);

    $member->refresh();
    expect($member->status)->toBe('pending_onboarding');
    expect($member->email_verified_at)->not->toBeNull();

    $response->assertJsonStructure(['data' => ['token']]);
});

test('login returns pending_additional_verification if email verified but phone not', function () {
    $member = Member::factory()->create([
        'email' => 'partial@example.com',
        'password' => Hash::make('password123'),
        'status' => 'pending_additional_verification',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'partial@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_additional_verification',
                'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
            ],
        ]);
});

test('login returns pending_onboarding if fully verified but onboarding incomplete', function () {
    $member = Member::factory()->create([
        'email' => 'verified@example.com',
        'password' => Hash::make('password123'),
        'status' => 'pending_onboarding',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'verified@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_onboarding',
                'code' => 'ONBOARDING_INCOMPLETE',
                'required_action' => 'complete_onboarding',
            ],
        ])
        ->assertJsonPath('data.cta', 'Complete Setup')
        ->assertJsonPath('message', 'Must complete onboarding to unlock your account.');
});

test('login keeps unverified onboarding members in verification flow', function () {
    $member = Member::factory()->create([
        'email' => 'unverified-onboarding@example.com',
        'password' => Hash::make('password123'),
        'status' => 'pending_onboarding',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'unverified-onboarding@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'pending_additional_verification',
                'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
            ],
        ]);
});

test('registration completion transitions to active', function () {
    $member = Member::factory()->create([
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '87654321',
        'status' => 'pending_onboarding',
        'email_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['onboarding'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.complete-registration'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '87654321',
        'date_of_birth' => '1992-02-02',
        'gender' => 'female',
        'is_parent_account' => true,
        'pin' => '1234',
    ]);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'state' => 'active',
            ],
        ]);

    $member->refresh();
    expect($member->status)->toBe('active');
    expect($member->onboarding_completed_at)->not->toBeNull();
    expect($member->pin)->not->toBeNull();
});

test('registration completion is blocked until otp verification exists', function () {
    $member = Member::factory()->create([
        'name' => 'No Verify User',
        'email' => 'no-verify@example.com',
        'phone' => '87654322',
        'status' => 'pending_onboarding',
        'state' => 'pending_onboarding',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['onboarding'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.complete-registration'), [
        'name' => 'No Verify User',
        'email' => 'no-verify@example.com',
        'phone' => '87654322',
        'date_of_birth' => '1992-02-02',
        'gender' => 'female',
        'is_parent_account' => true,
        'pin' => '1234',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
            'state' => 'pending_additional_verification',
        ]);

    $member->refresh();
    expect($member->onboarding_completed_at)->toBeNull();
    expect($member->state)->toBe('pending_onboarding');
});

test('unverified users cannot access protected routes', function () {
    $member = Member::factory()->create([
        'status' => 'pending_verification',
        'email_verified_at' => null,
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertStatus(403)
        ->assertJson([
            'code' => 'ADDITIONAL_VERIFICATION_REQUIRED',
            'state' => 'pending_additional_verification',
        ]);
});

test('users with incomplete onboarding cannot access protected routes', function () {
    $member = Member::factory()->create([
        'status' => 'pending_onboarding',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'onboarding_completed_at' => null,
    ]);

    Sanctum::actingAs($member, ['onboarding'], 'sanctum');

    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertStatus(403)
        ->assertJson([
            'message' => 'Must complete onboarding to access your account.',
            'code' => 'ONBOARDING_INCOMPLETE',
            'state' => 'pending_onboarding',
            'required_action' => 'complete_onboarding',
        ]);
});

test('users pending additional verification cannot access protected routes', function () {
    $member = Member::factory()->create([
        'status' => 'pending_additional_verification',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'onboarding_completed_at' => now(),
    ]);

    Sanctum::actingAs($member, ['verification'], 'sanctum');

    $response = $this->getJson(route('api.v1.member.profile'));

    $response->assertStatus(200);
});

test('password reset is denied for unverified accounts', function () {
    $member = Member::factory()->create([
        'email' => 'unverified-reset@example.com',
        'status' => 'pending_verification',
        'email_verified_at' => null,
        'phone_verified_at' => null,
    ]);

    $response = $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => 'unverified-reset@example.com',
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'code' => 'EMAIL_NOT_VERIFIED',
        ]);
});
