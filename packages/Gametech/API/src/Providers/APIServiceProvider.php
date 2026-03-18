<?php

namespace Gametech\API\Providers;


use Gametech\API\Ping;
use Illuminate\Support\ServiceProvider;



class APIServiceProvider extends ServiceProvider
{

    protected $defer = true;
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
//        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->loadRoutesFrom(__DIR__.'/../Http/routes.php');
//        $this->loadRoutesFrom(__DIR__.'/../Http/routesfree.php');

        $this->app->singleton('ping', function ($app) {
            return new Ping();
        });

    }

    public function provides()
    {
        return ['ping'];
    }
}
