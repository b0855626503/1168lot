<?php

return [
    'enabled'            => env('XRAY_ENABLED', true),
    'threshold_ms'       => env('XRAY_THRESHOLD_MS', 100),
    'sql_threshold_ms'   => env('XRAY_SQL_MS', 100),
    'redis_threshold_ms' => env('XRAY_REDIS_MS', 100),
    'http_threshold_ms'  => env('XRAY_HTTP_MS', 100),
    'sample'             => env('XRAY_SAMPLE', 100), // %
    'channel'            => env('XRAY_CHANNEL', 'slowlog'),
    'expose_header'      => env('XRAY_EXPOSE_HEADER', false),
    'ignore_paths'       => ['/health', '/__ping', '/__static_ping'],
    'top_sql'            => 3,
    'top_repeated_sql'   => 5,
];
