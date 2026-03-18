<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;

class MemberRemarkRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    function model(): string
    {
        return \Gametech\Member\Models\MemberRemark::class;

    }

    public function loadRemark($id)
    {
        return $this->orderBy('code', 'desc')->with('emp')->findByField('member_code',$id);
    }
}
