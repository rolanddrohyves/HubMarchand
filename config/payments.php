<?php

return [
    'default' => env('PAYMENT_PROVIDER', 'manual'),
    'enabled' => filter_var(env('PAYMENTS_ENABLED', false), FILTER_VALIDATE_BOOL),
    'providers' => [
        'manual' => [
            'enabled' => true,
        ],
        'wave' => [
            'enabled' => filter_var(env('WAVE_ENABLED', false), FILTER_VALIDATE_BOOL),
            'base_url' => env('WAVE_BASE_URL', 'https://api.wave.com'),
            'api_key' => env('WAVE_API_KEY'),
        ],
        'orange_money' => [
            'enabled' => filter_var(env('ORANGE_MONEY_ENABLED', false), FILTER_VALIDATE_BOOL),
            'base_url' => env('ORANGE_MONEY_BASE_URL'),
            'client_id' => env('ORANGE_MONEY_CLIENT_ID'),
            'client_secret' => env('ORANGE_MONEY_CLIENT_SECRET'),
        ],
        'card' => [
            'enabled' => filter_var(env('CARD_PAYMENTS_ENABLED', false), FILTER_VALIDATE_BOOL),
            'provider' => env('CARD_PAYMENT_PROVIDER', 'stripe'),
            'secret' => env('CARD_PAYMENT_SECRET'),
        ],
    ],
];
