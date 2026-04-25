<?php

return [
    'api_url' => env('APP_API_URL', 'api'),
    'public_cache_minutes' => (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5),
    'cashback' => [
        'start' => [
            'mode' => env('CASHBACK_START_MODE', 'range'),
            'target' => env('CASHBACK_START_TARGET', 'wallet'),
            'promo_policy' => env('CASHBACK_START_PROMO_POLICY', 'exclude_member'),
        ],
    ],
    'api_external_url' => env('APP_EXTERNAL_API_URL', 'api2.joker.com'),
];
