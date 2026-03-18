<?php

namespace Gametech\Core\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CheckinRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model()
    {

        return \Gametech\Core\Models\Checkin::class;
    }


}
