<?php

namespace Gametech\FrontendApi\Providers;

use Gametech\FrontendApi\Services\FrontendTokenService;
use Illuminate\Support\ServiceProvider;

class FrontendApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FrontendTokenService::class, function ($app) {
            return new FrontendTokenService();
        });
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');
    }
}

