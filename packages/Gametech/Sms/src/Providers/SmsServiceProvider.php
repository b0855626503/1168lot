<?php

namespace Gametech\Sms\Providers;

use Gametech\Sms\Contracts\SmsProviderInterface;
use Gametech\Sms\Providers\Vonage\VonageSmsProvider;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(SmsProviderInterface::class, VonageSmsProvider::class);

        $this->registerConfig();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/webhook.php');

        $this->loadViewsFrom(__DIR__.'/../Resources/views/admin', 'admin');

    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/acl.php', 'acl'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__).'/Config/sms.php', 'sms'
        );
    }
}
