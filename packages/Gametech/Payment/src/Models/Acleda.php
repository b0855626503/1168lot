<?php

namespace Gametech\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Gametech\Payment\Contracts\Acleda as AcledaContract;

class Acleda extends Model implements AcledaContract
{
    protected $fillable = ['user_code','user_name','refid','method'];
}