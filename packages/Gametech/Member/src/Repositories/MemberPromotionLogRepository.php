<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;

class MemberPromotionLogRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberPromotionLog::class;

    }
}
