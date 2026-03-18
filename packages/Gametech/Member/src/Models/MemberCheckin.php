<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Member\Contracts\MemberCheckin as MemberCheckinContract;

class MemberCheckin extends Model implements MemberCheckinContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_checkin';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'check_code',
        'date_check',
        'member_code',
        'ip',
        'enable',
        'user_create',
        'user_update',
    ];
}
