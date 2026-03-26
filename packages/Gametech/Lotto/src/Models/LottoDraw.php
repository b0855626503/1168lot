<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoDraw as LottoDrawContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoDraw - งวดหวย
 * Core entity ของระบบ
 *
 * Status Flow:
 * draft → open → closed → resulted
 */
class LottoDraw extends Model implements LottoDrawContract
{
    protected $table = 'lotto_draws';
    protected $dates = ['draw_date', 'open_at', 'opened_at', 'close_at', 'closed_at', 'result_at'];

    protected $fillable = [
        'market_id',
        'draw_date',     // วันงวด (date)
        'open_at',       // เปิดรับ (datetime)
        'opened_at',     // เวลาเปิดรับจริง
        'open_mode',     // scheduled | manual
        'close_at',      // ปิดรับ (datetime)
        'closed_at',     // เวลาปิดรับจริง
        'close_mode',    // scheduled | manual
        'result_at',     // ประกาศผล (datetime, nullable)
        'status',        // draft | open | closed | resulted
        'result_number', // เลขที่ออก (json array หรือ string)
        'created_by',    // user_id
    ];

    protected $casts = [
        'draw_date' => 'date',
        'open_at' => 'datetime',
        'opened_at' => 'datetime',
        'close_at' => 'datetime',
        'closed_at' => 'datetime',
        'result_at' => 'datetime',
        'open_mode' => 'string',
        'close_mode' => 'string',
        'result_number' => 'array',
    ];

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeResulted($query)
    {
        return $query->where('status', 'resulted');
    }

    // Relationships
    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }

    public function betSettings()
    {
        return $this->hasMany(LottoDrawBetSetting::class, 'draw_id');
    }

    public function tickets()
    {
        return $this->hasMany(LottoTicket::class, 'draw_id');
    }

    public function exposures()
    {
        return $this->hasMany(LottoNumberExposure::class, 'draw_id');
    }

    public function blockedNumbers()
    {
        return $this->hasMany(LottoNumberBlock::class, 'draw_id');
    }
}

