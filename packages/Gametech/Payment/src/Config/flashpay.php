<?php

return [
    /*
     * FlashPay Payment Gateway
     * Docs: https://flashpaysolution.com/docs
     * Base: https://api.flashpaysolution.com/api/v1
     * System bank code: 318
     */
    'api_url' => env('FLASHPAY_API_URL', 'https://api.flashpaysolution.com/api/v1'),

    'api_key' => env('FLASHPAY_API_KEY'),
    'webhook_secret' => env('FLASHPAY_WEBHOOK_SECRET'),
    'webhook_secret_withdraw' => env('FLASHPAY_WEBHOOK_SECRET_WITHDRAW'),

    'system_bank_code' => env('FLASHPAY_SYSTEM_BANK_CODE', 318),

    'min_deposit' => env('FLASHPAY_MIN_DEPOSIT', 1),
    'min_withdraw' => env('FLASHPAY_MIN_WITHDRAW', 1),

    'verify_callback_signature' => env('FLASHPAY_VERIFY_CALLBACK_SIGNATURE', true),

    'deposit_view' => env('FLASHPAY_DEPOSIT_VIEW', 'topup.qr.flashpay'),

    'timeout' => env('FLASHPAY_TIMEOUT', 30),
    'connect_timeout' => env('FLASHPAY_CONNECT_TIMEOUT', 15),

    'debug_log' => env('FLASHPAY_DEBUG_LOG', true),
    'log_channel' => env('FLASHPAY_LOG_CHANNEL', 'flashpay_api'),

    // TODO: get bank codes from FlashPay — only 3 verified from docs
    // Doc examples: SCB, KBANK, KTB — rest are [GUESS] based on common codes
    'bank_code_map' => [
        'BBL' => 'BBL',
        'KBANK' => 'KBANK',
        'KTB' => 'KTB',
        'SCB' => 'SCB',
        'GHBANK' => 'GHB',
        'KKP' => 'KKP',
        'CIMB' => 'CIMB',
        'IBANK' => 'IBANK',
        'TISCO' => 'TISCO',
        'TMB' => 'TMB',
        'BAY' => 'BAY',
        'UOB' => 'UOB',
        'LHBANK' => 'LHB',
        'GSB' => 'GSB',
        'TBANK' => 'TBANK',
        'BAAC' => 'BAAC',
        'TTB' => 'TTB',
    ],
];
