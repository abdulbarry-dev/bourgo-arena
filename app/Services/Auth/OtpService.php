<?php

namespace App\Services\Auth;

use App\Models\OtpCode;
use App\Notifications\SendOtpCode;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    public function generate(string $identifier): string
    {
        $code = (string) rand(100000, 999999);
        $expiryMinutes = config('otp.expiry', 10);

        OtpCode::create([
            'identifier' => $identifier,
            'code' => $code,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        $this->send($identifier, $code);

        return $code;
    }

    public function verify(string $identifier, string $code): bool
    {
        $otpCode = OtpCode::where('identifier', $identifier)
            ->where('code', $code)
            ->whereNull('used_at')
            ->latest()
            ->first();

        if (! $otpCode || $otpCode->isExpired()) {
            return false;
        }

        $otpCode->update(['used_at' => now()]);

        return true;
    }

    public function send(string $identifier, string $code): void
    {
        try {
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                Notification::route('mail', $identifier)->notify(new SendOtpCode($code));
            } else {
                // For phone numbers, we log it since no SMS provider is configured yet
                Log::info("OTP Code for {$identifier}: {$code}");
            }
        } catch (\Exception $e) {
            Log::error("Failed to send OTP to {$identifier}: ".$e->getMessage());

            // In local/testing environments, we log the code anyway as a fallback
            if (app()->environment('local', 'testing')) {
                Log::info("OTP Code (fallback log) for {$identifier}: {$code}");
            } else {
                // In production, we might still want to know it failed
                throw $e;
            }
        }
    }
}
