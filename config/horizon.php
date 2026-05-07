<?php

use Illuminate\Support\Str;

$app = Str::slug(env('APP_NAME', 'app'), '_');

return [

    'domain' => env('HORIZON_DOMAIN', null),

    'path' => env('HORIZON_PATH', 'horizon'),

    'use' => 'queue',

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    'middleware' => ['web'],

    'waits' => [
        'redis:broadcast' => 10,
        'redis:topup' => 15,
        'redis:bank' => 30,
        'redis:lotto' => 45,
        'redis:default' => 60,
        'redis:low' => 90,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => true,

    'memory_limit' => 128,

    'environments' => [
        'production' => [
            'supervisor-broadcast' => [
                'workers-name' => env('APP_NAME', 'laravel').'-broadcast',
                'connection' => 'redis',
                'queue' => ['broadcast'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 30,
                'sleep' => 0,
                'memory' => 128,
            ],
            'supervisor-topup' => [
                'workers-name' => env('APP_NAME', 'laravel').'-topup',
                'connection' => 'redis',
                'queue' => ['topup'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 3,
                'timeout' => 60,
                'sleep' => 1,
                'memory' => 128,
            ],
            'supervisor-bank' => [
                'workers-name' => env('APP_NAME', 'laravel').'-bank',
                'connection' => 'redis',
                'queue' => ['bank'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 3,
                'timeout' => 90,
                'sleep' => 1,
                'memory' => 128,
            ],
            'supervisor-lotto' => [
                'workers-name' => env('APP_NAME', 'laravel').'-lotto',
                'connection' => 'redis',
                'queue' => ['lotto'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 3,
                'timeout' => 120,
                'sleep' => 1,
                'memory' => 128,
            ],
            'supervisor-default' => [
                'workers-name' => env('APP_NAME', 'laravel').'-default',
                'connection' => 'redis',
                'queue' => ['default'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 3,
                'timeout' => 120,
                'sleep' => 2,
                'memory' => 128,
            ],
            'supervisor-low' => [
                'workers-name' => env('APP_NAME', 'laravel').'-low',
                'connection' => 'redis',
                'queue' => ['low'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 2,
                'timeout' => 90,
                'sleep' => 5,
                'memory' => 128,
            ],
        ],
    ],

];
