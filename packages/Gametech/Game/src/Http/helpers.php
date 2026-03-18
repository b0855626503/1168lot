<?php

use Gametech\Game\Game;

if (! function_exists('game')) {
        function game()
        {
            return app()->make(Game::class);
        }
    }
?>
