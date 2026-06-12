<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Konnect is the only supported online payment gateway.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Default Payment Provider
    |--------------------------------------------------------------------------
    */
    'default' => env('PAYMENT_DRIVER', 'konnect'),

    /*
    |--------------------------------------------------------------------------
    | Payment Providers Configuration
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'konnect' => [
            'api_key' => env('KONNECT_API_KEY'),
            'api_secret' => env('KONNECT_API_SECRET'),
            'sandbox' => env('KONNECT_SANDBOX', true),
            'webhook_secret' => env('KONNECT_WEBHOOK_SECRET'),
        ],
    ],

    // Legacy fallback for tests/old implementations
    'konnect' => [
        'api_key' => env('KONNECT_API_KEY'),
        'api_secret' => env('KONNECT_API_SECRET'),
        'sandbox' => env('KONNECT_SANDBOX', true),
        'webhook_secret' => env('KONNECT_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Methods
    |--------------------------------------------------------------------------
    |
    | Define which payment methods are enabled for subscriptions.
    | Supported values: 'cash', 'konnect'
    |
    */

    'methods' => [
        'cash' => true,
        'konnect' => true,
        'test' => true,
        'loyalty_points' => true,
    ],

    'geo_restriction' => [
        'enabled' => true,
        'allowed_countries' => ['TN'],
        'exempt_staff' => true,
        'block_local_ips' => false,
    ],

    'initiate_per_minute' => env('PAYMENT_INITIATE_PER_MINUTE', 10),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook verification and security settings
    |
    */

    'subscription' => [
        'pending_timeout_minutes' => (int) env('PENDING_PAYMENT_TIMEOUT_MINUTES', 5),
    ],

    'webhooks' => [
        'verify_signature' => true,
        'timeout' => 30,
        // Default to false in non-test environments so webhooks are enqueued asynchronously.
        'dispatch_sync' => env('PAYMENT_WEBHOOK_DISPATCH_SYNC', false),
    ],
];
