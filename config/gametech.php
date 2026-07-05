<?php

return [
    'api_url' => env('APP_API_URL', 'api'),
    'public_cache_minutes' => (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5),
    'cashback' => [
        'start' => [
            'schedule_enabled' => env('CASHBACK_SCHEDULE_ENABLED', false),
            'mode' => env('CASHBACK_START_MODE', 'range'),
            'target' => env('CASHBACK_START_TARGET', 'wallet'),
            'promo_policy' => env('CASHBACK_START_PROMO_POLICY', 'exclude_member'),
            'no_balance' => env('CASHBACK_START_NO_BALANCE', false),
            'deduct_lotto' => env('CASHBACK_START_DEDUCT_LOTTO', false),
        ],
    ],
    'ic' => [
        'start' => [
            'schedule_enabled' => env('IC_SCHEDULE_ENABLED', false),
            'mode' => env('IC_START_MODE', 'range'),
            'target' => env('IC_START_TARGET', 'wallet'),
            'promo_policy' => env('IC_START_PROMO_POLICY', 'exclude_member'),
            'no_balance' => env('IC_START_NO_BALANCE', false),
            'deduct_lotto' => env('IC_START_DEDUCT_LOTTO', false),
        ],
    ],
    'api_external_url' => env('APP_EXTERNAL_API_URL', 'api2.joker.com'),
];
