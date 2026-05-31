<?php

use App\Models\Member;
use App\Notifications\SendOtpCode;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    Notification::fake();
});

it('sends family otp to email if explicitly requested and verified', function () {
    $member = Member::factory()->create([
        'email' => 'email-choice@example.com',
        'phone' => '11111111',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'state' => 'active',
        'status' => 'active',
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.request-family-otp'), [
        'method' => 'email',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'OTP code sent to your registered email.');

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification) => $notification->preferredChannel === 'mail'
    );
});

it('sends family otp to phone if explicitly requested and verified', function () {
    $member = Member::factory()->create([
        'email' => 'phone-choice@example.com',
        'phone' => '22222222',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'state' => 'active',
        'status' => 'active',
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.request-family-otp'), [
        'method' => 'phone',
    ]);

    $response->assertSuccessful()
        ->assertJsonPath('message', 'OTP code sent to your registered phone number.');

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification) => $notification->preferredChannel === 'sms'
    );
});

it('fails if requesting an unverified method', function () {
    $member = Member::factory()->create([
        'email' => 'unverified-choice@example.com',
        'phone' => '33333333',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
        'state' => 'active',
        'status' => 'active',
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.request-family-otp'), [
        'method' => 'phone',
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Your phone number is not verified.');
});

it('defaults to verified phone if no method specified', function () {
    $member = Member::factory()->create([
        'email' => 'default-choice@example.com',
        'phone' => '44444444',
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
        'state' => 'active',
        'status' => 'active',
    ]);

    Sanctum::actingAs($member);

    $response = $this->postJson(route('api.v1.auth.request-family-otp'));

    $response->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification) => $notification->preferredChannel === 'sms'
    );
});
