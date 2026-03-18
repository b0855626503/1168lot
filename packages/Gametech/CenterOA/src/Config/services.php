<?php

return [
    'translation' => [
        'driver' => env('TRANSLATION_DRIVER', 'google'), // google|argos_cloud|argos_http|argos_local

        'google' => [
            'enabled' => env('GOOGLE_TRANSLATE_ENABLED', false),
            'api_key' => env('GOOGLE_TRANSLATE_API_KEY'),
            'default_source' => env('GOOGLE_TRANSLATE_DEFAULT_SOURCE', null),
            'default_target' => env('GOOGLE_TRANSLATE_DEFAULT_TARGET', 'th'),
            'timeout' => env('GOOGLE_TRANSLATE_TIMEOUT', 3),
        ],

        'argos_cloud' => [
            'enabled' => env('ARGOS_CLOUD_ENABLED', false),
            'base_uri' => env('ARGOS_CLOUD_BASE_URI', 'https://libretranslate.com/translate'),
            'timeout' => env('ARGOS_CLOUD_TIMEOUT', 6),
        ],
    ],

];
