<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class PaymentWaitingRepository extends Repository
{


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {
        return \Gametech\Payment\Models\PaymentWaiting::class;

    }
}
