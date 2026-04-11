<?php

use Illuminate\Support\Str;

$app = Str::slug(env('APP_NAME', 'app'), '_');

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => env('HORIZON_DOMAIN', null),

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => env('HORIZON_PATH', 'horizon'),

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'queue',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_').'_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:topup' => 15,   // เดิม 60
        'redis:kbank' => 30,
        'redis:broadcasts' => 30,
        'redis:tw' => 60,
        'redis:bay' => 60,
        'redis:ktb' => 60,
        'redis:scb' => 60,
        'redis:batch' => 60,
        'redis:cashback' => 60,
        'redis:ic' => 60,
        'redis:lotto' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080, // 7 วัน
        'failed' => 10080, // 7 วัน
        'monitored' => 10080, // 7 วัน
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => true,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 128,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */
    'environments' => [
        'production' => [
            'supervisor-topup' => [
                'workers-name' => env('APP_NAME', 'laravel').'-topup',
                'connection' => 'redis',
                'queue' => ['topup'],
                'balance' => 'simple',   // หรือคง 'auto' + min/max > 1 ก็ได้
                'minProcesses' => 1,          // เดิม 1
                'maxProcesses' => 1,          // เดิม 1
                'tries' => 1,          // เดิม 0 (ไม่จำกัด) → กำหนดให้ชัด
                'timeout' => 60,         // ให้สอดคล้อง Job::$timeout
                'sleep' => 1,          // ลด latency ตอนคิวฟ้าแลบ
                'memory' => 128,
            ],

            // 🏦 broadcast แยกไว้โอเคแล้ว
            'supervisor-1' => [
                'workers-name' => env('APP_NAME', 'laravel').'-broadcasts',
                'connection' => 'redis',
                'queue' => ['broadcasts'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 60,
                'sleep' => 1,
                'memory' => 128,
            ],

            // 📅 แยก kbank ออกจากงานรายวันถ้าต้องการความไว (ตัวเลือกแนะนำ)
            'supervisor-kbank' => [
                'workers-name' => env('APP_NAME', 'laravel').'-kbank',
                'connection' => 'redis',
                'queue' => ['kbank'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 60,
                'sleep' => 1,
                'memory' => 128,
            ],

            // 📅 งานเบา รายวัน / batch อื่น ๆ
            'supervisor-daily' => [
                'workers-name' => env('APP_NAME', 'laravel').'-daily',
                'connection' => 'redis',
                'queue' => ['cashback', 'ic'],
                'balance' => 'simple',
                'minProcesses' => 1,
                'maxProcesses' => 1,
                'tries' => 1,
                'timeout' => 60,
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
        ],
    ],

];
