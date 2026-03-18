<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\PGSoft as PGSoftContract;

class PGSoft extends Model implements PGSoftContract
{
    protected $connection = 'mongodb';
    protected $collection = 'pgsoft';

    protected $primaryKey = 'id';

}
