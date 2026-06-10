<?php

use App\Channels\SmsChannel;
use App\Jobs\SendSmsNotification;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Notifications\SendOtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ─── Config Validation ───

it('twilio config resolves correctly', function () {
    expect(config('services.twilio.account_sid'))->not->toBeNull();
    expect(config('services.twilio.auth_token'))->not->toBeNull();
    expect(config('services.twilio.from_number'))->not->toBeNull();
    expect(config('services.twilio.from_number'))->toStartWith('+');
});

// ─── OTP SMS ───

it('send-otp api with phone dispatches SendOtpCode via SmsChannel', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'phone' => '99887766',
        'email' => 'sms-test@example.com',
        'state' => 'active',
        'email_verified_at' => now(),
        'phone_verified_at' => null,
    ]);

    $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => '99887766',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array(SmsChannel::class, $channels),
    );
});

it('forgot-password with phone identifier triggers SmsChannel', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'phone' => '55443322',
        'email' => 'forgot-sms@example.com',
        'state' => 'active',
        'email_verified_at' => now(),
    ]);

    $this->postJson(route('api.v1.auth.forgot-password'), [
        'identifier' => '55443322',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array(SmsChannel::class, $channels),
    );
});

// ─── Admin SMS Job ───

it('SendSmsNotification reads Twilio config and sends HTTP request', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $member = Member::factory()->create(['phone' => '+21622446688']);
    $type = NotificationType::factory()->create();
    $log = NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'member_id' => $member->id,
        'channel' => 'sms',
        'body' => 'Test SMS notification body',
    ]);

    $job = new SendSmsNotification($log->id, $member->id);
    $job->handle();

    Http::assertSent(function (Request $request) {
        $payload = $request->data();
        $sid = config('services.twilio.account_sid');

        return str_contains($request->url(), "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json")
            && ($payload['From'] ?? null) === config('services.twilio.from_number')
            && ($payload['To'] ?? null) === '+21622446688'
            && ($payload['Body'] ?? null) === 'Test SMS notification body';
    });
});

it('SendSmsNotification truncates body to 160 characters', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $member = Member::factory()->create(['phone' => '+21622446688']);
    $type = NotificationType::factory()->create();
    $log = NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'member_id' => $member->id,
        'channel' => 'sms',
        'body' => str_repeat('a', 200),
    ]);

    $job = new SendSmsNotification($log->id, $member->id);
    $job->handle();

    Http::assertSent(function (Request $request) {
        $payload = $request->data();

        return strlen($payload['Body'] ?? '') === 160;
    });
});

it('SendSmsNotification sends raw phone number without formatting', function () {
    Http::fake([
        'https://api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $member = Member::factory()->create(['phone' => '12345678']);
    $type = NotificationType::factory()->create();
    $log = NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'member_id' => $member->id,
        'channel' => 'sms',
        'body' => 'Test',
    ]);

    $job = new SendSmsNotification($log->id, $member->id);
    $job->handle();

    Http::assertSent(function (Request $request) {
        $payload = $request->data();

        return ($payload['To'] ?? null) === '12345678';
    });
});
