<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file allows you to specify which payment gateway
    | to use for processing payments. Currently supported gateways:
    | 'konnect' and 'paymee'
    |
    */

    'driver' => env('PAYMENT_GATEWAY', 'konnect'),

    'konnect' => [
        'api_key' => env('KONNECT_API_KEY'),
        'api_secret' => env('KONNECT_API_SECRET'),
        'sandbox' => env('KONNECT_SANDBOX', true),
    ],

    'paymee' => [
        'api_key' => env('PAYMEE_API_KEY'),
        'api_secret' => env('PAYMEE_API_SECRET'),
        'sandbox' => env('PAYMEE_SANDBOX', true),
        'webhook_url' => env('PAYMEE_WEBHOOK_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported Payment Methods
    |--------------------------------------------------------------------------
    |
    | Define which payment methods are enabled for subscriptions.
    | Supported values: 'cash', 'konnect', 'paymee'
    |
    */

    'methods' => [
        'cash' => true,
        'konnect' => true,
        'paymee' => true,
    ],

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
    ],
];
