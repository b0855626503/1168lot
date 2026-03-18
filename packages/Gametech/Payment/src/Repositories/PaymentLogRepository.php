<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;

class PaymentLogRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Payment\Models\PaymentLog::class;

    }
}
