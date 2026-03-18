<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Member\Contracts\MemberTransfer as MemberTransferContract;
use Illuminate\Database\Eloquent\Model;

class MemberTransfer extends Model implements MemberTransferContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_transfer';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'user_name',
        'to_member_code',
        'to_user_name',
        'amount',
        'enable',
        'user_create',
        'user_update',
    ];
}
