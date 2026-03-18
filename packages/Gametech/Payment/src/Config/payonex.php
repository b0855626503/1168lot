<?php
	
	return [
		'min_deposit' => env('PAYONEX_PAYMENT_MIN_DEPOSIT', 0),
		'api_url' => env('PAYONEX_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('PAYONEX_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('PAYONEX_PAYMENT_SECRET_KEY', null),
		'access_key' => env('PAYONEX_PAYMENT_ACCESS_KEY', null),
		'client_id' => env('PAYONEX_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('PAYONEX_PAYMENT_MERCHANT_NO', null),
		'api_key' => env('PAYONEX_PAYMENT_API_KEY', null),
		'channel_type' => env('PAYONEX_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
        'auth_mode' => env('PAYONEX_AUTH_MODE', 'db'),
        'db_token_ttl_hours' => 24,
	];
