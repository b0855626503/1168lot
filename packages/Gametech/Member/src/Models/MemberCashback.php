<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Gametech\Member\Contracts\MemberCashback as MemberCashbackContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spiritix\LadaCache\Database\LadaCacheTrait;


class MemberCashback extends Model implements MemberCashbackContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_cashback';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'downline_code',
        'balance',
        'ic',
        'cashback',
        'date_cashback',
        'amount',
        'topupic',
        'emp_code',
        'ip_admin',
        'date_approve',
        'turnpro',
        'winlose',
        'enable',
        'startdate',
        'enddate',
        'game_user',
        'user_create',
        'user_update'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'member_code' => 'integer',
        'downline_code' => 'integer',
        'balance' => 'decimal:2',
        'ic' => 'decimal:2',
        'cashback' => 'decimal:2',
        'date_cashback' => 'date',
        'amount' => 'decimal:2',
        'topupic' => 'string',
        'emp_code' => 'integer',
        'ip_admin' => 'string',
        'date_approve' => 'datetime',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'downline_code');
    }

    public function downline(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'downline_code');
    }

    public function me(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }
}
