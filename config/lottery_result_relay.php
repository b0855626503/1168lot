<?php

use Illuminate\Support\Str;

$appSlug = Str::slug((string) env('APP_NAME', 'laravel'), '-');

return [
    'enabled' => (bool) env('LOTTERY_RESULT_RELAY_ENABLED', false),
    'mode' => (string) env('LOTTERY_RESULT_RELAY_MODE', 'disabled'),
    'stream_connection' => (string) env('LOTTERY_RESULT_RELAY_STREAM_CONNECTION', 'lotto'),
    'stream_key' => (string) env('LOTTERY_RESULT_RELAY_STREAM_KEY', 'lotto:stream:results'),
    'stream_maxlen' => max(100, (int) env('LOTTERY_RESULT_RELAY_STREAM_MAXLEN', 10000)),
    'queue' => (string) env('LOTTERY_RESULT_RELAY_QUEUE', 'lotto'),
    'consumer_group' => (string) env('LOTTERY_RESULT_RELAY_CONSUMER_GROUP', 'relay-'.$appSlug),
    'consumer_name' => (string) env('LOTTERY_RESULT_RELAY_CONSUMER_NAME', $appSlug.'-consumer'),
    'api_base_url' => rtrim((string) env('LOTTERY_RESULT_RELAY_API_BASE_URL', (string) env('APP_URL', '')), '/'),
    'published_marker_prefix' => (string) env('LOTTERY_RESULT_RELAY_PUBLISHED_MARKER_PREFIX', 'lotto:relay:published'),
    'latest_marker_prefix' => (string) env('LOTTERY_RESULT_RELAY_LATEST_MARKER_PREFIX', 'lotto:relay:latest'),
    'log_channel' => (string) env('LOTTERY_RESULT_RELAY_LOG_CHANNEL', 'daily'),
    'upstream_get_lottery_url' => (string) env('LOTTERY_RESULT_RELAY_UPSTREAM_GET_LOTTERY_URL', ''),
];
