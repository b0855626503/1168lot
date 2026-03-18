<?php

namespace Gametech\Member\Models;

use Alexmg86\LaravelSubQuery\Traits\LaravelSubQueryTrait;
use DateTimeInterface;
use Gametech\Core\Models\ReferProxy;
use Gametech\Game\Models\GameProxy;
use Gametech\Game\Models\GameUserFreeProxy;
use Gametech\Game\Models\GameUserProxy;
use Gametech\Member\Contracts\Member as MemberContract;
use Gametech\Payment\Models\BankPaymentProxy;
use Gametech\Payment\Models\BankProxy;
use Gametech\Payment\Models\BillFreeProxy;
use Gametech\Payment\Models\BillProxy;
use Gametech\Payment\Models\BonusSpinProxy;
use Gametech\Payment\Models\PaymentPromotionProxy;
use Gametech\Payment\Models\PaymentWaitingProxy;
use Gametech\Payment\Models\WithdrawFreeProxy;
use Gametech\Payment\Models\WithdrawProxy;
use Gametech\Payment\Models\WithdrawSeamlessFreeProxy;
use Gametech\Payment\Models\WithdrawSeamlessProxy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Member extends Authenticatable implements MemberContract
{
    use LaravelSubQueryTrait, Notifiable;

    public $with = ['bank'];

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    //    public function receivesBroadcastNotificationsOn() {
    //        return 'member.'.$this->code;
    //    }

    protected $table = 'members';

    const CREATED_AT = 'date_create';

    const UPDATED_AT = 'date_update';

    //    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $dates = ['date_create', 'date_update'];

    protected $primaryKey = 'code';

    protected $fillable = [
        'refer_code',
        'bank_code',
        'upline_code',
        'name',
        'firstname',
        'lastname',
        'name_addon',
        'firstname_addon',
        'lastname_addon',
        'user_name',
        'user_pass',
        'user_pin',
        'check_status',
        'acc_no',
        'acc_check',
        'acc_bay',
        'acc_kbank',
        'tel',
        'wallet_id',
        'birth_day',
        'age',
        'lineid',
        'confirm',
        'refer',
        'point_deposit',
        'count_deposit',
        'diamond',
        'upline',
        'credit',
        'balance',
        'balance_free',
        'date_regis',
        'pro',
        'status_pro',
        'acc_status',
        'otp',
        'pic_id',
        'scode',
        'ip',
        'lastlogin',
        'remark',
        'sms_status',
        'promotion',
        'pro_status',
        'hottime2',
        'hottime3',
        'hottime4',
        'prefix',
        'gender',
        'deposit',
        'allget_downline',
        'aff_get',
        'oldmember',
        'freecredit',
        'user_delay',
        'session_ip',
        'session_id',
        'session_page',
        'session_limit',
        'payment_task',
        'payment_token',
        'payment_level',
        'payment_game',
        'payment_pro',
        'payment_balance',
        'payment_amount',
        'payment_limit',
        'payment_delay',
        'payment_mac',
        'payment_browser',
        'payment_device',
        'enable',
        'user_create',
        'user_update',
        'date_create',
        'date_update',
        'password',
        'remember_token',
        'bonus',
        'cashback',
        'faststart',
        'ic',
        'nocashback',
        'game_user',
        'maxwithdraw_day',
    ];

    protected $casts = [
        'refer_code' => 'integer',
        'bank_code' => 'integer',
        'upline_code' => 'integer',
        'name' => 'string',
        'firstname' => 'string',
        'lastname' => 'string',
        'user_name' => 'string',
        'user_pass' => 'string',
        'user_pin' => 'string',
        'check_status' => 'string',
        'acc_no' => 'string',
        'acc_check' => 'string',
        'acc_bay' => 'string',
        'acc_kbank' => 'string',
        'tel' => 'string',
        'birth_day' => 'date:Y-m-d',
        'age' => 'string',
        'lineid' => 'string',
        'confirm' => 'string',
        'refer' => 'string',
        'point_deposit' => 'decimal:2',
        'count_deposit' => 'integer',
        'diamond' => 'decimal:2',
        'upline' => 'string',
        'credit' => 'decimal:2',
        'balance' => 'decimal:2',
        'balance_free' => 'decimal:2',
        'date_regis' => 'date:Y-m-d',
        'pro' => 'integer',
        'status_pro' => 'integer',
        'acc_status' => 'string',
        'otp' => 'string',
        'pic_id' => 'string',
        'scode' => 'string',
        'ip' => 'string',
        'lastlogin' => 'datetime:Y-m-d H:i',
        'remark' => 'string',
        'sms_status' => 'string',
        'promotion' => 'string',
        'pro_status' => 'string',
        'hottime2' => 'string',
        'hottime3' => 'string',
        'hottime4' => 'string',
        'prefix' => 'string',
        'gender' => 'string',
        'deposit' => 'integer',
        'allget_downline' => 'decimal:2',
        'aff_get' => 'string',
        'oldmember' => 'string',
        'freecredit' => 'string',
        'user_delay' => 'integer',
        'session_ip' => 'string',
        'session_id' => 'string',
        'session_page' => 'string',
        'session_limit' => 'datetime:Y-m-d H:00',
        'payment_task' => 'string',
        'payment_token' => 'string',
        'payment_level' => 'integer',
        'payment_game' => 'integer',
        'payment_pro' => 'integer',
        'payment_balance' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'payment_limit' => 'datetime:Y-m-d H:00',
        'payment_delay' => 'datetime:Y-m-d H:00',
        'payment_mac' => 'string',
        'payment_browser' => 'string',
        'payment_device' => 'string',
        'enable' => 'string',
        'user_create' => 'string',
        'user_update' => 'string',
        'date_create' => 'datetime:Y-m-d H:00',
        'date_update' => 'datetime:Y-m-d H:00',
        'password' => 'string',
        'amount' => 'decimal:2',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'user_pass',
    ];

    protected static function booted()
    {
        static::addGlobalScope('code', function (Builder $builder) {
            $builder->where('members.code', '>', 0);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('members.enable', 'Y');
    }

    public function scopeInactive($query)
    {
        return $query->where('members.enable', 'N');
    }

    public function scopeConfirm($query)
    {
        return $query->where('members.confirm', 'Y');
    }

    public function scopeWaiting($query)
    {
        return $query->where('members.confirm', 'N');
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(BankProxy::modelClass(), 'bank_code');
    }

    public function refers(): BelongsTo
    {
        return $this->belongsTo(ReferProxy::modelClass(), 'refer_code');
    }

    public function up(): BelongsTo
    {
        return $this->belongsTo(self::class, 'upline_code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(GameUserProxy::modelClass(), 'code', 'member_code');
    }

    public function downs(): HasMany
    {
        return $this->hasMany(MemberProxy::modelClass(), 'upline_code');
    }

    public function down()
    {
        return $this->hasMany(MemberProxy::modelClass(), 'upline_code');
    }

    public function memberIc(): HasMany
    {
        return $this->hasMany(MemberIcProxy::modelClass(), 'member_code');
    }

    public function memberCash(): HasMany
    {
        return $this->hasMany(MemberCashbackProxy::modelClass(), 'downline_code');
    }

    public function parentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function games(): HasMany
    {
        return $this->hasMany(GameProxy::modelClass());
    }

    public function gamesUser(): HasMany
    {
        return $this->hasMany(GameUserProxy::modelClass(), 'member_code');
    }

    public function gamesUserFree(): HasMany
    {
        return $this->hasMany(GameUserFreeProxy::modelClass(), 'member_code');
    }

    public function gameUser(): HasOne
    {
        return $this->hasOne(GameUserProxy::modelClass(), 'member_code');
    }

    public function gameUserFree(): HasOne
    {
        return $this->hasOne(GameUserFreeProxy::modelClass(), 'member_code');
    }

    public function bankPayments(): HasMany
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'member_topup', 'code');
    }

    public function bank_payments()
    {
        return $this->bankPayments()->where('status', 1)->where('enable', 'Y');
    }

    public function topupSum()
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'member_topup', 'code')->where('status', 1)->where('enable', 'Y')->sum('value');
    }

    public function last_payment()
    {
        return $this->hasOne(BankPaymentProxy::modelClass(), 'member_topup', 'code')->where('enable', 'Y')->orderByDesc('date_topup');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(BillProxy::modelClass(), 'member_code');
    }

    public function is_pro(): HasMany
    {
        return $this->bills()->where('enable', 'Y')->where('pro_code', '<>', 0);
    }

    public function billsFree(): HasMany
    {
        return $this->hasMany(BillFreeProxy::modelClass(), 'member_code');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(MemberLogProxy::modelClass(), 'member_code');
    }

    public function withdraw(): HasMany
    {
        return $this->hasMany(WithdrawProxy::modelClass(), 'member_code');
    }

    public function withdrawFree(): HasMany
    {
        return $this->hasMany(WithdrawFreeProxy::modelClass(), 'member_code');
    }

    public function withdrawSeamless(): HasMany
    {
        return $this->hasMany(WithdrawSeamlessProxy::modelClass(), 'member_code');
    }

    public function withdrawSeamlessFree(): HasMany
    {
        return $this->hasMany(WithdrawSeamlessFreeProxy::modelClass(), 'member_code');
    }

    public function paymentsPromotion(): HasMany
    {
        return $this->hasMany(PaymentPromotionProxy::modelClass(), 'member_code');
    }

    public function paymentPromotion(): HasOne
    {
        return $this->hasOne(PaymentPromotionProxy::modelClass(), 'member_code');
    }

    public function paymentWaiting(): HasMany
    {
        return $this->hasMany(PaymentWaitingProxy::modelClass());
    }

    public function memberFreeCredit(): HasMany
    {
        return $this->hasMany(MemberFreeCreditProxy::modelClass(), 'member_code');
    }

    public function bonus_spin(): HasMany
    {
        return $this->hasMany(BonusSpinProxy::modelClass(), 'member_code');
    }

    public function memberTran(): HasMany
    {
        return $this->hasMany(MemberCreditLogProxy::modelClass(), 'member_code');
    }

    public function wallet_transaction(): MorphTo
    {
        return $this->morphTo();
    }

    public function bill()
    {
        return $this->hasOne(BillProxy::modelClass(), 'member_code');
    }

    public function member_cashback()
    {
        return $this->hasOne(MemberCashbackProxy::modelClass(), 'downline_code', 'member_code');
    }

    public function member_ic()
    {
        return $this->hasOne(MemberIcProxy::modelClass(), 'downline_code', 'member_code');
    }

    public function upline()
    {
        return $this->hasOne(MemberProxy::modelClass(), 'code', 'upline_code');
        //        return $this->belongsTo(self::class, 'upline_code','code');
    }

    public function memberReward(): HasMany
    {
        return $this->hasMany(MemberRewardLogProxy::modelClass(), 'member_code');
    }

    public function member_remark()
    {
        return $this->hasMany(MemberRemarkProxy::modelClass(), 'member_code');
    }

    public function memberCreditFree(): HasMany
    {
        return $this->hasMany(MemberCreditFreeLogProxy::modelClass(), 'member_code');
    }

    public function memberCredit(): HasMany
    {
        return $this->hasMany(MemberCreditLogProxy::modelClass(), 'member_code');
    }

    public function receivesBroadcastNotificationsOn()
    {
        return env('APP_NAME').'_members.'.$this->code;
    }

    public function payment_first()
    {
        return $this->hasOne(BankPaymentProxy::modelClass(), 'member_topup', 'code')->where('enable', 'Y')->where('status', 1)->oldest();
    }

    public function payment()
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'member_topup', 'code')->where('enable', 'Y')->where('status', 1);
    }

    public function payout()
    {
        return $this->hasMany(WithdrawSeamlessProxy::modelClass(), 'member_code', 'code')->where('enable', 'Y')->where('status', 1);
    }

    public function depositStat()
    {
        return $this->hasOne(MemberDepositStat::class, 'member_code', 'code');
    }
}
