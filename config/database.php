<?php

use Illuminate\Support\Str;

$app = Str::slug(env('APP_NAME', 'laravel'), '_');

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8',
            'collation' => 'utf8_general_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'schema' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
        ],

        'mongodb' => [
            'driver' => 'mongodb',
            'dsn' => env('MONGODB_DSN'), // ถ้าใช้ SRV ก็ชี้ตรงนี้ได้
            'database' => env('MONGODB_DATABASE', 'app'),
            'options' => [
                // ช่วยเวลาเริ่มเชื่อมต่อครั้งแรก
                'serverSelectionTryOnce' => false,
                'serverSelectionTimeoutMS' => 10000,
                'connectTimeoutMS'         => 3000,
                'socketTimeoutMS'          => 10000,
                'retryReads'               => true,
                'retryWrites'              => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', $app . '_database_'),
        ],

        // Cache / default
        'default' => [
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '139.59.96.78'),
            'password' => env('REDIS_PASSWORD', 'gametech2020'),
            'port' => env('REDIS_PORT', '6379'),

            // รองรับทั้งตัวใหม่/ตัวเก่า
            'database' => env('REDIS_DB', env('REDIS_CACHE_DB', '0')),

            // ---- phpredis persistent ----
            'persistent' => true,
            'persistent_id' => env('REDIS_DEFAULT_PERSISTENT_ID', $app . ':redis:default:' . env('REDIS_DB', env('REDIS_CACHE_DB', '0'))),

            // ---- fail fast (กันค้าง) ----
            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),
        ],

        // Session
        'session' => [
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '139.59.96.78'),
            'password' => env('REDIS_PASSWORD', 'gametech2020'),
            'port' => env('REDIS_PORT', '6379'),

            // ตัวใหม่: REDIS_SESSION_DB, fallback: REDIS_CACHE_DB, fallback สุดท้าย: 1
            'database' => env('REDIS_SESSION_DB', env('REDIS_CACHE_DB', '1')),

            'persistent' => true,
            'persistent_id' => env(
                'REDIS_SESSION_PERSISTENT_ID',
                $app . ':redis:session:' . env('REDIS_SESSION_DB', env('REDIS_CACHE_DB', '1'))
            ),

            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),
        ],

        // Queue
        'queue' => [
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '139.59.96.78'),
            'password' => env('REDIS_PASSWORD', 'gametech2020'),
            'port' => env('REDIS_PORT', '6379'),

            // ตัวใหม่: REDIS_QUEUE_DB, fallback: REDIS_CACHE_DB, fallback สุดท้าย: 2
            'database' => env('REDIS_QUEUE_DB', env('REDIS_CACHE_DB', '2')),

            'persistent' => true,
            'persistent_id' => env(
                'REDIS_QUEUE_PERSISTENT_ID',
                $app . ':redis:queue:' . env('REDIS_QUEUE_DB', env('REDIS_CACHE_DB', '2'))
            ),

            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),
        ],

        // Game
        'game' => [
            'scheme' => 'tcp',
            'host' => env('REDIS_HOST', '139.59.96.78'),
            'password' => env('REDIS_PASSWORD', 'gametech2020'),
            'port' => env('REDIS_PORT', '6379'),

            // ตัวใหม่: REDIS_GAME_DB, fallback: REDIS_CACHE_DB, fallback สุดท้าย: 3
            'database' => env('REDIS_GAME_DB', env('REDIS_CACHE_DB', '3')),

            'persistent' => true,
            'persistent_id' => env(
                'REDIS_GAME_PERSISTENT_ID',
                $app . ':redis:game:' . env('REDIS_GAME_DB', env('REDIS_CACHE_DB', '3'))
            ),

            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),
        ],

        'gamelog' => [
            'host' => env('REDIS_GAMELOG_HOST', '127.0.0.1'),
            'password' => env('REDIS_GAMELOG_PASSWORD', null),
            'port' => env('REDIS_GAMELOG_PORT', null),
            'database' => env('REDIS_GAMELOG_DB', '0'),
        ],

        // Fanout
        'fanout' => [
            'host' => env('REDIS_HOST', '139.59.96.78'),
            'password' => env('REDIS_PASSWORD', 'gametech2020'),
            'port' => env('REDIS_PORT', '6379'),

            // ตัวใหม่: REDIS_FANOUT_DB, fallback: 5 (อย่า fallback ไป REDIS_CACHE_DB เพราะ fanout ต้องนิ่ง)
            'database' => env('REDIS_FANOUT_DB', '5'),

            'persistent' => true,
            'persistent_id' => env(
                'REDIS_FANOUT_PERSISTENT_ID',
                $app . ':redis:fanout:' . env('REDIS_FANOUT_DB', '5')
            ),

            'timeout' => (float) env('REDIS_TIMEOUT', 1.0),
            'read_timeout' => (float) env('REDIS_READ_TIMEOUT', 1.0),

            'options' => [
                'prefix' => env('FANOUT_PREFIX', 'fanout_'), // ควรเหมือนกันทุกเว็บ
            ],
        ],
    ],

];