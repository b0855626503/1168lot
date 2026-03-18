<?php

namespace Gametech\Core\Models;

use DateTimeInterface;
use Gametech\Admin\Models\AdminProxy;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Database\Eloquent\Model;

use Gametech\Core\Contracts\Log as LogContract;

class Log extends Model implements LogContract
{
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'logs';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    public function admin()
    {
        return $this->belongsTo(AdminProxy::modelClass(), 'emp_code');
    }

    public function user()
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'record', 'code');
    }

}
