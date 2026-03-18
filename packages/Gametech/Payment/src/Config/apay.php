<?php
	
	return [
		'min_deposit' => env('APAY_PAYMENT_MIN_DEPOSIT', 0),
		'api_url' => env('APAY_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('APAY_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('APAY_PAYMENT_SECRET_KEY', null),
		'client_id' => env('APAY_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('APAY_PAYMENT_MERCHANT_NO', null),
		'username' => env('APAY_PAYMENT_USERNAME', null),
		'api_key' => env('APAY_PAYMENT_API_KEY', null),
		'channel_type' => env('APAY_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
	];
