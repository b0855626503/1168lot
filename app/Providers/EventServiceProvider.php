<?php

namespace App\Providers;

use Gametech\Auto\Events\BatchUser;
use Gametech\Auto\Events\PaymentOut;
use Gametech\Auto\Events\PaymentOutFree;
use Gametech\Auto\Listeners\BatchUserListen;
use Gametech\Auto\Listeners\PaymentOutListen;
use Gametech\Auto\Listeners\PaymentOutListenFree;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        BatchUser::class => [
            BatchUserListen::class,
        ],
        PaymentOut::class => [
            PaymentOutListen::class,
        ],
        PaymentOutFree::class => [
            PaymentOutListenFree::class,
        ],
        \Illuminate\Foundation\Http\Events\RequestHandled::class => [
            \App\Listeners\LogRequestDuration::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
