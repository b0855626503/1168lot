<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoResultArchive as LottoResultArchiveContract;
use Illuminate\Database\Eloquent\Model;

class LottoResultArchive extends Model implements LottoResultArchiveContract
{
    protected $table = 'lotto_result_archives';

    protected $fillable = [
        'market_code',
        'draw_date',
        'draw_key',
        'result_set',
        'result_hash',
        'source_draw_id',
        'source_type',
        'correction_count',
        'previous_result_set',
        'source_info_json',
        'corrected_at',
    ];

    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_code', 'code');
    }

    protected $casts = [
        'draw_date' => 'date',
        'result_set' => 'array',
        'source_draw_id' => 'integer',
        'correction_count' => 'integer',
        'previous_result_set' => 'array',
        'source_info_json' => 'array',
        'corrected_at' => 'datetime',
    ];
}
