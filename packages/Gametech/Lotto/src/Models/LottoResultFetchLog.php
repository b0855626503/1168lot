<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoResultFetchLog as LottoResultFetchLogContract;
use Illuminate\Database\Eloquent\Model;

class LottoResultFetchLog extends Model implements LottoResultFetchLogContract
{
    public $timestamps = false;
    protected $table = 'lotto_result_fetch_logs';

    protected $fillable = [
        'draw_id',
        'market_id',
        'source_id',
        'attempt_no',
        'status',
        'pipeline_stage',
        'run_id',
        'request_url',
        'request_meta_json',
        'response_http_status',
        'response_body',
        'parsed_payload_json',
        'normalized_result_json',
        'selection_debug_json',
        'is_dry_run',
        'is_manual_settle',
        'is_manual_retry',
        'error_message',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'draw_id' => 'integer',
        'market_id' => 'integer',
        'source_id' => 'integer',
        'attempt_no' => 'integer',
        'request_meta_json' => 'array',
        'parsed_payload_json' => 'array',
        'normalized_result_json' => 'array',
        'selection_debug_json' => 'array',
        'is_dry_run' => 'boolean',
        'is_manual_settle' => 'boolean',
        'is_manual_retry' => 'boolean',
        'response_http_status' => 'integer',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
    ];
}
