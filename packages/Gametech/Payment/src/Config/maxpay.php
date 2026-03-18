<?php
	
	return [
		'min_deposit' => env('MAXPAY_PAYMENT_MIN_DEPOSIT', 0),
		'api_url' => env('MAXPAY_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('MAXPAY_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('MAXPAY_PAYMENT_SECRET_KEY', null),
		'client_id' => env('MAXPAY_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('MAXPAY_PAYMENT_MERCHANT_NO', null),
		'api_key' => env('MAXPAY_PAYMENT_API_KEY', null),
		'channel_type' => env('MAXPAY_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
	];
