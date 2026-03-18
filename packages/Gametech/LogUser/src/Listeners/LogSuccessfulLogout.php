<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Logout;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogSuccessfulLogout
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
     * @param Logout $event
     *
     * @return void
     */
    public function handle(Logout $event)
    {
        if (config('LaravelLoggerUser.logSuccessfulLogout')) {
            ActivityLoggerUser::activity('Logged Out');
        }
    }
}
