<?php

namespace Gametech\Lotto\Models;

use Gametech\Lotto\Contracts\LottoResultSource as LottoResultSourceContract;
use Illuminate\Database\Eloquent\Model;

class LottoResultSource extends Model implements LottoResultSourceContract
{
    protected $table = 'lotto_result_sources';

    protected $fillable = [
        'market_id',
        'is_active',
        'priority',
        'source_type',
        'endpoint_url',
        'http_method',
        'request_headers_json',
        'request_query_template_json',
        'request_body_template_json',
        'lookup_date_mode',
        'lookup_date_offset_days',
        'parser_type',
        'parser_config_json',
        'mapping_config_json',
        'validation_config_json',
        'retry_policy_json',
        'timeout_seconds',
        'effective_from',
        'effective_to',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
        'request_headers_json' => 'array',
        'request_query_template_json' => 'array',
        'request_body_template_json' => 'array',
        'lookup_date_offset_days' => 'integer',
        'parser_config_json' => 'array',
        'mapping_config_json' => 'array',
        'validation_config_json' => 'array',
        'retry_policy_json' => 'array',
        'timeout_seconds' => 'integer',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
    ];

    public function market()
    {
        return $this->belongsTo(LotteryMarket::class, 'market_id');
    }
}
