<?php

namespace Gametech\Core\Models;

use DateTimeInterface;
use Gametech\Core\Contracts\Notice as NoticeContract;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Notice extends Model implements NoticeContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'notices';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    public $fillable = [
        'route',
        'message',
        'enable',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'route' => 'string',
        'message' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    public static $rules = [
        'route' => 'required|string',
        'message' => 'required|string',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'required',
        'date_update' => 'required'
    ];
}
