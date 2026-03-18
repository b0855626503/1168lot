<?php

namespace Gametech\Marketing\Repositories;

use Gametech\Core\Eloquent\Repository;


class MarketingMemberRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return \Gametech\Marketing\Models\MarketingMember::class;

    }
}
