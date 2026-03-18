<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use Gametech\Core\Contracts\BillLast as BillLastContract;
use Illuminate\Database\Eloquent\Model;

class BillLast extends Model implements BillLastContract
{
    use LaravelSubQueryTrait;

    protected $table = 'view_maxbill';

    protected $primaryKey = 'code';
}
