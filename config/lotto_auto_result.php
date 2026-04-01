<?php

return [
    'timezone' => env('LOTTO_AUTO_RESULT_TIMEZONE', (string) config('app.timezone', 'Asia/Bangkok')),

    'fetch' => [
        // Hard stop for auto fetch attempts after result_at.
        'max_window_minutes' => (int) env('LOTTO_AUTO_RESULT_MAX_WINDOW_MINUTES', 1440),
    ],

    'retry' => [
        'max_attempts' => (int) env('LOTTO_AUTO_RESULT_RETRY_MAX_ATTEMPTS', 27),
        'base_backoff_seconds' => (int) env('LOTTO_AUTO_RESULT_RETRY_BASE_BACKOFF_SECONDS', 10),
        'max_backoff_seconds' => (int) env('LOTTO_AUTO_RESULT_RETRY_MAX_BACKOFF_SECONDS', 300),
    ],

    'browser_worker' => [
        'enabled' => (bool) env('LOTTO_AUTO_RESULT_BROWSER_WORKER_ENABLED', false),
        'lock_ttl_seconds' => (int) env('LOTTO_AUTO_RESULT_BROWSER_LOCK_TTL_SECONDS', 120),
        'cache_ttl_seconds' => (int) env('LOTTO_AUTO_RESULT_BROWSER_CACHE_TTL_SECONDS', 180),
        'hard_timeout_seconds' => (int) env('LOTTO_AUTO_RESULT_BROWSER_HARD_TIMEOUT_SECONDS', 60),
        'max_captured_responses' => (int) env('LOTTO_AUTO_RESULT_BROWSER_MAX_CAPTURED_RESPONSES', 3),
    ],

    'browser_runtime' => [
        'enabled' => (bool) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_ENABLED', (bool) env('LOTTO_AUTO_RESULT_BROWSER_WORKER_ENABLED', false)),
        'node_binary' => (string) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_NODE_BINARY', 'node'),
        'worker_script' => (string) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_WORKER_SCRIPT', base_path('scripts/lotto/browser_runtime_worker.js')),
        'schema_version' => 1,

        'rollout' => [
            'whitelist_source_ids' => array_values(array_filter(array_map(
                static fn (string $id): int => (int) trim($id),
                explode(',', (string) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_WHITELIST_SOURCE_IDS', ''))
            ), static fn (int $id): bool => $id > 0)),
        ],

        'concurrency' => [
            'global' => max(1, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_GLOBAL_CONCURRENCY', 5)),
            'per_source' => max(1, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_PER_SOURCE_CONCURRENCY', 1)),
            'per_domain' => max(1, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_PER_DOMAIN_CONCURRENCY', 2)),
        ],

        'timeouts' => [
            'overall_seconds' => max(10, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_OVERALL_TIMEOUT_SECONDS', 60)),
        ],

        'artifacts' => [
            'base_dir' => (string) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_ARTIFACT_BASE_DIR', storage_path('app/lotto/browser-runtime')),
            'retention_days' => max(1, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_ARTIFACT_RETENTION_DAYS', 7)),
            'max_bytes_per_run' => max(1024, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_ARTIFACT_MAX_BYTES', 5 * 1024 * 1024)),
            'preview_bytes' => max(256, (int) env('LOTTO_AUTO_RESULT_BROWSER_RUNTIME_PREVIEW_BYTES', 16 * 1024)),
        ],
    ],

    'hardening' => [
        'alerts' => [
            'enabled' => (bool) env('LOTTO_AUTO_RESULT_ALERT_ENABLED', true),
            'telegram_endpoint' => (string) env('LOTTO_AUTO_RESULT_ALERT_ENDPOINT', 'notify/send'),
            'telegram_queue' => (string) env('LOTTO_AUTO_RESULT_ALERT_QUEUE', 'broadcasts'),
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

    'internal_result_sources' => [
        'timeout_seconds' => max(1, (int) env('LOTTO_INTERNAL_RESULT_TIMEOUT_SECONDS', 15)),
        'shared_key' => (string) env('LOTTO_INTERNAL_RESULT_SHARED_KEY', ''),
        'header_name' => (string) env('LOTTO_INTERNAL_RESULT_SHARED_HEADER', 'X-Lotto-Internal-Key'),
        'exphuay' => [
            'browser_fallback_enabled' => filter_var((string) env('LOTTO_EXPHUAY_BROWSER_FALLBACK', 'true'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false,
            'browser_fallback_timeout_seconds' => max(10, (int) env('LOTTO_EXPHUAY_BROWSER_FALLBACK_TIMEOUT_SECONDS', 60)),
            'browser_wait_until' => (string) env('LOTTO_EXPHUAY_BROWSER_WAIT_UNTIL', 'domcontentloaded'),
            'browser_timeout_ms' => max(10000, (int) env('LOTTO_EXPHUAY_BROWSER_TIMEOUT_MS', 60000)),
        ],
    ],
];
