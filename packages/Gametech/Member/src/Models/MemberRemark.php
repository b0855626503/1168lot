<?php

namespace Gametech\Member\Models;

use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Illuminate\Database\Eloquent\Model;
use Gametech\Member\Contracts\MemberRemark as MemberRemarkContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberRemark extends Model implements MemberRemarkContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_remark';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'emp_code',
        'remark',
        'ip',
        'enable',
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
        'emp_code' => 'integer',
        'remark' => 'string',
        'ip' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string'
    ];

    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'member_code' => 'required|integer',
        'emp_code' => 'required|integer',
        'remark' => 'required|string|max:191',
        'ip' => 'required|string|max:100',
        'enable' => 'required|string',
        'user_create' => 'required|string|max:100',
        'user_update' => 'required|string|max:100',
        'date_create' => 'nullable',
        'date_update' => 'nullable'
    ];

    public function emp()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_code');
    }
}
