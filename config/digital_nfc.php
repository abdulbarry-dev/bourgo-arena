<?php

return [
    /**
     * List of supported phone models for digital NFC access.
     * If empty, all models are allowed unless specifically blocked.
     */
    'supported_models' => [
        'Samsung Galaxy S24',
        'Samsung Galaxy S23',
        'Google Pixel 9',
        'Google Pixel 8',
    ],

    /**
     * Minimum Android version required for digital NFC access (Host Card Emulation).
     */
    'minimum_android_version' => 12,

    /**
     * List of blocked manufacturers.
     */
    'blocked_manufacturers' => [
        'Huawei',
    ],
];
