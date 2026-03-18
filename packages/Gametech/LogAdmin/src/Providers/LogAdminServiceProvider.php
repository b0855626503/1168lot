<?php

namespace Gametech\LogAdmin\Providers;


use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class LogAdminServiceProvider extends ServiceProvider
{

    const DISABLE_DEFAULT_ROUTES_CONFIG = 'LaravelLogger.disableRoutes';

    protected $defer = false;

    protected $listeners = [

        'Gametech\LogAdmin\Events\Attempting' => [
            'Gametech\LogAdmin\Listeners\LogAuthenticationAttempt',
        ],

        'Gametech\LogAdmin\Events\Authenticated' => [
            'Gametech\LogAdmin\Listeners\LogAuthenticated',
        ],

        'Gametech\LogAdmin\Events\Login' => [
            'Gametech\LogAdmin\Listeners\LogSuccessfulLogin',
        ],

        'Gametech\LogAdmin\Events\Failed' => [
            'Gametech\LogAdmin\Listeners\LogFailedLogin',
        ],

        'Gametech\LogAdmin\Events\Logout' => [
            'Gametech\LogAdmin\Listeners\LogSuccessfulLogout',
        ],

        'Gametech\LogAdmin\Events\Lockout' => [
            'Gametech\LogAdmin\Listeners\LogLockout',
        ],

        'Gametech\LogAdmin\Events\PasswordReset' => [
            'Gametech\LogAdmin\Listeners\LogPasswordReset',
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
//        $router->aliasMiddleware('logadmin', LogAdminMiddleware::class);

    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->loadRoutesFrom(__DIR__ . '/../Http/routes.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'LaravelLogger');

        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'LaravelLogger');

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
            dirname(__DIR__) . '/Config/laravel-logger.php', 'LaravelLogger'
        );
//        $this->mergeConfigFrom(__DIR__.'/../Config/laravel-logger.php', 'LaravelLogger');
    }
}
