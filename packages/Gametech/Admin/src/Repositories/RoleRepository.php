<?php

namespace Gametech\Admin\Repositories;

use Gametech\Core\Eloquent\Repository;

class RoleRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Admin\Models\Role::class;
    }
}
