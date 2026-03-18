<?php

namespace Gametech\LogUser\Providers;

use Gametech\LogUser\Http\Middleware\LogActivityUser as LogUserMiddleware;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class LogUserServiceProvider extends ServiceProvider
{

    const DISABLE_DEFAULT_ROUTES_CONFIG = 'LaravelLoggerUser.disableRoutes';

    protected $defer = false;

    protected $listeners = [

        'Gametech\LogUser\Events\Attempting' => [
            'Gametech\LogUser\Listeners\LogAuthenticationAttempt',
        ],

        'Gametech\LogUser\Events\Authenticated' => [
            'Gametech\LogUser\Listeners\LogAuthenticated',
        ],

        'Gametech\LogUser\Events\Login' => [
            'Gametech\LogUser\Listeners\LogSuccessfulLogin',
        ],

        'Gametech\LogUser\Events\Failed' => [
            'Gametech\LogUser\Listeners\LogFailedLogin',
        ],

        'Gametech\LogUser\Events\Logout' => [
            'Gametech\LogUser\Listeners\LogSuccessfulLogout',
        ],

        'Gametech\LogUser\Events\Lockout' => [
            'Gametech\LogUser\Listeners\LogLockout',
        ],

        'Gametech\LogUser\Events\PasswordReset' => [
            'Gametech\LogUser\Listeners\LogPasswordReset',
        ],

    ];

    /**
     * Bootstrap services.
     *
     * @param Router $router
     * @return void
     */
    public function boot(Router $router)
    {

//        $router->aliasMiddleware('loguser', LogUserMiddleware::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'LaravelLoggerUser');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'LaravelLoggerUser');

        $this->registerConfig();

        $this->registerEventListeners();
    }

    private function getListeners()
    {
        return $this->listeners;
    }

    private function registerEventListeners()
    {
        $listeners = $this->getListeners();
        foreach ($listeners as $listenerKey => $listenerValues) {
            foreach ($listenerValues as $listenerValue) {
                Event::listen(
                    $listenerKey,
                    $listenerValue
                );
            }
        }
    }

    /**
     * Register package config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/laravel-logger.php', 'LaravelLoggerUser'
        );

    }
}
