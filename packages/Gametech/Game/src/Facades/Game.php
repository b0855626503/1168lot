<?php

namespace Gametech\Game\Facades;

use Illuminate\Support\Facades\Facade;

class Game extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'game';
    }
}
