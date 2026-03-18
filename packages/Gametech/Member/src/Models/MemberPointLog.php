<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Member\Contracts\MemberPointLog as MemberPointLogContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberPointLog extends Model implements MemberPointLogContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_pointlog';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'gameuser_code',
        'game_code',
        'point_type',
        'point',
        'point_amount',
        'point_before',
        'point_balance',
        'ip',
        'auto',
        'remark',
        'enable',
        'emp_code',
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
        'gameuser_code' => 'integer',
        'game_code' => 'integer',
        'point_type' => 'string',
        'point' => 'decimal:2',
        'point_amount' => 'decimal:2',
        'point_before' => 'decimal:2',
        'point_balance' => 'decimal:2',
        'ip' => 'string',
        'auto' => 'string',
        'remark' => 'string',
        'enable' => 'string',
        'emp_code' => 'integer',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:i:s',
        'date_update' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    protected static $rules = [
        'member_code' => 'nullable|integer',
        'gameuser_code' => 'required|integer',
        'game_code' => 'required|integer',
        'point_type' => 'required|string',
        'point' => 'required|numeric',
        'point_amount' => 'required|numeric',
        'point_before' => 'required|numeric',
        'point_balance' => 'required|numeric',
        'ip' => 'required|string|max:30',
        'auto' => 'required|string',
        'remark' => 'required|string|max:255',
        'enable' => 'required|string',
        'emp_code' => 'nullable|integer',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
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
        return $query->where('enable','N');
    }

    public function scopeAuto($query)
    {
        return $query->where('auto','Y');
    }

    public function scopeNotauto($query)
    {
        return $query->where('auto','N');
    }

    public function member()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_code');
    }
}
