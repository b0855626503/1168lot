<?php

namespace Gametech\Lotto\Providers;

use Gametech\Lotto\Console\Commands\BootstrapMemberMarketPoliciesCommand;
use Gametech\Lotto\Services\BetService;
use Gametech\Lotto\Services\DrawService;
use Gametech\Lotto\Services\ExposureService;
use Gametech\Lotto\Services\MemberMarketPolicyService;
use Gametech\Lotto\Services\SettlementService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

/**
 * Lotto Feature Service Provider
 * Registers routes, views, and services
 */
class LottoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfig();

        $this->app->singleton(BetService::class, function ($app) {
            return new BetService($app->make(ExposureService::class));
        });

        $this->app->singleton(ExposureService::class, function ($app) {
            return new ExposureService();
        });

        $this->app->singleton(DrawService::class, function ($app) {
            return new DrawService();
        });

        $this->app->singleton(SettlementService::class, function ($app) {
            return new SettlementService();
        });

        $this->app->singleton(MemberMarketPolicyService::class, function ($app) {
            return new MemberMarketPolicyService();
        });

        $this->commands([
            BootstrapMemberMarketPoliciesCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../Routes/admin.php');
        $this->loadRoutesFrom(__DIR__ . '/../Routes/api.php');

        $this->loadViewsFrom(__DIR__ . '/../Resources/views/admin', 'admin');
        $this->loadViewsFrom(__DIR__ . '/../Resources/views', 'lotto');

        Event::listen('member.created.after', function ($member): void {
            if (! isset($member->code)) {
                return;
            }

            app(MemberMarketPolicyService::class)->bootstrapForMember((int) $member->code);
        });
    }

    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/admin-menu.php', 'menu.admin'
        );

        $this->mergeConfigFrom(
            dirname(__DIR__) . '/Config/acl.php', 'acl'
        );
    }
}

