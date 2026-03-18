<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Failed;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogFailedLogin
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
     * @param Failed $event
     *
     * @return void
     */
    public function handle(Failed $event)
    {
        if (config('LaravelLoggerUser.logFailedAuthAttempts')) {
            ActivityLoggerUser::activity('Failed Login Attempt');
        }
    }
}
