<?php

return [
    'timezone' => env('LOTTO_AUTO_RESULT_TIMEZONE', (string) config('app.timezone', 'Asia/Bangkok')),

    'fetch' => [
        // Hard stop for auto fetch attempts after result_at.
        'max_window_minutes' => (int) env('LOTTO_AUTO_RESULT_MAX_WINDOW_MINUTES', 1440),
    ],

    'hardening' => [
        'alerts' => [
            'enabled' => (bool) env('LOTTO_AUTO_RESULT_ALERT_ENABLED', true),
            'telegram_endpoint' => (string) env('LOTTO_AUTO_RESULT_ALERT_ENDPOINT', 'notify/send'),
            'telegram_queue' => (string) env('LOTTO_AUTO_RESULT_ALERT_QUEUE', 'cashback'),
            'log_channel' => (string) env('LOTTO_AUTO_RESULT_ALERT_LOG_CHANNEL', 'daily'),
            'dedupe_seconds' => (int) env('LOTTO_AUTO_RESULT_ALERT_DEDUPE_SECONDS', 21600),
        ],

        'rate_limit' => [
            'enabled' => (bool) env('LOTTO_AUTO_RESULT_RATE_LIMIT_ENABLED', true),
            'per_source_per_minute' => (int) env('LOTTO_AUTO_RESULT_RATE_LIMIT_PER_SOURCE_PER_MINUTE', 30),
            'window_seconds' => (int) env('LOTTO_AUTO_RESULT_RATE_LIMIT_WINDOW_SECONDS', 60),
        ],

        'metrics' => [
            'default_window_hours' => (int) env('LOTTO_AUTO_RESULT_METRICS_WINDOW_HOURS', 24),
        ],
    ],
];
