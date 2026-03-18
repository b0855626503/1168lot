<?php

namespace Gametech\LogAdmin\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Gametech\LogAdmin\Http\Traits\ActivityLogger;

class LogPasswordReset
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
     * @param PasswordReset $event
     *
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        if (config('LaravelLogger.logPasswordReset')) {
            ActivityLogger::activitie('Reset Password');
        }
    }
}
