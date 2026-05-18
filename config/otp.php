<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure the settings for the One-Time Password (OTP)
    | generation and validation.
    |
    */

    'expiry' => env('OTP_EXPIRY', 10), // in minutes
    'length' => env('OTP_LENGTH', 6),
    'resend_cooldown_seconds' => env('OTP_RESEND_COOLDOWN_SECONDS', 60),
];
