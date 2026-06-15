<?php

return [
    'api_url' => env('WEALTHPAY_API_URL', 'https://api.wealthwave.io'),
    'merchant_id' => env('WEALTHPAY_MERCHANT_ID'),
    'token' => env('WEALTHPAY_TOKEN'),

    'system_bank_code' => env('WEALTHPAY_SYSTEM_BANK_CODE', 317),

    'min_deposit' => env('WEALTHPAY_MIN_DEPOSIT', 100),
    'min_withdraw' => env('WEALTHPAY_MIN_WITHDRAW', 20),

    'deposit_callback_url' => env('WEALTHPAY_DEPOSIT_CALLBACK_URL'),
    'withdraw_callback_url' => env('WEALTHPAY_WITHDRAW_CALLBACK_URL'),

    'timeout' => env('WEALTHPAY_TIMEOUT', 30),
    'connect_timeout' => env('WEALTHPAY_CONNECT_TIMEOUT', 15),
    'debug_log' => env('WEALTHPAY_DEBUG_LOG', true),
    'log_channel' => env('WEALTHPAY_LOG_CHANNEL', 'wealthpay_api'),

    'deposit_view' => env('WEALTHPAY_DEPOSIT_VIEW', 'topup.box.onpay_new'),

    'secret_key' => env('WEALTHPAY_SECRET_KEY'),

    'verify_callback_signature' => env('WEALTHPAY_VERIFY_CALLBACK_SIGNATURE', false),

    'bank_code_map' => [
        'KKP' => 'KK',
        'LHBANK' => 'LH',
        'GHBANK' => 'GHB',
    ],
];
