<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;

class ReferRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Core\Models\Refer::class;

    }
}
