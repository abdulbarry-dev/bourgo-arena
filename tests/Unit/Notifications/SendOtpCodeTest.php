<?php

use App\Channels\SmsChannel;
use App\Mail\SendOtpCodeMail;
use App\Notifications\SendOtpCode;
use Tests\TestCase;

uses(TestCase::class);

// ─── Helpers ───

function makeOtpNotifiable(array $overrides = []): object
{
    $defaults = [
        'email' => 'test@example.com',
        'phone' => '22334455',
        'email_verified_at' => null,
        'phone_verified_at' => null,
        'name' => null,
    ];
    $attrs = array_merge($defaults, $overrides);

    return new class($attrs['email'], $attrs['phone'], $attrs['email_verified_at'], $attrs['phone_verified_at'], $attrs['name'])
    {
        public function __construct(
            public ?string $email,
            public ?string $phone,
            public $email_verified_at,
            public $phone_verified_at,
            public ?string $name,
        ) {}

        public function routeNotificationFor(string $channel): ?string
        {
            if ($channel === 'mail') {
                return $this->email;
            }

            return $this->phone;
        }
    };
}

// ─── via() routing ───

describe('via() routing', function () {
    it('routes to mail when preferredChannel is mail', function () {
        $notification = new SendOtpCode('123456', 'mail');
        $notifiable = makeOtpNotifiable();

        $channels = $notification->via($notifiable);

        expect($channels)->toBe(['mail']);
    });

    it('routes to SmsChannel when preferredChannel is sms', function () {
        $notification = new SendOtpCode('123456', 'sms');
        $notifiable = makeOtpNotifiable();

        $channels = $notification->via($notifiable);

        expect($channels)->toBe([SmsChannel::class]);
    });

    it('routes to both channels when both email and phone are verified', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toContain('mail');
        expect($channels)->toContain(SmsChannel::class);
    });

    it('routes to mail when only email is verified', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe(['mail']);
    });

    it('routes to sms when only phone is verified', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe([SmsChannel::class]);
    });

    it('falls back to mail when has email but neither verified', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email' => 'test@example.com',
            'phone' => null,
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe(['mail']);
    });

    it('falls back to sms when has phone but no email and neither verified', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email' => null,
            'phone' => '22334455',
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe([SmsChannel::class]);
    });

    it('falls back to mail as last resort when nothing available', function () {
        $notification = new SendOtpCode('123456');
        $notifiable = makeOtpNotifiable([
            'email' => null,
            'phone' => null,
            'email_verified_at' => null,
            'phone_verified_at' => null,
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe(['mail']);
    });
});

// ─── toMail() ───

describe('toMail()', function () {
    it('returns SendOtpCodeMail with correct code and email', function () {
        $notification = new SendOtpCode('654321');
        $notifiable = makeOtpNotifiable(['name' => 'Ahmed']);

        $mailable = $notification->toMail($notifiable);

        expect($mailable)->toBeInstanceOf(SendOtpCodeMail::class);
        expect($mailable->code)->toBe('654321');
        expect($mailable->userEmail)->toBe('test@example.com');
        expect($mailable->userName)->toBe('Ahmed');
    });

    it('handles null userName in toMail', function () {
        $notification = new SendOtpCode('654321');
        $notifiable = makeOtpNotifiable();

        $mailable = $notification->toMail($notifiable);

        expect($mailable->userName)->toBeNull();
    });
});

// ─── toSms() ───

describe('toSms()', function () {
    it('returns formatted message with code and expiry', function () {
        $notification = new SendOtpCode('987654');
        $notifiable = makeOtpNotifiable();

        $message = $notification->toSms($notifiable);

        expect($message)->toBeString();
        expect($message)->toContain('987654');
        expect($message)->toContain((string) config('otp.expiry', 10));
    });
});
