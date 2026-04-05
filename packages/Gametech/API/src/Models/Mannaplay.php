<?php

namespace Gametech\API\Models;

use MongoDB\Laravel\Eloquent\Model;
use Gametech\API\Contracts\Mannaplay as MannaplayContract;

class Mannaplay extends Model implements MannaplayContract
{
    protected $connection = 'mongodb';
    protected $table = 'mannaplay';

    protected $primaryKey = 'id';
}
