<?php

use App\Channels\SmsChannel;
use App\Mail\AccountDeletionScheduledMail;
use App\Models\Member;
use App\Notifications\AccountDeletionScheduled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ─── Helpers ───

function makeDelNotifiable(array $overrides = []): object
{
    $defaults = [
        'email_verified_at' => now(),
        'phone_verified_at' => null,
    ];
    $attrs = array_merge($defaults, $overrides);

    return new class($attrs['email_verified_at'], $attrs['phone_verified_at'])
    {
        public function __construct(
            public $email_verified_at,
            public $phone_verified_at,
        ) {}
    };
}

// ─── via() routing ───

describe('via()', function () {
    it('routes to mail when only email is verified', function () {
        $notification = new AccountDeletionScheduled;
        $notifiable = makeDelNotifiable([
            'email_verified_at' => now(),
            'phone_verified_at' => null,
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe(['mail']);
    });

    it('routes to SmsChannel when only phone is verified', function () {
        $notification = new AccountDeletionScheduled;
        $notifiable = makeDelNotifiable([
            'email_verified_at' => null,
            'phone_verified_at' => now(),
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toBe([SmsChannel::class]);
    });

    it('routes to both channels when both are verified', function () {
        $notification = new AccountDeletionScheduled;
        $notifiable = makeDelNotifiable([
            'email_verified_at' => now(),
            'phone_verified_at' => now(),
        ]);

        $channels = $notification->via($notifiable);

        expect($channels)->toContain('mail');
        expect($channels)->toContain(SmsChannel::class);
    });
});

// ─── Content methods ───

describe('content methods', function () {
    it('toMail returns AccountDeletionScheduledMail', function () {
        $notification = new AccountDeletionScheduled;
        $notifiable = Member::factory()->create();

        $mailable = $notification->toMail($notifiable);

        expect($mailable)->toBeInstanceOf(AccountDeletionScheduledMail::class);
    });

    it('toSms returns a string', function () {
        $notification = new AccountDeletionScheduled;
        $notifiable = Member::factory()->create();

        $message = $notification->toSms($notifiable);

        expect($message)->toBeString();
        expect($message)->toContain('scheduled for deletion');
    });
});
