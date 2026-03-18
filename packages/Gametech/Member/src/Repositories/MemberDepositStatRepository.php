<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;

class MemberDepositStatRepository extends Repository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return \Gametech\Member\Models\MemberDepositStat::class;

    }
}
