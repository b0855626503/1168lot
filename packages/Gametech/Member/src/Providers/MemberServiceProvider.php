<?php

namespace Gametech\Member\Providers;

use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Observers\MemberObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class MemberServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        MemberProxy::observe(MemberObserver::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {

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
