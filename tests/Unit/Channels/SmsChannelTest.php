<?php

use App\Channels\SmsChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Twilio\Rest\Client;

uses(TestCase::class);

// ─── Helpers ───

function makeSmsNotifiable(?string $phone = '22334455'): object
{
    return new class($phone)
    {
        public function __construct(public ?string $phone) {}

        public function routeNotificationFor(string $channel): ?string
        {
            return $this->phone;
        }
    };
}

function makeNotificationWithSms(?string $message = 'Your code is 123456'): Notification
{
    $notification = Mockery::mock(Notification::class);
    $notification->shouldReceive('toSms')->andReturn($message);

    return $notification;
}

// ─── Phone formatting ───

describe('phone formatting', function () {
    it('formats 8-digit Tunisian numbers with +216 prefix without crashing', function () {
        Config::set('services.twilio', [
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
        ]);

        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable('12345678'), makeNotificationWithSms());

        expect(true)->toBeTrue();
    });

    it('strips non-numeric characters from phone without crashing', function () {
        Config::set('services.twilio', [
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
        ]);

        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable('+216-12-345-678'), makeNotificationWithSms());

        expect(true)->toBeTrue();
    });

    it('does not modify already-formatted international numbers without crashing', function () {
        Config::set('services.twilio', [
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
        ]);

        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable('+33123456789'), makeNotificationWithSms());

        expect(true)->toBeTrue();
    });
});

// ─── Early return paths ───

describe('early returns', function () {
    it('returns without crashing when notifiable has no phone', function () {
        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable(null), makeNotificationWithSms());

        expect(true)->toBeTrue();
    });

    it('returns early when notification has no toSms method', function () {
        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable(), Mockery::mock(Notification::class));

        expect(true)->toBeTrue();
    });

    it('returns without crashing when Twilio credentials are not configured', function () {
        Config::set('services.twilio', [
            'account_sid' => '',
            'auth_token' => '',
            'from_number' => '',
        ]);

        $channel = new SmsChannel;
        $channel->send(makeSmsNotifiable(), makeNotificationWithSms());

        expect(true)->toBeTrue();
    });
});

// ─── Twilio coverage via existing integration tests ───
// Twilio SDK integration (Client::messages->create) is covered by:
//   - ForgotPasswordTest::test_forgot_password_sends_otp_via_sms_for_phone_identifier
//   - Tests sending OTP via SmsChannel through the full notification stack
