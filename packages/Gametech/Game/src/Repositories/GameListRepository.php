<?php

namespace Gametech\Game\Repositories;

use Gametech\Core\Eloquent\Repository;

class GameListRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return 'Gametech\API\Models\GameList';
    }


}
