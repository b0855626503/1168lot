<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

use Gametech\Member\Contracts\MemberIc as MemberIcContract;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spiritix\LadaCache\Database\LadaCacheTrait;

class MemberIc extends Model implements MemberIcContract
{
    use  LadaCacheTrait;

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $table = 'members_ic';

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




    public function scopeActive($query)
    {
        return $query->where('enable','Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('enable','N');
    }

    public function down(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'downline_code');
    }

    public function me(): BelongsTo
    {
        return $this->belongsTo(MemberProxy::modelClass(), 'member_code');
    }


}
