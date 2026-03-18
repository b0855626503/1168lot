<?php

namespace Gametech\Core\Models;

use DateTimeInterface;
use Gametech\Core\Contracts\Faq as FaqContract;
use Illuminate\Database\Eloquent\Model;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Faq extends Model implements FaqContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'faq';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    public $fillable = [
        'question',
        'answer',
        'sort',
        'enable',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'question' => 'string',
        'answer' => 'string',
        'sort' => 'integer',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    public static $rules = [
        'question' => 'required|string',
        'answer' => 'required|string',
        'sort' => 'required|integer',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'required',
        'date_update' => 'required'
    ];


    public function scopeActive($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable','N');
    }
}
