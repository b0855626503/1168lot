<?php
	
	return [
		'min_deposit' => env('WORLDPAY_PAYMENT_MIN_DEPOSIT', 0),
		'min_withdraw' => env('WORLDPAY_PAYMENT_MIN_WITHDRAW', 0),
		'pin_withdraw' => env('WORLDPAY_PAYMENT_PIN_WITHDRAW', 0),
		'api_url' => env('WORLDPAY_PAYMENT_API_URL', 'https://example.com'),
		'partner_id' => env('WORLDPAY_PAYMENT_PARTNER_ID', null),
		'secret_key' => env('WORLDPAY_PAYMENT_SECRET_KEY', null),
		'client_id' => env('WORLDPAY_PAYMENT_CLIENT_ID', null),
		'merchant_no' => env('WORLDPAY_PAYMENT_MERCHANT_NO', null),
		'api_key' => env('WORLDPAY_PAYMENT_API_KEY', null),
		'channel_type' => env('WORLDPAY_PAYMENT_CHANNEL_TYPE', null),
		'deposit_range' => [
			200, 500, 1000, 1500, 2000, 3000
		],
	];
