<?php

use App\Models\Member;
use App\Notifications\SendOtpCode;
use App\Services\Auth\AuthService;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('valid login returns token for active member', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'state' => 'active',
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'password123',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'success',
            'data' => [
                'token',
                'state',
                'user' => [
                    'id',
                    'name',
                    'email',
                ],
            ],
        ])
        ->assertJsonPath('data.state', 'active');
});

test('wrong password returns 401', function () {
    $member = Member::factory()->create([
        'email' => 'test@example.com',
        'password' => Hash::make('password123'),
        'state' => 'active',
    ]);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'test@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized();
});

test('duplicate email registration returns 422', function () {
    Member::factory()->create([
        'email' => 'duplicate@example.com',
    ]);

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'New User',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '12345678',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('member can register successfully and gets pending_verification state', function () {
    Notification::fake();

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '1234567890',
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
        ])
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
            ],
        ]);

    $this->assertDatabaseHas('members', [
        'email' => 'john@example.com',
        'state' => 'pending_verification',
    ]);

    $member = Member::where('email', 'john@example.com')->first();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class
    );
});

test('otp generation is deduplicated during a short resend window', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'email' => 'burst@example.com',
        'state' => 'pending_verification',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    $otpService = app(OtpService::class);

    $firstCode = $otpService->generate('burst@example.com');
    $secondCode = $otpService->generate('burst@example.com');

    expect($firstCode)->not->toBeEmpty();
    expect($secondCode)->toBe($firstCode);
    expect(Notification::sent($member, SendOtpCode::class)->count())->toBe(1);
});

test('logout revokes token', function () {
    $member = Member::factory()->create([
        'state' => 'active',
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.logout'));

    $response->assertSuccessful();
    expect($member->tokens()->count())->toBe(0);
});

test('OTP generate and verify flow', function () {
    Notification::fake();
    $member = Member::factory()->create([
        'email' => 'otp@example.com',
        'state' => 'pending_verification',
        'email_verified_at' => null,
        'onboarding_completed_at' => null,
    ]);

    // Send OTP
    $response = $this->postJson(route('api.v1.auth.send-otp'), [
        'identifier' => 'otp@example.com',
    ]);

    $response->assertSuccessful();

    $member->refresh();
    expect($member->otp_code)->not->toBeNull();

    // Verify OTP (simulating literal match for simplicity in test if not using Hash::check directly,
    // but here I can't easily get the plain code from DB because it's hashed.
    // I'll manually set a known OTP for verification test.)
    $plainOtp = '123456';
    $member->update([
        'otp_code' => $plainOtp, // Hashed via cast
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    $verifyResponse = $this->postJson(route('api.v1.auth.verify-otp'), [
        'identifier' => 'otp@example.com',
        'otp' => $plainOtp,
    ]);

    $verifyResponse->assertSuccessful()
        ->assertJson([
            'success' => true,
            'data' => [
                'valid' => true,
                'state' => 'pending_onboarding',
            ],
        ]);

    $member->refresh();
    expect($member->state)->toBe('pending_onboarding');
    expect($member->email_verified_at)->not->toBeNull();
});

test('member can complete registration through the complete-registration endpoint', function () {
    $member = Member::factory()->create([
        'state' => 'pending_onboarding',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.complete-registration'), [
        'name' => 'Complete User',
        'email' => 'complete@example.com',
        'phone' => '987654321',
        'date_of_birth' => '1995-05-05',
        'gender' => 'female',
        'is_parent_account' => true,
    ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('members', [
        'email' => 'complete@example.com',
        'state' => 'active',
    ]);
});

test('access history endpoint is no longer exposed', function () {
    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
        'status' => 'active',
    ]);

    Sanctum::actingAs($member, ['*'], 'sanctum');

    $this->getJson('/api/v1/user/access-history')->assertNotFound();
});

test('authenticated member can request family otp', function () {
    Notification::fake();
    $member = Member::factory()->create([
        'email' => 'family@example.com',
        'phone' => '11223344',
        'state' => 'active',
        'email_verified_at' => now(),
        'onboarding_completed_at' => now(),
    ]);
    Sanctum::actingAs($member, ['*'], 'sanctum');

    $response = $this->postJson(route('api.v1.auth.request-family-otp'));

    $response->assertSuccessful();

    $member->refresh();
    expect($member->otp_code)->not->toBeNull();
});

test('member can reset password using otp after verification', function () {
    Notification::fake();
    $member = Member::factory()->create([
        'email' => 'reset@example.com',
        'password' => Hash::make('old-password'),
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    // Request OTP
    $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => 'reset@example.com',
    ])->assertSuccessful();

    $member->refresh();
    $plainOtp = '654321';
    $member->update([
        'otp_code' => $plainOtp,
        'otp_expires_at' => now()->addMinutes(10),
    ]);

    // Reset Password
    $response = $this->postJson(route('api.v1.auth.reset-password'), [
        'identifier' => 'reset@example.com',
        'otp' => $plainOtp,
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ]);

    $response->assertSuccessful();

    $member->refresh();
    expect(Hash::check('new-password123', $member->password))->toBeTrue();
});

test('forgot password returns success even if user not found', function () {
    Notification::fake();
    $response = $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => 'nonexistent@example.com',
    ]);

    $response->assertSuccessful();
});

test('resetPasswordByOtp updates password hash', function () {
    $member = Member::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $authService = app(AuthService::class);

    $authService->resetPasswordByOtp($member, 'new-secure-password');
    $member->refresh();

    expect(Hash::check('new-secure-password', $member->password))->toBeTrue();
    expect(Hash::check('old-password', $member->password))->toBeFalse();
});
