<?php

namespace Gametech\Promotion\Providers;

use Gametech\Promotion\Models\PromotionProxy;
use Gametech\Promotion\Observers\PromotionObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class PromotionServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        PromotionProxy::observe(PromotionObserver::class);
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
