<?php

namespace Gametech\Payment\Repositories;

use Gametech\Core\Eloquent\Repository;
use Gametech\Member\Repositories\MemberRepository;
use Illuminate\Container\Container as App;

class BankHengpayRepository extends Repository
{
    function model()
    {
        return \Gametech\Payment\Models\BankHengpay::class;


    }
}
