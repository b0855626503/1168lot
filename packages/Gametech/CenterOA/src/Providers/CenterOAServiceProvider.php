<?php

namespace Gametech\CenterOA\Providers;

use Illuminate\Support\ServiceProvider;

class CenterOAServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerConfig();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');

    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/services.php', 'services'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/website.php', 'website'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/logging.php', 'logging'
        );
    }
}
