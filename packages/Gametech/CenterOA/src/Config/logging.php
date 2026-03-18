<?php

return [
    'channels' => [
        'center_oa' => [
            'driver' => 'daily',
            'path' => storage_path('logs/center_oa/daily.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'line_oa' => [
            'driver' => 'daily',
            'path' => storage_path('logs/line_oa/daily.log'),
            'level' => 'debug',
            'days' => 14,
        ],

        'facebook_oa' => [
            'driver' => 'daily',
            'path' => storage_path('logs/facebook_oa/daily.log'),
            'level' => 'debug',
            'days' => 14,
        ],
    ],
];
