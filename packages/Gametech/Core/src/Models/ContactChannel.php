<?php

namespace Gametech\Core\Models;

use Gametech\Core\Contracts\ContactChannel as ContactChannelContract;
use Illuminate\Database\Eloquent\Model;

class ContactChannel extends Model implements ContactChannelContract
{
    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    public $incrementing = true;

    public $timestamps = false;

    protected $table = 'contact_channels';

    protected $primaryKey = 'code';

    protected $keyType = 'int'; // ปิด timestamps ปกติ

    protected $fillable = [
        'code',
        'type',
        'label',
        'link',
        'sort',
        'enable',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
    ];
}
