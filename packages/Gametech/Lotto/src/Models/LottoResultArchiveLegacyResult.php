<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Database\Factories\LottoResultArchiveLegacyResultFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LottoResultArchiveLegacyResult extends Model
{
    use HasFactory;

    protected static function newFactory(): LottoResultArchiveLegacyResultFactory
    {
        return LottoResultArchiveLegacyResultFactory::new();
    }

    protected $table = 'lotto_result_archive_legacy_results';

    protected $fillable = [
        'type',
        'name_th',
        'request_date',
        'page',
        'source_result_id',
        'lottos_name',
        'lottos_th',
        'lottos_date',
        'lottos_date_raw',
        'lottos_time',
        'lottos_number',
        'lottos_under',
        'market_code',
        'market_id',
        'source_url',
        'fetched_at',
        'fetch_status',
        'last_error',
        'checksum',
        'payload_json',
        'unique_key',
    ];

    protected $casts = [
        'request_date' => 'date:Y-m-d',
        'lottos_date' => 'datetime',
        'fetched_at' => 'datetime',
        'payload_json' => 'array',
    ];
}
