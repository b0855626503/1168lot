<?php

namespace Gametech\API\Models;

use Jenssegers\Mongodb\Eloquent\Model;
use Gametech\API\Contracts\Slotxo as SlotxoContract;

class Slotxo extends Model implements SlotxoContract
{
    protected $connection = 'mongodb';
    protected $collection = 'slotxo';

    protected $primaryKey = 'id';
}
