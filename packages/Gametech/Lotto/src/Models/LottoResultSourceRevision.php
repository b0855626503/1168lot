<?php

namespace Gametech\Lotto\Models;

use Illuminate\Database\Eloquent\Model;

class LottoResultSourceRevision extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'lotto_result_source_revisions';

    protected $fillable = [
        'source_id',
        'revision_no',
        'snapshot_json',
        'config_hash',
        'changed_by',
        'reason',
        'created_at',
    ];

    protected $casts = [
        'source_id' => 'integer',
        'revision_no' => 'integer',
        'snapshot_json' => 'array',
        'config_hash' => 'string',
        'changed_by' => 'integer',
        'reason' => 'string',
        'created_at' => 'datetime',
    ];

    public function source()
    {
        return $this->belongsTo(LottoResultSource::class, 'source_id');
    }
}
