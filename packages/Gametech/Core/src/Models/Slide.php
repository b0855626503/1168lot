<?php

namespace Gametech\Core\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Core\Contracts\Slide as SlideContract;
use Illuminate\Database\Eloquent\Model;

class Slide extends Model implements SlideContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'slides';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'filepic',
        'sort',
        'enable',
        'user_create',
        'user_update'
    ];
}
