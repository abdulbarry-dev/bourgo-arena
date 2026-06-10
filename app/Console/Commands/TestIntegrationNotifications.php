<?php

namespace App\Console\Commands;

use App\Channels\SmsChannel;
use App\Mail\SendOtpCodeMail;
use App\Notifications\SendOtpCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class TestIntegrationNotifications extends Command
{
    protected $signature = 'notifications:test-integrations';

    protected $description = 'Send a test email (Resend) and SMS (Twilio) to verify integrations work end-to-end';

    public function handle(): int
    {
        $email = $this->ask('Email address to test', 'abdulbarry.guenichi@gmail.com');
        $phone = $this->ask('Phone number to test (8-digit Tunisian or full international)', '53005234');
        $code = (string) random_int(100000, 999999);

        $this->info('=== Notification Integration Test ===');
        $this->newLine();
        $this->warn("Using OTP code: {$code}");
        $this->newLine();

        // ─── Email test ───
        $this->line("1. Sending test email to: {$email}");

        try {
            $mail = new SendOtpCodeMail(
                code: $code,
                userEmail: $email,
                userName: 'Test User',
            );

            Mail::mailer('resend')->send($mail);

            $this->info("   ✅ Email sent successfully via Resend");
        } catch (\Throwable $e) {
            $this->error("   ❌ Email failed: {$e->getMessage()}");
            Log::error('Email integration test failed', ['error' => $e->getMessage(), 'email' => $email]);
        }

        $this->newLine();

        // ─── SMS test ───
        $this->line("2. Sending test SMS to: {$phone}");

        try {
            Notification::route(SmsChannel::class, $phone)
                ->notify(new SendOtpCode($code, 'sms'));

            $this->info("   ✅ SMS sent successfully via Twilio");
        } catch (\Throwable $e) {
            $this->error("   ❌ SMS failed: {$e->getMessage()}");
            Log::error('SMS integration test failed', ['error' => $e->getMessage(), 'phone' => $phone]);
        }

        $this->newLine();
        $this->info('=== Test complete ===');
        $this->line("Check your email at {$email} and SMS at {$phone} for the OTP code: {$code}");

        return self::SUCCESS;
    }
}
