<?php

namespace Gametech\Core\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen('customer.login.fail', 'Gametech\Core\Listeners\Customer@fail');

        Event::listen('customer.login.after', 'Gametech\Core\Listeners\Customer@login');

        Event::listen('customer.logout.after', 'Gametech\Core\Listeners\Customer@logout');

        Event::listen('customer.register.after', 'Gametech\Core\Listeners\Customer@register');

        Event::listen('customer.transfer.wallet.before', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.wallet.after', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.wallet.rollback', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.game.before', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.game.after', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.game.rollback', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.credit.before', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.credit.after', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.transfer.credit.rollback', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.credit.transfer.game.before', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.credit.transfer.game.after', 'Gametech\Core\Listeners\Customer@memberEvent');

        Event::listen('customer.credit.transfer.game.rollback', 'Gametech\Core\Listeners\Customer@memberEvent');



        Event::listen('admin.login.after', 'Gametech\Core\Listeners\Admin@login');

        Event::listen('admin.logout.after', 'Gametech\Core\Listeners\Admin@logout');

//        Event::listen('customer.transfer.wallet.before', 'Webkul\Product\Listeners\ProductFlat@afterAttributeCreatedUpdated');

//        Event::listen('customer.transfer.wallet.after', 'Gametech\Core\Listeners\Customer@updateLogin');
//
//        Event::listen('customer.transfer.game.after', 'Gametech\Core\Listeners\Customer@updateLogin');
//
//        Event::listen('customer.withdraw.after', 'Gametech\Core\Listeners\Customer@updateLogin');
//
//        Event::listen('customer.withdrawfree.after', 'Gametech\Core\Listeners\Customer@updateLogin');
//
//        Event::listen('catalog.product.create.after', 'Webkul\Product\Listeners\ProductFlat@afterProductCreatedUpdated');
//
//        Event::listen('catalog.product.update.after', 'Webkul\Product\Listeners\ProductFlat@afterProductCreatedUpdated');

    }
}
