<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Member\Contracts\MemberDiamondLog as MemberDiamondLogContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberDiamondLog extends Model implements MemberDiamondLogContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_diamondlog';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'gameuser_code',
        'game_code',
        'diamond_type',
        'diamond',
        'diamond_amount',
        'diamond_before',
        'diamond_balance',
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
        'diamond_type' => 'string',
        'diamond' => 'decimal:2',
        'diamond_amount' => 'decimal:2',
        'diamond_before' => 'decimal:2',
        'diamond_balance' => 'decimal:2',
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
        'diamond_type' => 'required|string',
        'diamond' => 'required|numeric',
        'diamond_amount' => 'required|numeric',
        'diamond_before' => 'required|numeric',
        'diamond_balance' => 'required|numeric',
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
