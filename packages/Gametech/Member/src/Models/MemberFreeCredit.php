<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Webkul\Product\Models\ProductProxy;
use Gametech\Member\Contracts\MemberFreeCredit as MemberFreeCreditContract;

class MemberFreeCredit extends Model implements MemberFreeCreditContract
{
    use LaravelSubQueryTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_freecredit';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';


    protected $primaryKey = 'code';

    protected $fillable = [
        'kind',
        'member_code',
        'gameuser_code',
        'game_code',
        'credit_type',
        'credit',
        'credit_amount',
        'credit_before',
        'credit_balance',
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
        'kind' => 'string',
        'member_code' => 'integer',
        'gameuser_code' => 'integer',
        'game_code' => 'integer',
        'credit_type' => 'string',
        'credit' => 'decimal:2',
        'credit_amount' => 'decimal:2',
        'credit_before' => 'decimal:2',
        'credit_balance' => 'decimal:2',
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
    public static $rules = [
        'kind' => 'required|string|max:20',
        'member_code' => 'required|integer',
        'gameuser_code' => 'required|integer',
        'game_code' => 'required|integer',
        'credit_type' => 'required|string',
        'credit' => 'required|numeric',
        'credit_amount' => 'required|numeric',
        'credit_before' => 'required|numeric',
        'credit_balance' => 'required|numeric',
        'ip' => 'required|string|max:45',
        'auto' => 'required|string',
        'remark' => 'required|string|max:255',
        'enable' => 'required|string',
        'emp_code' => 'required|integer',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];


    public function scopeActive($query)
    {
        return $query->where('members_freecredit.enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('members_freecredit.enable','N');
    }

    public function scopeAuto($query)
    {
        return $query->where('members_freecredit.auto','Y');
    }

    public function scopeNotauto($query)
    {
        return $query->where('members_freecredit.auto','N');
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
