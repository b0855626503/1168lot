<?php
	
	return [
		'min_deposit' => env('TLCONNECTPAY_PAYMENT_MIN_DEPOSIT', 0),
		'api_url' => env('TLCONNECTPAY_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('TLCONNECTPAY_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('TLCONNECTPAY_PAYMENT_SECRET_KEY', null),
		'client_id' => env('TLCONNECTPAY_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('TLCONNECTPAY_PAYMENT_MERCHANT_NO', null),
		'api_key' => env('TLCONNECTPAY_PAYMENT_API_KEY', null),
		'channel_type' => env('TLCONNECTPAY_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
	];
