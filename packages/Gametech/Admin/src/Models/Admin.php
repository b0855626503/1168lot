<?php

namespace Gametech\Admin\Models;


use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Admin\Contracts\Admin as AdminContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//use HighIdeas\UsersOnline\Traits\UsersOnlineTrait;

class Admin extends Authenticatable implements AdminContract
{
    use Notifiable;

    use LaravelSubQueryTrait;

//    use UsersOnlineTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'employees';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'department_code',
        'position_code',
        'task_code',
        'id',
        'authen_id',
        'prefix_code',
        'name',
        'surname',
        'nickname',
        'birthday',
        'birthmonth',
        'birthyear',
        'tel',
        'mobile',
        'address',
        'province_code',
        'zipcode',
        'email',
        'user_prefix1',
        'user_prefix2',
        'user_name',
        'user_pass',
        'user_passdel',
        'level',
        'filepic',
        'min_money',
        'max_money',
        'credit',
        'credit_balance',
        'percent',
        'fight',
        'superadmin',
        'enable',
        'user_create',
        'user_update',
        'login_session',
        'password',
        'role_id',
        'lastlogin',
        'google2fa_secret',
        'google2fa_enable'
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'code' => 'integer',
        'department_code' => 'integer',
        'position_code' => 'integer',
        'task_code' => 'integer',
        'id' => 'string',
        'authen_id' => 'string',
        'prefix_code' => 'integer',
        'name' => 'string',
        'surname' => 'string',
        'nickname' => 'string',
        'birthday' => 'string',
        'birthmonth' => 'string',
        'birthyear' => 'string',
        'tel' => 'string',
        'mobile' => 'string',
        'address' => 'string',
        'province_code' => 'integer',
        'zipcode' => 'string',
        'email' => 'string',
        'user_prefix1' => 'string',
        'user_prefix2' => 'string',
        'user_name' => 'string',
        'user_pass' => 'string',
        'user_passdel' => 'string',
        'level' => 'integer',
        'filepic' => 'string',
        'min_money' => 'decimal:2',
        'max_money' => 'decimal:2',
        'credit' => 'decimal:2',
        'credit_balance' => 'decimal:2',
        'percent' => 'integer',
        'fight' => 'integer',
        'superadmin' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'login_session' => 'string',
        'password' => 'string',
        'role_id' => 'integer',
        'google2fa_enable' => 'integer',
        'lastlogin' => 'datetime:Y-m-d H:i:s'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password','google2fa_secret'
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

    /**
     * Get the role that owns the admin.
     */
    public function role()
    {
        return $this->belongsTo(RoleProxy::modelClass(), 'role_id');
    }



    /**
     * Checks if admin has permission to perform certain action.
     *
     * @param  String  $permission
     * @return Boolean
     */
    public function hasPermission($permission)
    {
//        if($permission == 'auth.broadcast')
        if ($this->role->permission_type == 'custom' && ! $this->role->permissions) {
            return false;
        }

        return in_array($permission, $this->role->permissions);
    }

    public function receivesBroadcastNotificationsOn() { return env('APP_NAME').'_events'; }
}
