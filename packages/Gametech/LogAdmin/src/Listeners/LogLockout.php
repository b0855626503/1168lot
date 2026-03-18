<?php

namespace Gametech\LogAdmin\Listeners;

use Illuminate\Auth\Events\Lockout;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;

class LogLockout
{
    use ActivityLogger;

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
        if (config('LaravelLogger.logLockOut')) {
            ActivityLogger::activitie('Locked Out');
        }
    }
}
