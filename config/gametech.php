<?php

return [
    'api_url' => env('APP_API_URL', 'api'),
    'public_cache_minutes' => (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5),
    'cashback' => [
        'start' => [
            'mode' => env('CASHBACK_START_MODE', 'range'),
            'target' => env('CASHBACK_START_TARGET', 'wallet'),
        ],
    ],
];
