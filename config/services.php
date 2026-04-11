<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'track' => [
        'base_url' => env('TRACK_BASE_URL', 'https://thegrand789.com'),
    ],

    'me2me' => [
        'account_name_url' => env('ME2ME_ACCOUNT_NAME_URL', 'https://me2me.biz/getname.php'),
        'api_key' => env('ME2ME_API_KEY', 'af96aa1c-e1f5-4c22-ab96-7f5453704aa9'),
        'timeout' => (int) env('ME2ME_TIMEOUT', 10),
    ],

];
