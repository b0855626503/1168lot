<?php

namespace Gametech\Game\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Game\Contracts\FreeGame as FreeGameContract;
use Gametech\Member\Models\MemberProxy;
use Gametech\Payment\Models\BillFreeProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Payment\Models\PaymentWaitingProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class FreeGame extends Model implements FreeGameContract
{
    use LaravelSubQueryTrait;


    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public $table = 'freegames';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $primaryKey = 'code';

    protected $fillable = [
        'member_code',
        'member_user',
        'gameuser_code',
        'free_game_name',
        'expired_date',
        'bet_amount',
        'game_count',
        'product_id',
        'game_ids',
        'game_name',
        'emp_code',
        'emp_user',
        'ip',
        'status',
        'freegame_idx',
        'user_create',
        'date_create',
        'user_update',
        'date_update',

    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('freegames.code', '>', 0);
        });
    }





}
