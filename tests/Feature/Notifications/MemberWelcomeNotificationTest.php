<?php

use App\Jobs\SendMemberWelcomePush;
use App\Jobs\SendMemberWelcomeSms;
use App\Models\Member;
use App\Models\MemberDeviceToken;
use App\Services\Members\PushNotificationService;
use App\Services\Members\SmsNotificationService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

test('sms notification service sends welcome message through twilio api', function () {
    config([
        'services.twilio.account_sid' => 'AC_TEST_SID',
        'services.twilio.auth_token' => 'twilio-secret',
        'services.twilio.from_number' => '+15550001111',
    ]);

    Http::fake([
        'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $member = Member::factory()->create([
        'name' => 'SMS Member',
        'phone' => '+21620000001',
    ]);

    app(SmsNotificationService::class)->sendWelcomeMessage($member);

    Http::assertSent(function (Request $request) use ($member): bool {
        $payload = $request->data();

        return $request->url() === 'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json'
            && ($payload['To'] ?? null) === $member->phone
            && ($payload['From'] ?? null) === '+15550001111';
    });
});

test('push notification service sends welcome message through fcm api', function () {
    config([
        'services.fcm.server_key' => 'fcm-test-key',
    ]);

    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    app(PushNotificationService::class)->send(
        ['fcm-token-a', 'fcm-token-b'],
        'Welcome title',
        'Welcome body',
        ['type' => 'member_welcome'],
    );

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();

        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && $request->hasHeader('Authorization', 'key=fcm-test-key')
            && ($payload['notification']['title'] ?? null) === 'Welcome title';
    });
});

test('welcome sms job delegates to twilio service', function () {
    config([
        'services.twilio.account_sid' => 'AC_TEST_SID',
        'services.twilio.auth_token' => 'twilio-secret',
        'services.twilio.from_number' => '+15550001111',
    ]);

    Http::fake([
        'https://api.twilio.com/2010-04-01/Accounts/AC_TEST_SID/Messages.json' => Http::response(['sid' => 'SM123'], 201),
    ]);

    $member = Member::factory()->create([
        'phone' => '+21620000002',
    ]);

    SendMemberWelcomeSms::dispatchSync($member->id);

    Http::assertSentCount(1);
});

test('welcome push job sends to active member device tokens only', function () {
    config([
        'services.fcm.server_key' => 'fcm-test-key',
    ]);

    Http::fake([
        'https://fcm.googleapis.com/fcm/send' => Http::response(['success' => 1], 200),
    ]);

    $member = Member::factory()->create();

    MemberDeviceToken::query()->create([
        'member_id' => $member->id,
        'token' => 'active-token',
        'provider' => 'fcm',
        'device_type' => 'android',
        'is_active' => true,
        'last_used_at' => now(),
    ]);

    MemberDeviceToken::query()->create([
        'member_id' => $member->id,
        'token' => 'inactive-token',
        'provider' => 'fcm',
        'device_type' => 'ios',
        'is_active' => false,
        'last_used_at' => now(),
    ]);

    SendMemberWelcomePush::dispatchSync($member->id);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();
        $tokens = $payload['registration_ids'] ?? [];

        return $request->url() === 'https://fcm.googleapis.com/fcm/send'
            && in_array('active-token', $tokens, true)
            && ! in_array('inactive-token', $tokens, true);
    });
});
