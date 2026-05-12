<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class LottoResultArchiveLog extends Model
{
    public $timestamps = false;

    protected $table = 'lotto_result_archive_logs';

    protected $fillable = [
        'archive_id',
        'market_code',
        'draw_date',
        'draw_key',
        'action',
        'run_id',
        'status',
        'old_result_set',
        'new_result_set',
        'changed_keys',
        'source_info_json',
        'error_message',
        'trace_json',
        'created_at',
    ];

    protected $casts = [
        'archive_id' => 'integer',
        'draw_date' => 'date',
        'old_result_set' => 'array',
        'new_result_set' => 'array',
        'changed_keys' => 'array',
        'source_info_json' => 'array',
        'trace_json' => 'array',
        'created_at' => 'datetime',
    ];
}
