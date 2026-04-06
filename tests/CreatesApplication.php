<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return Application
     */
    public function createApplication()
    {
        $testingConfigCache = __DIR__.'/../bootstrap/cache/testing-config.php';
        putenv('APP_ENV=testing');
        putenv('APP_CONFIG_CACHE='.$testingConfigCache);
        $_ENV['APP_ENV'] = 'testing';
        $_SERVER['APP_ENV'] = 'testing';
        $_ENV['APP_CONFIG_CACHE'] = $testingConfigCache;
        $_SERVER['APP_CONFIG_CACHE'] = $testingConfigCache;

        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
