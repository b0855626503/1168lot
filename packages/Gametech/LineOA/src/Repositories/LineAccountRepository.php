<?php

namespace Gametech\LineOA\Repositories;

use Gametech\Core\Eloquent\Repository;

class LineAccountRepository extends Repository
{
    /**
     * Specify Model class name
     *
     * @return mixed
     */
    public function model()
    {
        return \Gametech\LineOA\Models\LineAccount::class;

    }
}
