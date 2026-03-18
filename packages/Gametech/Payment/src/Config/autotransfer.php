<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AutoTransfer (Auto Transfer) Integration
    |--------------------------------------------------------------------------
    | Provider name: AutoTransfer
    |
    | ENDPOINT_URL: Base URL of Auto Transfer service (no trailing slash preferred)
    | API_KEY: Credential for outbound requests (withdraw/send slip/account listing)
    | SOURCE_SYSTEM_NAME: Credential for outbound requests
    |
    | TRIGGERED_APIKEY: Optional inbound apikey header for deposit/withdraw callback
    | CHECK_MA_APIKEY: Optional inbound apikey header for maintenance check
    |
    | TIMEOUT: HTTP timeout in seconds
    */

    'endpoint_url' => env('AUTOTRANSFER_ENDPOINT_URL', 'https://example.com'),
    'api_key' => env('AUTOTRANSFER_API_KEY', null),
    'source_system_name' => env('AUTOTRANSFER_SOURCE_SYSTEM_NAME', null),

    // inbound (optional)
    'triggered_apikey' => env('AUTOTRANSFER_TRIGGERED_APIKEY', null),
    'check_ma_apikey' => env('AUTOTRANSFER_CHECK_MA_APIKEY', null),

    // behavior
    'timeout' => (int) env('AUTOTRANSFER_TIMEOUT', 20),

    // safety switch (force maintenance response)
    'force_maintenance' => env('AUTOTRANSFER_FORCE_MAINTENANCE', 'N'), // Y/N
];
