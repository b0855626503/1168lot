<?php
	
	return [
		'min_deposit' => env('WELLPAY_PAYMENT_MIN_DEPOSIT', 0),
		'api_url' => env('WELLPAY_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('WELLPAY_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('WELLPAY_PAYMENT_SECRET_KEY', null),
		'client_id' => env('WELLPAY_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('WELLPAY_PAYMENT_MERCHANT_NO', null),
		'api_key' => env('WELLPAY_PAYMENT_API_KEY', null),
		'channel_type' => env('WELLPAY_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
	];
