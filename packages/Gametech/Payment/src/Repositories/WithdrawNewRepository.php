<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;


class WithdrawNewRepository extends Repository
{

    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return \Gametech\Payment\Models\WithdrawNew::class;

    }
}
