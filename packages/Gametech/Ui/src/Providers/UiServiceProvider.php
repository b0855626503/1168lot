<?php

namespace Gametech\Ui\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class UiServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {

        $this->publishes([
            __DIR__ . '/../../publishable/assets' => public_path('assets/ui'),
        ], 'public');


    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->registerConfig();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );
    }
}
