<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\PGSoft as PGSoftContract;

class PGSoft extends Model implements PGSoftContract
{
    protected $connection = 'mongodb';
    protected $table = 'pgsoft';

    protected $primaryKey = 'id';

}
