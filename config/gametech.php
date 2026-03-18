<?php

return [
    'api_url' => env('APP_API_URL', 'api'),
    'public_cache_minutes' => (int) env('PROVIDER_PUBLIC_CACHE_MINUTES', 5),
];
