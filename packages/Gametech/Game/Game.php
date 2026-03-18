<?php

namespace Gametech\Game;

use Gametech\Game\Repositories\GameRepository;



class Game
{
    /**
     * CoreConfigRepository class
     *
     * @var GameRepository
     */
    protected $gameRepository;



    /**
     * Create a new instance.
     *

     * @param GameRepository $gameRepository
     *
     * @return void
     */
    public function __construct
    (
        GameRepository $gameRepository
    )
    {
        $this->gameRepository = $gameRepository;
    }



}
