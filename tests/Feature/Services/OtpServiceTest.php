<?php

use App\Models\Member;
use App\Models\OtpCode;
use App\Notifications\SendOtpCode;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->otpService = app(OtpService::class);
});

// ─── generate() ───

describe('generate()', function () {
    it('generates a 6-digit code for a member', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'gen@example.com']);

        $code = $this->otpService->generate('gen@example.com');

        expect(strlen((string) $code))->toBe(6);
        expect($code)->toBeNumeric();
    });

    it('stores hashed code on the member model', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'hash@example.com']);

        $code = $this->otpService->generate('hash@example.com');
        $member->refresh();

        expect($member->otp_code)->not->toBeNull();
        expect($member->otp_code)->not->toBe($code);
        expect(Hash::check($code, $member->otp_code))->toBeTrue();
    });

    it('returns cached code during cache cooldown', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'cooldown@example.com']);

        $firstCode = $this->otpService->generate('cooldown@example.com');
        $secondCode = $this->otpService->generate('cooldown@example.com');

        expect($secondCode)->toBe($firstCode);
        Notification::assertSentToTimes($member, SendOtpCode::class, 1);
    });

    it('returns cached code during member DB cooldown', function () {
        Notification::fake();
        $member = Member::factory()->create([
            'email' => 'db-cooldown@example.com',
            'otp_last_sent_at' => now()->subSeconds(30),
        ]);
        $cacheKey = (new ReflectionMethod($this->otpService, 'cachedCodeCacheKey'))
            ->invoke($this->otpService, 'db-cooldown@example.com');
        Cache::put($cacheKey, '999999', now()->addMinutes(10));

        $code = $this->otpService->generate('db-cooldown@example.com');

        expect($code)->toBe('999999');
    });

    it('stores code in otp_codes table for non-member identifier', function () {
        Notification::fake();

        $code = $this->otpService->generate('anon@example.com');

        $this->assertDatabaseHas('otp_codes', ['identifier' => 'anon@example.com']);
        $otpCode = OtpCode::where('identifier', 'anon@example.com')->first();
        expect(Hash::check($code, $otpCode->code))->toBeTrue();
    });

    it('invalidates previous unused otp_codes for same identifier', function () {
        Notification::fake();
        OtpCode::factory()->create(['identifier' => 'repeat@example.com']);

        $this->otpService->generate('repeat@example.com');

        expect(OtpCode::where('identifier', 'repeat@example.com')->count())->toBe(1);
    });

    it('sends notification after generation', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'notif-test@example.com']);

        $this->otpService->generate('notif-test@example.com');

        Notification::assertSentTo($member, SendOtpCode::class);
    });

    it('sets expiry based on config', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'expiry@example.com']);

        $this->otpService->generate('expiry@example.com');
        $member->refresh();

        expect($member->otp_expires_at)->not->toBeNull();
        expect($member->otp_expires_at->diffInMinutes(now()))
            ->toBeLessThanOrEqual((int) config('otp.expiry', 10) + 1);
    });

    it('resets attempts counter after successful generation', function () {
        Notification::fake();
        $member = Member::factory()->create([
            'email' => 'attempts@example.com',
            'otp_attempts' => 3,
        ]);

        $this->otpService->generate('attempts@example.com');
        $member->refresh();

        expect($member->otp_attempts)->toBe(0);
    });
});

// ─── verify() ───

describe('verify()', function () {
    it('verifies correct code for a member', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'verify@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->otpService->verify('verify@example.com', '123456');

        expect($result)->toBeTrue();
    });

    it('returns false for wrong code', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'wrong@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->otpService->verify('wrong@example.com', '000000');

        expect($result)->toBeFalse();
    });

    it('increments attempts on wrong code', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'increment@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('increment@example.com', '000000');
        $member->refresh();

        expect($member->otp_attempts)->toBe(1);
    });

    it('throws exception when max attempts exceeded', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'maxed@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 5,
        ]);

        expect(fn () => $this->otpService->verify('maxed@example.com', '000000'))
            ->toThrow(Exception::class, 'Too many failed attempts');
    });

    it('returns false for expired code', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'expired@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->subMinute(),
        ]);

        $result = $this->otpService->verify('expired@example.com', '123456');

        expect($result)->toBeFalse();
    });

    it('returns false when no otp_code set', function () {
        $member = Member::factory()->create(['email' => 'none@example.com']);

        $result = $this->otpService->verify('none@example.com', '123456');

        expect($result)->toBeFalse();
    });

    it('clears OTP fields after successful verification', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'clear@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
            'otp_attempts' => 2,
        ]);

        $this->otpService->verify('clear@example.com', '123456');
        $member->refresh();

        expect($member->otp_code)->toBeNull();
        expect($member->otp_expires_at)->toBeNull();
        expect($member->otp_attempts)->toBe(0);
    });

    it('sets email_verified_at when verifying with email identifier', function () {
        Notification::fake();
        $member = Member::factory()->create(['email' => 'ev@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('ev@example.com', '123456');
        $member->refresh();

        expect($member->email_verified_at)->not->toBeNull();
    });

    it('sets phone_verified_at when verifying with phone identifier', function () {
        Notification::fake();
        $member = Member::factory()->create([
            'email' => 'pv@example.com',
            'phone' => '22446688',
        ]);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('22446688', '123456');
        $member->refresh();

        expect($member->phone_verified_at)->not->toBeNull();
    });

    it('verifies correct code for non-member via otp_codes table', function () {
        OtpCode::factory()->create([
            'identifier' => 'anon@test.com',
            'code' => '654321',
            'expires_at' => now()->addMinutes(10),
        ]);

        $result = $this->otpService->verify('anon@test.com', '654321');

        expect($result)->toBeTrue();
    });

    it('returns false for expired OtpCode', function () {
        OtpCode::factory()->expired()->create([
            'identifier' => 'expired-anon@test.com',
            'code' => '654321',
        ]);

        $result = $this->otpService->verify('expired-anon@test.com', '654321');

        expect($result)->toBeFalse();
    });

    it('returns false for used OtpCode', function () {
        OtpCode::factory()->used()->create([
            'identifier' => 'used-anon@test.com',
            'code' => '654321',
        ]);

        $result = $this->otpService->verify('used-anon@test.com', '654321');

        expect($result)->toBeFalse();
    });

    it('marks OtpCode as used after successful verification', function () {
        $otpCode = OtpCode::factory()->create([
            'identifier' => 'mark-used@test.com',
            'code' => '111111',
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('mark-used@test.com', '111111');
        $otpCode->refresh();

        expect($otpCode->used_at)->not->toBeNull();
    });

    it('preserves active state when verifying secondary method', function () {
        Notification::fake();
        $member = Member::factory()->active()->create(['email' => 'active@example.com']);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('active@example.com', '123456');
        $member->refresh();

        expect($member->state)->toBe('active');
    });

    it('transitions to pending_onboarding when verification completes for non-active member', function () {
        Notification::fake();
        $member = Member::factory()->create([
            'email' => 'pending@example.com',
            'state' => 'pending_verification',
            'email_verified_at' => null,
            'phone_verified_at' => null,
            'onboarding_completed_at' => null,
        ]);
        $member->update([
            'otp_code' => '123456',
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        $this->otpService->verify('pending@example.com', '123456');
        $member->refresh();

        expect($member->state)->toBe('pending_onboarding');
    });
});
