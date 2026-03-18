<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\Lockout;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogLockout
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
     * @param Lockout $event
     *
     * @return void
     */
    public function handle(Lockout $event)
    {
        if (config('LaravelLoggerUser.logLockOut')) {
            ActivityLoggerUser::activity('Locked Out');
        }
    }
}
