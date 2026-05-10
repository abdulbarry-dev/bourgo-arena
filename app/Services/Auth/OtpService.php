<?php

namespace App\Services\Auth;

use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;

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
        Log::info("OTP Code for {$identifier}: {$code}");
    }
}
