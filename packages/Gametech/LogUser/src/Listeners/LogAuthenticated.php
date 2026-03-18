<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Authenticated;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogAuthenticated
{
    use ActivityLoggerUser;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle ANY authenticated event.
     *
     * @param Authenticated $event
     *
     * @return void
     */
    public function handle(Authenticated $event)
    {
        if (config('LaravelLoggerUser.logAllAuthEvents')) {
            ActivityLoggerUser::activity('Authenticated Activity');
        }
    }
}
