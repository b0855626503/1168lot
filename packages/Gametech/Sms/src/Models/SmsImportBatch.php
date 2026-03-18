<?php

namespace Gametech\Sms\Models;

use Gametech\Sms\Contracts\SmsImportBatch as SmsImportBatchContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsImportBatch extends Model implements SmsImportBatchContract
{
    use SoftDeletes;

    protected $table = 'sms_import_batches';

    protected $fillable = [
        'team_id',
        'campaign_id',

        'file_name',
        'file_mime',
        'file_size',
        'file_sha1',
        'storage_disk',
        'storage_path',

        'source_label',
        'phone_column',
        'country_code',
        'has_header',

        'consent_basis',
        'consent_note',

        'total_rows',
        'valid_phones',
        'invalid_phones',
        'duplicate_phones',
        'suppressed_phones',

        'status',
        'error_message',

        'uploaded_by',
        'meta',
    ];

    protected $casts = [
        'has_header' => 'boolean',
        'meta' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }

    public function recipients()
    {
        return $this->hasMany(SmsRecipient::class, 'import_batch_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isReady(): bool
    {
        return $this->status === 'ready';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
