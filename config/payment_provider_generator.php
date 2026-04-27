<?php

return [
    'reference_provider' => 'smkpay',

    'default_mode' => env('PAYMENT_PROVIDER_GENERATOR_MODE', 'dry_run'),

    'interactive' => [
        'ask_when_missing_withdraw' => true,
        'ask_when_missing_callback' => true,
        'ask_when_missing_balance' => true,
        'ask_when_signature_unknown' => true,
    ],

    'paths' => [
        'payment_library' => 'packages/Gametech/Payment/src/Libraries',
        'payment_controller' => 'packages/Gametech/Payment/src/Http/Controllers',
        'auto_jobs' => 'packages/Gametech/Auto/src/Jobs',
        'routes_hint' => 'packages/Gametech/FrontendApi/src/Routes/api.php',
        'base_output' => 'storage/app/mcp/payment-providers',
    ],

    'whitelist_write_paths' => [
        'packages/Gametech/Payment/src/Libraries',
        'packages/Gametech/Payment/src/Http/Controllers',
        'packages/Gametech/Auto/src/Jobs',
        'config',
        'docs/internal/03_DOMAINS',
        'docs/04_PLANS',
        'storage/app/mcp/payment-providers',
    ],

    'blocked_paths' => [
        '.env',
        'vendor',
        'storage/logs',
        'bootstrap/cache',
        'public',
    ],

    'status_mapping' => [
        'pending' => 'pending',
        'processing' => 'pending',
        'pending_review' => 'pending',
        'in_review' => 'pending',
        'waiting' => 'pending',
        'wait' => 'pending',
        'created' => 'pending',
        'success' => 'completed',
        'paid' => 'completed',
        'completed' => 'completed',
        'approved' => 'completed',
        'expired' => 'expired',
        'failed' => 'failed',
        'fail' => 'failed',
        'rejected' => 'rejected',
        'cancelled' => 'failed',
        'canceled' => 'failed',
        'refunded' => 'refunded',
    ],

    'terminal_statuses' => [
        'completed',
        'failed',
        'rejected',
        'refunded',
    ],
];
