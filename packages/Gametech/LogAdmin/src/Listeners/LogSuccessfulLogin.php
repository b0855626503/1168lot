<?php

namespace Gametech\LogAdmin\Listeners;

use Illuminate\Auth\Events\Login;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;

class LogSuccessfulLogin
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
     * @param Login $event
     *
     * @return void
     */
    public function handle(Login $event)
    {
        if (config('LaravelLogger.logSuccessfulLogin')) {
            ActivityLogger::activitie('Logged In');
        }
    }
}
