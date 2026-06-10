<?php

use App\Channels\SmsChannel;
use App\Models\Member;
use App\Notifications\SendOtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ─── Config propagation ───

it('all config values propagate through the full chain', function () {
    expect(config('services.resend.key'))->toStartWith('re_');
    expect(config('services.twilio.account_sid'))->not->toBeNull();
    expect(config('services.twilio.auth_token'))->not->toBeNull();
    expect(config('services.twilio.from_number'))->toStartWith('+');
    expect((int) config('otp.expiry'))->toBe(10);
    expect((int) config('otp.length'))->toBe(6);
});

// ─── Registration → OTP Email ───

it('registration dispatches SendOtpCode via mail channel', function () {
    Notification::fake();

    $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Integration User',
        'email' => 'integration@example.com',
        'phone' => '99118822',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'date_of_birth' => '1990-01-01',
        'gender' => 'male',
    ])->assertStatus(201);

    $member = Member::where('email', 'integration@example.com')->first();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array('mail', $channels),
    );
});

// ─── Forgot password → OTP Email ───

it('forgot-password with email dispatches SendOtpCode via mail', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'email' => 'forgot@example.com',
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => 'forgot@example.com',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array('mail', $channels),
    );
});

// ─── Forgot password → OTP SMS ───

it('forgot-password with phone dispatches SendOtpCode via SmsChannel', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'phone' => '77665544',
        'email' => 'forgot-sms-e2e@example.com',
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => '77665544',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array(SmsChannel::class, $channels),
    );
});
