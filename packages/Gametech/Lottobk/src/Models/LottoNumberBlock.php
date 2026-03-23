<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoNumberBlock as LottoNumberBlockContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoNumberBlock - เลขอั้น
 * v1: ใช้แค่ block mode
 */
class LottoNumberBlock extends Model implements LottoNumberBlockContract
{
    protected $table = 'lotto_number_blocks';

    protected $fillable = [
        'draw_id',
        'bet_type',
        'number',
        'mode',         // block | limit_future
        'reason',       // reason text
        'blocked_by',   // user_id
        'blocked_at',   // datetime
    ];

    protected $casts = [
        'blocked_at' => 'datetime',
    ];

    protected $dates = ['blocked_at'];

    // Relationships
    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }
}

