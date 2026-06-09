<?php

return [
    'fixed_monthly_renewal_points' => 250,

    'variable' => [
        'eligible_categories' => ['Padel', 'Football'],
        'base_points_per_reservation' => 10,
    ],

    'pricing_discounts' => [
        'Standard' => 0.0,
        'Plus' => 0.0,
        'Ultra' => 0.0,
        'Max' => 0.0,
        'Family' => 0.0,
        'Family Plus' => 0.0,
        'Family Ultra' => 0.0,
        'Family Max' => 0.0,
    ],

    'balance_history_limit' => 20,

    /*
    |--------------------------------------------------------------------------
    | Points to TND Conversion
    |--------------------------------------------------------------------------
    |
    | Administrators can control the loyalty points payment system through
    | these configuration values. Points are converted to TND at the defined
    | rate. The minimum and maximum caps prevent abuse.
    |
    */
    'points_to_tnd' => [
        'rate' => 100,
        'minimum_payment_points' => 100,
        'maximum_per_transaction' => 10000,
    ],
];
