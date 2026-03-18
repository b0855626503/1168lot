<?php

namespace App\Broadcasting;

use Gametech\Member\Models\Member;

class MessageAll
{
    /**
     * Create a new channel instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Authenticate the user's access to the channel.
     *
     * @param \Gametech\Member\Models\Member $user
     * @return array|bool
     */
    public function join(Member $user)
    {
        //
    }
}
