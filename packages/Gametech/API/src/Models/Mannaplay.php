<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Mannaplay as MannaplayContract;

class Mannaplay extends Model implements MannaplayContract
{
    protected $connection = 'mongodb';
    protected $collection = 'mannaplay';

    protected $primaryKey = 'id';
}
