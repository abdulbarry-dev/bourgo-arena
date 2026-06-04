<?php

return [
    'individual' => [
        [
            'label' => 'Standard',
            'multiplier' => 1.0,
            'required_subscriptions' => 0,
            'requirements' => 'Default membership tier.',
            'benefits' => 'Basic access to arena facilities.',
        ],
        [
            'label' => 'Plus',
            'multiplier' => 1.2,
            'required_subscriptions' => 2,
            'requirements' => 'Requires 2 active subscriptions.',
            'benefits' => '20% boost to loyalty points earning.',
        ],
        [
            'label' => 'Ultra',
            'multiplier' => 1.5,
            'required_subscriptions' => 3,
            'requirements' => 'Requires 3 active subscriptions.',
            'benefits' => '50% boost to loyalty points earning.',
        ],
        [
            'label' => 'Max',
            'multiplier' => 2.0,
            'required_subscriptions' => 4,
            'requirements' => 'Requires 4 or more active subscriptions.',
            'benefits' => '100% boost to loyalty points earning.',
        ],
    ],
    'family' => [
        [
            'label' => 'Family',
            'multiplier' => 1.0,
            'required_subscriptions' => 0,
            'requirements' => 'Default family membership.',
            'benefits' => 'Basic access for all linked family members.',
        ],
        [
            'label' => 'Family Plus',
            'multiplier' => 1.2,
            'required_subscriptions' => 2,
            'requirements' => 'Requires 2 active family subscriptions.',
            'benefits' => '20% boost to family loyalty points.',
        ],
        [
            'label' => 'Family Ultra',
            'multiplier' => 1.5,
            'required_subscriptions' => 3,
            'requirements' => 'Requires 3 active family subscriptions.',
            'benefits' => '50% boost to family loyalty points.',
        ],
        [
            'label' => 'Family Max',
            'multiplier' => 2.0,
            'required_subscriptions' => 4,
            'requirements' => 'Requires 4 or more active family subscriptions.',
            'benefits' => '100% boost to family loyalty points.',
        ],
    ],
];
