<?php

use App\Jobs\SendEmailNotification;
use App\Jobs\SendPushNotification;
use App\Jobs\SendSmsNotification;
use App\Mail\AdminNotificationMail;
use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Models\NotificationLog;
use App\Models\NotificationType;
use App\Services\Members\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── Helpers ───────────────────────────────────────────────
function createLog(string $channel, ?Member $member = null): NotificationLog
{
    $type = NotificationType::factory()->create();

    return NotificationLog::factory()->create([
        'notification_type_id' => $type->id,
        'member_id' => $member?->id,
        'channel' => $channel,
        'status' => 'queued',
    ]);
}

// ──────────────────────────────────────────────────────────
// SendEmailNotification
// ──────────────────────────────────────────────────────────
describe('SendEmailNotification', function () {
    it('marks log as sent when email is delivered', function () {
        $member = Member::factory()->create(['email' => 'test@example.com']);
        $log = createLog('email', $member);

        Mail::fake();

        $job = new SendEmailNotification($log->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('sent');
        expect($log->fresh()->sent_at)->not->toBeNull();

        Mail::assertQueued(AdminNotificationMail::class);
    });

    it('silently returns when log is not found', function () {
        $job = new SendEmailNotification(9999);
        $job->handle();
        // No exception should be thrown
        expect(true)->toBeTrue();
    });

    it('silently returns when log has no member', function () {
        $log = createLog('email', null); // no member attached

        $job = new SendEmailNotification($log->id);
        $job->handle();

        // Status stays queued since we don't mark it failed when member is missing
        expect($log->fresh()->status)->toBe('queued');
    });

    it('marks log as failed when member has no email', function () {
        $member = Member::factory()->create(['email' => null]);
        $log = createLog('email', $member);

        $job = new SendEmailNotification($log->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata)->toHaveKey('error');
    });

    it('marks log as failed when mailer throws an exception', function () {
        $member = Member::factory()->create(['email' => 'test@example.com']);
        $log = createLog('email', $member);

        Mail::fake();
        Mail::shouldReceive('mailer->send')->andThrow(new RuntimeException('SMTP connection failed'));

        $job = new SendEmailNotification($log->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata['error'])->toContain('SMTP connection failed');
    });
});

// ──────────────────────────────────────────────────────────
// SendPushNotification
// ──────────────────────────────────────────────────────────
describe('SendPushNotification', function () {
    it('marks log as sent when push is delivered to a specific member', function () {
        $member = Member::factory()->create();
        $token = MemberDeviceToken::factory()->create([
            'member_id' => $member->id,
            'is_active' => true,
        ]);
        $log = createLog('push', $member);

        $pushService = Mockery::mock(PushNotificationService::class);
        $pushService->shouldReceive('send')
            ->once()
            ->with([$token->token], $log->subject, $log->body, Mockery::any());

        $job = new SendPushNotification($log->id, $member->id);
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('sent');
        expect($log->fresh()->sent_at)->not->toBeNull();
    });

    it('silently returns when log is not found', function () {
        $pushService = Mockery::mock(PushNotificationService::class);

        $job = new SendPushNotification(9999);
        $job->handle($pushService);

        expect(true)->toBeTrue();
    });

    it('silently returns when member is not found in sendToMember', function () {
        $log = createLog('push');
        $pushService = Mockery::mock(PushNotificationService::class);

        $job = new SendPushNotification($log->id, 9999);
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('queued');
    });

    it('silently returns when member has no device token', function () {
        $member = Member::factory()->create();
        $log = createLog('push', $member);

        $pushService = Mockery::mock(PushNotificationService::class);
        $pushService->shouldReceive('send')->never();

        $job = new SendPushNotification($log->id, $member->id);
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('queued');
    });

    it('marks log as failed when sendToAll has no active tokens', function () {
        $log = createLog('push', null);

        $pushService = Mockery::mock(PushNotificationService::class);
        $pushService->shouldReceive('send')->never();

        $job = new SendPushNotification($log->id); // no memberId = sendToAll
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata['error'])->toContain('No active device tokens');
    });

    it('marks log as sent when push is delivered to all members', function () {
        $member = Member::factory()->create();
        MemberDeviceToken::factory()->create([
            'member_id' => $member->id,
            'is_active' => true,
        ]);
        $log = createLog('push', null);

        $pushService = Mockery::mock(PushNotificationService::class);
        $pushService->shouldReceive('send')
            ->once()
            ->with(Mockery::type('array'), $log->subject, $log->body, Mockery::any());

        $job = new SendPushNotification($log->id);
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('sent');
    });

    it('marks log as failed when push service throws', function () {
        $member = Member::factory()->create();
        MemberDeviceToken::factory()->create([
            'member_id' => $member->id,
            'is_active' => true,
        ]);
        $log = createLog('push', $member);

        $pushService = Mockery::mock(PushNotificationService::class);
        $pushService->shouldReceive('send')
            ->once()
            ->andThrow(new RuntimeException('FCM server error'));

        $job = new SendPushNotification($log->id, $member->id);
        $job->handle($pushService);

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata['error'])->toContain('FCM server error');
    });
});

// ──────────────────────────────────────────────────────────
// SendSmsNotification
// ──────────────────────────────────────────────────────────
describe('SendSmsNotification', function () {
    it('marks log as sent when SMS is delivered to a specific member', function () {
        $member = Member::factory()->create(['phone' => '+21650123456']);
        $log = createLog('sms', $member);

        Http::fake([
            'api.twilio.com/*' => Http::response(['sid' => 'SM123'], 201),
        ]);

        config()->set('services.twilio.account_sid', 'test_sid');
        config()->set('services.twilio.auth_token', 'test_token');
        config()->set('services.twilio.from_number', '+15017122661');

        $job = new SendSmsNotification($log->id, $member->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('sent');
        expect($log->fresh()->sent_at)->not->toBeNull();
    });

    it('silently returns when log is not found', function () {
        $job = new SendSmsNotification(9999);
        $job->handle();

        expect(true)->toBeTrue();
    });

    it('silently returns when member is not found in sendToMember', function () {
        $log = createLog('sms');

        $job = new SendSmsNotification($log->id, 9999);
        $job->handle();

        expect($log->fresh()->status)->toBe('queued');
    });

    it('silently returns when member has no phone in sendToMember', function () {
        $member = Member::factory()->create(['phone' => null]);
        $log = createLog('sms', $member);

        $job = new SendSmsNotification($log->id, $member->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('queued');
    });

    it('marks log as failed when no members have phones in sendToAll', function () {
        Member::factory()->create(['phone' => null]);
        $log = createLog('sms', null);

        $job = new SendSmsNotification($log->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata['error'])->toContain('No members with phone numbers');
    });

    it('marks log as failed when Twilio is not configured', function () {
        $member = Member::factory()->create(['phone' => '+21650123456']);
        $log = createLog('sms', $member);

        config()->set('services.twilio.account_sid', '');
        config()->set('services.twilio.auth_token', '');
        config()->set('services.twilio.from_number', '');

        $job = new SendSmsNotification($log->id, $member->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('failed');
        expect($log->fresh()->metadata['error'])->toContain('Twilio not configured');
    });

    it('marks log as failed when Twilio responds with error', function () {
        $member = Member::factory()->create(['phone' => '+21650123456']);
        $log = createLog('sms', $member);

        config()->set('services.twilio.account_sid', 'test_sid');
        config()->set('services.twilio.auth_token', 'test_token');
        config()->set('services.twilio.from_number', '+15017122661');

        Http::fake([
            'api.twilio.com/*' => Http::response(['message' => 'Invalid phone number'], 400),
        ]);

        $job = new SendSmsNotification($log->id, $member->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('failed');
    });

    it('uses correct body when message exceeds 160 characters for SMS', function () {
        $member = Member::factory()->create(['phone' => '+21650123456']);
        $log = createLog('sms', $member);
        $longBody = str_repeat('a', 200);
        $log->update(['body' => $longBody]);

        config()->set('services.twilio.account_sid', 'test_sid');
        config()->set('services.twilio.auth_token', 'test_token');
        config()->set('services.twilio.from_number', '+15017122661');

        Http::fake(function ($request) {
            parse_str($request->body(), $params);
            expect($params['Body'])->toHaveLength(160);

            return Http::response(['sid' => 'SM123'], 201);
        });

        $job = new SendSmsNotification($log->id, $member->id);
        $job->handle();

        expect($log->fresh()->status)->toBe('sent');
    });
});
