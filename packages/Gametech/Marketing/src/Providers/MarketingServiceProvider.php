<?php

namespace Gametech\Marketing\Providers;

use Gametech\Marketing\Contracts\MarketingMember;
use Gametech\Marketing\Models\MarketingMemberProxy;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
//        dd('MarketingServiceProvider loaded');

        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__.'/../Routes/admin-routes.php');

        $this->loadRoutesFrom(__DIR__.'/../Routes/customer-routes.php');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'marketing');

        $this->loadViewsFrom(__DIR__.'/../Resources/views/admin', 'admin');

        $this->loadViewsFrom(__DIR__.'/../Resources/views/wallet', 'wallet');

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerConfig();

//        $this->app->bind(MarketingMember::class, MarketingMemberProxy::class);
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

//        $this->mergeConfigFrom(
//            dirname(__DIR__).'/Config/concord.php', 'concord'
//        );
    }
}
