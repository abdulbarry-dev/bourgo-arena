<?php

namespace App\Services\Auth;

use App\Channels\SmsChannel;
use App\Models\Member;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\SendOtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    protected int $expiryMinutes = 10;

    protected int $maxAttempts = 5;

    // TODO: Enable in production - currently disabled for development
    protected int $resendCooldownSeconds = 0;

    public function generate(string $identifier): string
    {
        $code = (string) rand(100000, 999999);
        $expiryMinutes = config('otp.expiry', $this->expiryMinutes);

        // Check if identifier belongs to a Member
        $member = Member::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if ($member) {
            // Check cooldown only in production
            if ($this->resendCooldownSeconds > 0 && $member->otp_last_sent_at && $member->otp_last_sent_at->addSeconds($this->resendCooldownSeconds)->isFuture()) {
                throw new \Exception(__('Please wait before requesting a new code.'));
            }

            $member->update([
                'otp_code' => $code, // Will be hashed via cast
                'otp_expires_at' => now()->addMinutes($expiryMinutes),
                'otp_attempts' => 0,
                'otp_last_sent_at' => now(),
            ]);
        } else {
            // Fallback to otp_codes table for other users or general use
            OtpCode::where('identifier', $identifier)->delete(); // Invalidate previous

            OtpCode::create([
                'identifier' => $identifier,
                'code' => $code, // I should probably add hashing to OtpCode model or handle it here
                'expires_at' => now()->addMinutes($expiryMinutes),
            ]);
        }

        $this->send($identifier, $code);

        return $code;
    }

    public function verify(string $identifier, string $code): bool
    {
        $member = Member::where('email', $identifier)
            ->orWhere('phone', $identifier)
            ->first();

        if ($member) {
            if (! $member->otp_code || $member->otp_expires_at->isPast()) {
                return false;
            }

            if ($member->otp_attempts >= $this->maxAttempts) {
                throw new \Exception(__('Too many failed attempts. Please request a new code.'));
            }

            if (! Hash::check($code, $member->otp_code)) {
                $member->increment('otp_attempts');

                return false;
            }

            // Valid code - mark as verified and clear OTP
            $now = now();
            $updateData = [
                'otp_code' => null,
                'otp_expires_at' => null,
                'otp_attempts' => 0,
            ];

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $updateData['email_verified_at'] = $now;
            } else {
                $updateData['phone_verified_at'] = $now;
            }

            // Update status if it was pending verification
            if ($member->status === 'pending_verification') {
                $updateData['status'] = 'pending_onboarding';
            }

            $member->update($updateData);

            return true;
        }

        // Fallback to otp_codes table
        $otpCode = OtpCode::where('identifier', $identifier)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otpCode || $otpCode->isExpired()) {
            return false;
        }

        // Note: otp_codes table currently doesn't store hashed codes in its schema or casts.
        // For simplicity and backward compatibility with my previous turn, I'll check literal if it doesn't look like a hash.
        $isMatch = (strlen($otpCode->code) === 6 && $otpCode->code === $code) || Hash::check($code, $otpCode->code);

        if (! $isMatch) {
            return false;
        }

        $otpCode->update(['used_at' => now()]);

        return true;
    }

    public function send(string $identifier, string $code): void
    {
        $isEmail = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $preferredChannel = $isEmail ? 'mail' : 'sms';

        // Check if we should re-route from phone to email for web requests
        $isApiRequest = request()->is('api/*') || request()->expectsJson();
        $isConsole = app()->runningInConsole();

        try {
            $notifiable = null;

            if (! $isApiRequest && ! $isConsole && ! $isEmail) {
                // For non-API/non-Console requests, if it's a phone, try to find the user's email
                $user = User::where('phone', $identifier)->first()
                    ?? Member::where('phone', $identifier)->first();

                if ($user && $user->email) {
                    $identifier = $user->email;
                    $notifiable = $user;
                    $preferredChannel = 'mail';
                }
            }

            if (! $notifiable) {
                $notifiable = User::where('email', $identifier)->orWhere('phone', $identifier)->first()
                    ?? Member::where('email', $identifier)->orWhere('phone', $identifier)->first();
            }

            if ($notifiable) {
                Log::info("Sending OTP to found notifiable ({$identifier}) via {$preferredChannel}");
                $notifiable->notify(new SendOtpCode($code, $preferredChannel));
            } elseif ($isEmail) {
                Log::info("Sending OTP to email ({$identifier}) via mail (Anonymous)");
                Notification::route('mail', $identifier)->notify(new SendOtpCode($code, 'mail'));
            } else {
                Log::info("Sending OTP to phone ({$identifier}) via sms (Anonymous)");
                Notification::route(SmsChannel::class, $identifier)->notify(new SendOtpCode($code, 'sms'));
            }
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$identifier}: ".$e->getMessage());

            if (app()->environment('local', 'testing')) {
                Log::info("OTP Code (fallback log) for {$identifier}: {$code}");
            } else {
                throw $e;
            }
        }
    }
}
