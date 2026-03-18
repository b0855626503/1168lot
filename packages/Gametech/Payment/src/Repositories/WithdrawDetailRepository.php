<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;


class WithdrawDetailRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Payment\Models\WithdrawDetail::class;

    }
}
