<?php

namespace Gametech\Game\Repositories;

use Gametech\Core\Eloquent\Repository;


/**
 * Class GameUserRepository
 * @package Gametech\Game\Repositories
 */
class GameUserEventRepository extends Repository
{


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Game\Models\GameUserEvent::class;

    }


}
