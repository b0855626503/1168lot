<?php

namespace Gametech\Marketing\Models;

use Gametech\Marketing\Contracts\MarketingMember as MarketingMemberContract;
use Gametech\Member\Models\Member;
use Gametech\Payment\Models\BankPaymentProxy;
use Gametech\Payment\Models\WithdrawSeamlessProxy;

class MarketingMember extends Member implements MarketingMemberContract
{
    protected $fillable = [
        'refer_code',
        'bank_code',
        'upline_code',
        'name',
        'firstname',
        'lastname',
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
        'team_id',
        'campaign_id',
        'maxwithdraw_day',
        'game_user',
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
        'maxwithdraw_day' => 'decimal:2',
        'team_id' => 'integer',
        'campaign_id' => 'integer',
    ];

    public function getIsFromMarketingAttribute()
    {
        return ! is_null($this->team_id) || ! is_null($this->campaign_id);
    }

    public function getMarketingSourceAttribute(): string
    {
        if ($this->team_id && $this->campaign_id) {
            return 'team_campaign'; // หรือ 'both'
        }

        if ($this->team_id) {
            return 'team';
        }

        if ($this->campaign_id) {
            return 'campaign';
        }

        return 'none'; // เผื่อในอนาคตกรณี team_id, campaign_id = null
    }

    public function team()
    {
        return $this->belongsTo(MarketingTeamProxy::modelClass(), 'team_id');
    }

    public function campaign()
    {
        return $this->belongsTo(MarketingCampaignProxy::modelClass(), 'campaign_id');
    }

    public function scopeFromTeamOnly($query)
    {
        return $query->whereNotNull('team_id')->whereNull('campaign_id');
    }

    public function scopeFromCampaignOnly($query)
    {
        return $query->whereNull('team_id')->whereNotNull('campaign_id');
    }

    public function scopeFromBoth($query)
    {
        return $query->whereNotNull('team_id')->whereNotNull('campaign_id');
    }

    public function scopeFromMarketing($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('team_id')->orWhereNotNull('campaign_id');
        });
    }

    public function getTeamNameAttribute()
    {
        return $this->team ? $this->team->name : '-';
    }

    public function getCampaignNameAttribute()
    {
        return $this->campaign ? $this->campaign->name : '-';
    }

    public function deposits()
    {
        return $this->hasMany(BankPaymentProxy::modelClass(), 'member_topup', 'code');
    }

    public function withdrawals()
    {
        return $this->hasMany(WithdrawSeamlessProxy::modelClass(), 'member_code', 'code');
    }

    public function firstDeposit()
    {
        return $this->hasOne(BankPaymentProxy::modelClass(), 'member_topup', 'code')
            ->where('status', 1)
            ->where('enable', 'Y')
            ->orderBy('date_approve', 'asc');
    }
}
