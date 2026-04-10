<?php

return [
    'min_deposit' => 200.00,
    'api_url' => env('PAYMENT_API_URL', 'https://example.com'),
    'partner_id' => env('PAYMENT_PARTNER_ID', null),
    'secret_key' => env('PAYMENT_SECRET_KEY', null),
    'merchant_no' => env('PAYMENT_MERCHANT_NO', null),
    'api_key' => env('PAYMENT_API_KEY', null),
    'min_deposit_usd' => 200.00,
    'partner_id' => env('PAYMENT_PARTNER_ID', null),
    'secret_key' => env('PAYMENT_SECRET_KEY', null),
    'merchant_no_usd' => env('PAYMENT_MERCHANT_NO_USD', null),
    'api_key_usd' => env('PAYMENT_API_KEY_USD', null),
];
