<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Attempting;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogAuthenticationAttempt
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
     * @param Attempting $event
     *
     * @return void
     */
    public function handle(Attempting $event)
    {
        if (config('LaravelLoggerUser.logAuthAttempts')) {
            ActivityLogger::activity('Authenticated Attempt');
        }
    }
}
