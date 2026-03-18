<?php

namespace Gametech\LogUser\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Gametech\LogUser\Http\Traits\ActivityLoggerUser;

class LogPasswordReset
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
     * @param PasswordReset $event
     *
     * @return void
     */
    public function handle(PasswordReset $event)
    {
        if (config('LaravelLoggerUser.logPasswordReset')) {
            ActivityLoggerUser::activity('Reset Password');
        }
    }
}
