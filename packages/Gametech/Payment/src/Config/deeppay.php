<?php

return [
    'api_url' => env('DEEPPAY_API_URL', 'https://merchant.linkdeepcode.me'),
    'username' => env('DEEPPAY_USERNAME'),
    'api_key' => env('DEEPPAY_API_KEY'),
    'secret_key' => env('DEEPPAY_SECRET_KEY'),
    'pin_code' => env('DEEPPAY_PIN_CODE'),

    'currency' => env('DEEPPAY_CURRENCY', 'THB'),
    'lang' => env('DEEPPAY_LANG', 'th'),

    'min_deposit' => env('DEEPPAY_MIN_DEPOSIT', 100),
    'min_withdraw' => env('DEEPPAY_MIN_WITHDRAW', 100),
    'system_bank_code' => env('DEEPPAY_SYSTEM_BANK_CODE', 313),

    'deposit_callback_url' => env('DEEPPAY_DEPOSIT_CALLBACK_URL'),
    'withdraw_callback_url' => env('DEEPPAY_WITHDRAW_CALLBACK_URL'),

    'verify_callback_via_transaction' => env('DEEPPAY_VERIFY_CALLBACK_VIA_TRANSACTION', true),

    'timeout' => env('DEEPPAY_TIMEOUT', 30),
    'connect_timeout' => env('DEEPPAY_CONNECT_TIMEOUT', 15),
    'debug_log' => env('DEEPPAY_DEBUG_LOG', true),
    'log_channel' => env('DEEPPAY_LOG_CHANNEL', 'deeppay_api'),

    'deposit_view' => env('DEEPPAY_DEPOSIT_VIEW', 'topup.box.onpay_new'),

    'bank_code_map' => [
        'BBL' => '002',
        'KBANK' => '004',
        'KTB' => '006',
        'TTB' => '011',
        'TMB' => '011',
        'SCB' => '014',
        'CIMBT' => '022',
        'UOBT' => '024',
        'BAY' => '025',
        'GSB' => '030',
        'GHB' => '033',
        'BAAC' => '034',
        'KKP' => '069',
        'TRUE' => '000',
    ],
];
