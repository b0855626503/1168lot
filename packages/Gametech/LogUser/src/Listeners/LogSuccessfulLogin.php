<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Login;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser ;

class LogSuccessfulLogin
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
     * Handle the event.
     *
     * @param Login $event
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if (config('LaravelLoggerUser.logSuccessfulLogin')) {
            ActivityLoggerUser::activity('Logged In');
        }
    }
}
