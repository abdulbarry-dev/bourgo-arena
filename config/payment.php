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
        'flouci' => [
            'app_token' => env('FLOUCI_APP_TOKEN'),
            'app_secret' => env('FLOUCI_APP_SECRET'),
            'sandbox' => env('FLOUCI_SANDBOX', true),
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
        'flouci' => true,
    ],

    'initiate_per_minute' => env('PAYMENT_INITIATE_PER_MINUTE', 10),

    /*
    |--------------------------------------------------------------------------
    | Receipt Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for PDF receipt generation and email dispatch
    |
    */

    'receipts' => [
        'enabled' => true,
        'storage' => 'private',
        'disk' => 'local',
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook verification and security settings
    |
    */

    'webhooks' => [
        'verify_signature' => true,
        'timeout' => 30,
        // When true, webhook dispatches reconciliation jobs synchronously (useful for tests).
        // Default to false in non-test environments so webhooks are enqueued asynchronously.
        'dispatch_sync' => env('PAYMENT_WEBHOOK_DISPATCH_SYNC', false),
    ],
];
