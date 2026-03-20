<?php

return [
    'modules' => [
        /**
         * Example:
         * VendorA\ModuleX\Providers\ModuleServiceProvider::class,
         * VendorB\ModuleY\Providers\ModuleServiceProvider::class
         *
         */
        Gametech\Admin\Providers\ModuleServiceProvider::class,
        Gametech\Core\Providers\ModuleServiceProvider::class,
        Gametech\Game\Providers\ModuleServiceProvider::class,
        Gametech\Member\Providers\ModuleServiceProvider::class,
        Gametech\Payment\Providers\ModuleServiceProvider::class,
        Gametech\Promotion\Providers\ModuleServiceProvider::class,
        Gametech\LogAdmin\Providers\ModuleServiceProvider::class,
        Gametech\LogUser\Providers\ModuleServiceProvider::class,
        Gametech\API\Providers\ModuleServiceProvider::class,
//        Gametech\TelegramBot\Providers\ModuleServiceProvider::class,
        Gametech\Marketing\Providers\ModuleServiceProvider::class,
        Gametech\LineOA\Providers\ModuleServiceProvider::class,
//        Gametech\FacebookOA\Providers\ModuleServiceProvider::class,
        Gametech\Reward\Providers\ModuleServiceProvider::class,
        Gametech\Sms\Providers\ModuleServiceProvider::class,
        Gametech\Lotto\Providers\ModuleServiceProvider::class,

    ],
    'register_route_models' => true
];
