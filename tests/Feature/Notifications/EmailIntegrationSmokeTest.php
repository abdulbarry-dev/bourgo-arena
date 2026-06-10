<?php

use App\Channels\SmsChannel;
use App\Jobs\SendEmailNotification;
use App\Mail\AccountDeletionScheduledMail;
use App\Mail\AdminNotificationMail;
use App\Mail\SendOtpCodeMail;
use App\Models\Member;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Notifications\AccountDeletionScheduled;
use App\Notifications\SendOtpCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

// ─── Config Validation ───

it('resend mailer config resolves in non-test environments', function () {
    expect(config('mail.default'))->toBe('array'); // phpunit.xml overrides to 'array' for tests
    expect(env('MAIL_MAILER'))->toBe('array');
    expect(config('services.resend.key'))->not->toBeNull();
    expect(config('services.resend.key'))->toStartWith('re_');
});

// ─── OTP Email ───

it('send-otp api dispatches SendOtpCodeMail via mail channel', function () {
    Notification::fake();
    Mail::fake();

    $member = Member::factory()->create([
        'email' => 'otp-test@example.com',
        'state' => 'pending_verification',
    ]);

    $this->postJson(route('api.v1.auth.send-otp'), [
        'identifier' => 'otp-test@example.com',
    ])->assertSuccessful();

    Notification::assertSentTo(
        $member,
        SendOtpCode::class,
        fn ($notification, $channels) => in_array('mail', $channels),
    );
});

it('SendOtpCodeMail has correct envelope content', function () {
    $mail = new SendOtpCodeMail(
        code: '123456',
        userEmail: 'test@example.com',
        userName: 'Ahmed',
    );

    expect($mail->envelope()->to[0]->address)->toBe('test@example.com');
    expect($mail->envelope()->subject)->toBe('Your OTP Verification Code');
    expect($mail->content()->markdown)->toBe('emails.otp.code');
});

// ─── Admin Notification Email ───

it('AdminNotificationMail has correct envelope and markdown template', function () {
    $member = Member::factory()->create(['email' => 'admin-test@example.com']);

    $mail = new AdminNotificationMail(
        member: $member,
        subjectText: 'Test Subject',
        body: 'Test body',
    );

    expect($mail->envelope()->subject)->toBe('Test Subject');
    expect($mail->content()->markdown)->toBe('emails.admin.admin-notification');
});

it('SendEmailNotification dispatches AdminNotificationMail via resend', function () {
    Mail::fake();
    $member = Member::factory()->create(['email' => 'send-test@example.com']);
    $type = NotificationType::factory()->create();
    $log = NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'member_id' => $member->id,
        'channel' => 'email',
    ]);

    $job = new SendEmailNotification($log->id);
    $job->handle();

    Mail::assertQueued(AdminNotificationMail::class);
});

// ─── Account Deletion Email + SMS ───

it('AccountDeletionScheduled delivers via mail and sms when both verified', function () {
    Notification::fake();

    $member = Member::factory()->create([
        'email_verified_at' => now(),
        'phone_verified_at' => now(),
    ]);

    $member->notify(new AccountDeletionScheduled);

    Notification::assertSentTo(
        $member,
        AccountDeletionScheduled::class,
        fn ($notification, $channels) => (
            in_array('mail', $channels)
            && in_array(SmsChannel::class, $channels)
        ),
    );
});

it('AccountDeletionScheduledMail returns correct mailable', function () {
    $member = Member::factory()->create(['email' => 'del-test@example.com']);

    $notification = new AccountDeletionScheduled;
    $mailable = $notification->toMail($member);

    expect($mailable)->toBeInstanceOf(AccountDeletionScheduledMail::class);
    expect($mailable->envelope()->to[0]->address)->toBe('del-test@example.com');
    expect($mailable->envelope()->subject)->toBe('Account Deletion Scheduled');
});
