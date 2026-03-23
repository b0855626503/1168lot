<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoNumberExposure as LottoNumberExposureContract;
use Illuminate\Database\Eloquent\Model;

/**
 * LottoNumberExposure - ยอดสะสมต่อเลข
 * ❤️ หัวใจของระบบ
 *
 * ต้องใช้ row lock เวลาแทง
 * ต้อง atomic transaction
 */
class LottoNumberExposure extends Model implements LottoNumberExposureContract
{
    protected $table = 'lotto_number_exposures';

    protected $fillable = [
        'draw_id',
        'bet_type',     // top_3, tod_3, etc.
        'number',       // เลข (string: "123" / "45" / etc.)
        'sold_amount',  // ยอดสะสม
    ];

    protected $casts = [
        'sold_amount' => 'decimal:2',
    ];

    // Relationships
    public function draw()
    {
        return $this->belongsTo(LottoDraw::class, 'draw_id');
    }
}

