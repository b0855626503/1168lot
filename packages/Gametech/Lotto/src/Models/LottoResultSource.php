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
        'fetch_config_json',
        'selection_config_json',
        'readiness_config_json',
        'pipeline_version',
        'fetch_strategy',
        'selection_stage',
        'supports_partial',
        'requires_browser',
        'shadow_enabled',
        'cutover_enabled',
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
        'fetch_config_json' => 'array',
        'selection_config_json' => 'array',
        'readiness_config_json' => 'array',
        'pipeline_version' => 'string',
        'fetch_strategy' => 'string',
        'selection_stage' => 'string',
        'supports_partial' => 'boolean',
        'requires_browser' => 'boolean',
        'shadow_enabled' => 'boolean',
        'cutover_enabled' => 'boolean',
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

    public function revisions()
    {
        return $this->hasMany(LottoResultSourceRevision::class, 'source_id')->orderBy('revision_no');
    }
}
