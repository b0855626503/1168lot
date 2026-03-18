<?php

namespace Gametech\Core\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Core\Contracts\Checkin as CheckinContract;

class Checkin extends Model implements CheckinContract
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'checkins';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';
}
