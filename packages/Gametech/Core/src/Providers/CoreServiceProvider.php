<?php

namespace Gametech\Core\Providers;

use Gametech\Core\Core;
use Gametech\Core\Exceptions\Handler;
use Gametech\Core\Models\ConfigProxy;
use Gametech\Core\Observers\ConfigObserver;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Gametech\Core\Facades\Core as CoreFacade;

class CoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {


        ConfigProxy::observe(ConfigObserver::class);

//        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'core');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            ExceptionHandler::class,
            Handler::class
        );

        $this->app->register(EventServiceProvider::class);

        $this->registerFacades();
//        $this->registerConfig();
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerFacades()
    {
//        $loader = AliasLoader::getInstance();
//        $loader->alias('core', CoreFacade::class);
//
//        $this->app->singleton('core', function () {
//            return app()->make(Core::class);
//        });

//        AliasLoader::getInstance()->alias('core', CoreFacade::class);

        $this->app->singleton('core', function ($app) {
            return $app->make(Core::class);
            // หรือประกอบเองถ้าต้องยัดสกาลาร์จาก config:
            // return new Core(config('core.a'), ..., $app->make(...));
        });

// 2) ทำ container alias เพื่อให้ type-hint Core::class ได้อินสแตนซ์เดียวกัน
        $this->app->alias(Core::class, 'core');
        AliasLoader::getInstance()->alias('core', CoreFacade::class);
// 3) ทำ facade alias แบบชื่อคลาสที่อ่านง่าย
//        AliasLoader::getInstance()->alias('core', CoreFacade::class);


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

        $this->mergeConfigFrom(__DIR__.'/../config/gametech.php', 'gametech');
    }
}
