<?php

namespace Gametech\Admin\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Gametech\Admin\Contracts\Role as RoleContract;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class Role extends Model implements RoleContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'roles';

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $primaryKey = 'code';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
        'permission_type',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    /**
     * Get the admins.
     */
    public function admins()
    {
        return $this->hasMany(AdminProxy::modelClass());
    }
}
