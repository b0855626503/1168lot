<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Core\Models\RewardProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Gametech\Member\Contracts\MemberRewardLog as MemberRewardLogContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberRewardLog extends Model implements MemberRewardLogContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_reward_logs';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'reward_code',
        'point',
        'point_amount',
        'point_before',
        'point_balance',
        'ip',
        'remark',
        'approve',
        'date_approve',
        'ip_admin',
        'enable',
        'emp_code',
        'user_create',
        'user_update'
    ];

    protected $casts = [
        'code' => 'integer',
        'member_code' => 'integer',
        'reward_code' => 'integer',
        'point' => 'decimal:2',
        'point_amount' => 'decimal:2',
        'point_before' => 'decimal:2',
        'point_balance' => 'decimal:2',
        'ip' => 'string',
        'remark' => 'string',
        'approve' => 'boolean',
        'date_approve' => 'datetime',
        'ip_admin' => 'string',
        'enable' => 'string',
        'emp_code' => 'integer',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('code', '<>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable', 'N');
    }

    public function scopeConfirm($query)
    {
        return $query->where('approve', 1);
    }

    public function scopeWaiting($query)
    {
        return $query->where('approve', 0);
    }

    public function emp()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_code');
    }

    public function reward()
    {
        return $this->belongsTo(RewardProxy::modelClass(), 'reward_code');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

}
