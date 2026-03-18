<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class ClientModeEvent extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
        'mode',
        'display_mode',
        'reason',
        'url',
        'ua',
        'pwa_installed_hint',
    ];
}