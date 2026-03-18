<?php

namespace Gametech\Core\Models;

use Gametech\Core\Contracts\CashBack as CashBackContract;
use Illuminate\Database\Eloquent\Model;

class CashBack extends Model implements CashBackContract
{
    protected $fillable = [];
}
