<?php

namespace Gametech\Game\Providers;

use Gametech\Game\Game;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;
use Gametech\Game\Facades\Game  as GameFacade;

class GameServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        include __DIR__ . '/../Http/helpers.php';
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
//        $this->registerConfig();
        $this->registerBouncer();
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/game.php', 'game'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/gamefree.php', 'gamefree'
        );
    }

    /**
     * Register Bouncer as a singleton.
     *
     * @return void
     */
    protected function registerBouncer()
    {
        //to make the cart facade and bind the
        //alias to the class needed to be called.
        $loader = AliasLoader::getInstance();

        $loader->alias('game', GameFacade::class);

        $this->app->singleton('game', function () {
            return app()->make(Game::class);
        });

    }
}
