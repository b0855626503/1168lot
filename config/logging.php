<?php

use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\SyslogUdpHandler;

if (! function_exists('log_path')) {
    function log_path(string $file): string
    {
        $path = storage_path('logs/'.$file);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return $path;
        }

        return $path;
    }
}

return [

    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | This option defines the default log channel that gets used when writing
    | messages to the logs. The name specified in this option should match
    | one of the channels defined in the "channels" configuration array.
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels for your application. Out of
    | the box, Laravel uses the Monolog PHP logging library. This gives
    | you a variety of powerful log handlers / formatters to utilize.
    |
    | Available Drivers: "single", "daily", "slack", "syslog",
    |                    "errorlog", "monolog",
    |                    "custom", "stack"
    |
    */

    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'errorlog', 'syslog'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => log_path('laravel.log'),
            'level' => 'debug',
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => log_path('daily.log'),
            'level' => 'warning',
            'days' => 3,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => 'debug',
            'handler' => SyslogUdpHandler::class,
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => 'debug',
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => 'debug',
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => log_path('emergency.log'),
        ],

        'cashback' => [
            'driver' => 'single',
            'path' => log_path('cashback_create.log'),
            'level' => 'info',
        ],

        'slowlog' => [
            'driver' => 'daily',
            'path' => log_path('slow/slow-requests.log'),
            'level' => 'info',
        ],

        'slow' => [
            'driver' => 'daily',
            'path' => log_path('slow/daily.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'check' => [
            'driver' => 'daily',
            'path' => log_path('check/daily.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'center_oa' => [
            'driver' => 'daily',
            'path' => log_path('center_oa/daily.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'line_oa' => [
            'driver' => 'daily',
            'path' => log_path('line_oa/daily.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'facebook_oa' => [
            'driver' => 'daily',
            'path' => log_path('facebook_oa/daily.log'),
            'level' => 'debug',
            'days' => 7,
        ],

        'wildpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wildpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wildpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wildpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'wildpay_cancel_create' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/cancel_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wildpay_cancel_callback' => [
            'driver' => 'daily',
            'path' => log_path('wildpay/cancel_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifu_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('sulifu/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifu_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('sulifu/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifu_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('sulifu/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifu_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('sulifu/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'xpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('xpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('xpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('xpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('xpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'matepay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('matepay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'matepay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('matepay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'gamelog' => [
            'driver' => 'daily',
            'path' => log_path('gamelog/redis.log'),
            'level' => 'debug',
            'days' => 2,
        ],
        'wellpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('wellpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wellpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('wellpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wellpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('wellpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'wellpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('wellpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'kingpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('kingpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'kingpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('kingpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'kingpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('kingpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'kingpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('kingpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'cloudpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('cloudpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'cloudpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('cloudpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'cloudpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('cloudpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'cloudpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('cloudpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'omnipay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('omnipay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'omnipay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('omnipay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'omnipay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('omnipay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'omnipay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('omnipay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'payonex_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('payonex/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'payonex_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('payonex/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'payonex_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('payonex/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'payonex_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('payonex/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'apay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('apay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'apay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('apay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'apay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('apay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'apay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('apay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'sulifupay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('sulifupay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifupay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('sulifupay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifupay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('sulifupay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'sulifupay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('sulifupay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'tlconnectpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('tlconnectpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'tlconnectpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('tlconnectpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'tlconnectpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('tlconnectpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'tlconnectpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('tlconnectpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'onpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('onpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'onpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('onpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'onpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('onpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'onpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('onpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'maxpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('maxpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'maxpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('maxpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'maxpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('maxpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'maxpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('maxpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'worldpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('worldpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'worldpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('worldpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'worldpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('worldpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'worldpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('worldpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'autotransfer_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('autotransfer/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'autotransfer_check_ma' => [
            'driver' => 'daily',
            'path' => log_path('autotransfer/check_ma.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'autotransfer_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('autotransfer/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'autotransfer_api' => [
            'driver' => 'daily',
            'path' => log_path('autotransfer/autotransfer_api.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'smkpay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('smkpay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'smkpay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('smkpay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'smkpay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('smkpay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'smkpay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('smkpay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'smkpay_api' => [
            'driver' => 'daily',
            'path' => log_path('smkpay/api.log'),
            'level' => 'info',
            'days' => 14,
        ],
        'api' => [
            'driver' => 'daily',
            'path' => log_path('api.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_deposit_create' => [
            'driver' => 'daily',
            'path' => log_path('xepay/deposit_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_deposit_callback' => [
            'driver' => 'daily',
            'path' => log_path('xepay/deposit_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_withdraw_create' => [
            'driver' => 'daily',
            'path' => log_path('xepay/withdraw_create.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_withdraw_callback' => [
            'driver' => 'daily',
            'path' => log_path('xepay/withdraw_callback.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_balance' => [
            'driver' => 'daily',
            'path' => log_path('xepay/balance.log'),
            'level' => 'info',
            'days' => 14,
        ],

        'xepay_api' => [
            'driver' => 'daily',
            'path' => log_path('xepay/api.log'),
            'level' => 'info',
            'days' => 14,
        ],

    ],

];
