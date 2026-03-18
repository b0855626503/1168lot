<?php

namespace Gametech\Game\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\Storage;


class GameSingleRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Game\Models\GameSingle::class;

    }

}
