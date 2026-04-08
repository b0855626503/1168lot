<?php

return [
    'min_deposit' => env('XEPAY_MIN_DEPOSIT', 100),
    'min_withdraw' => env('XEPAY_MIN_WITHDRAW', 200),

    'api_url' => env('XEPAY_API_URL', 'https://example.com'),
    'mer_no' => env('XEPAY_MER_NO', null),
    'api_key' => env('XEPAY_API_KEY', null),
    'c_type' => env('XEPAY_C_TYPE', null),

    // สำหรับ routing/callback
    'deposit_notify_url' => env('XEPAY_DEPOSIT_NOTIFY_URL', null),
    'withdraw_notify_url' => env('XEPAY_WITHDRAW_NOTIFY_URL', null),
    'return_url' => env('XEPAY_RETURN_URL', null),

    // provider bank code map: [internal_bank_code => provider_bank_code]
    'bank_code_map' => [],
    'default_bank_code' => env('XEPAY_DEFAULT_BANK_CODE', ''),

    // optional extra params
    'verify_channel_no' => env('XEPAY_VERIFY_CHANNEL_NO', null),
    'default_open_province' => env('XEPAY_DEFAULT_OPEN_PROVINCE', '1'),
    'default_open_city' => env('XEPAY_DEFAULT_OPEN_CITY', '1'),
    'default_player_email' => env('XEPAY_DEFAULT_PLAYER_EMAIL', ''),

    // web display
    'system_bank_code' => env('XEPAY_SYSTEM_BANK_CODE', 314),
    'deposit_view' => env('XEPAY_DEPOSIT_VIEW', 'topup.box.onpay_new'),

    // log
    'debug_log' => env('XEPAY_DEBUG_LOG', true),
    'log_channel' => env('XEPAY_LOG_CHANNEL', 'xepay_api'),
];
