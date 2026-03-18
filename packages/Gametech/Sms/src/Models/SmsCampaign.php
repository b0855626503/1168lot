<?php

namespace Gametech\Sms\Models;

use Gametech\Sms\Contracts\SmsCampaign as SmsCampaignContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SmsCampaign extends Model implements SmsCampaignContract
{
    use SoftDeletes;

    protected $table = 'sms_campaigns';

    protected $fillable = [
        'team_id',
        'code',
        'name',
        'subject',
        'sender_name',
        'message',

        'audience_mode',
        'filter_json',
        'respect_opt_out',
        'require_consent',

        'scheduled_at',
        'window_start',
        'window_end',
        'timezone',

        'throttle_per_minute',
        'max_attempts',
        'retry_backoff_seconds',

        'provider',
        'status',

        'total_recipients',
        'queued_count',
        'sent_count',
        'delivered_count',
        'failed_count',
        'invalid_count',
        'duplicate_count',
        'opted_out_count',
        'suppressed_count',

        'created_by',
        'updated_by',
        'started_at',
        'finished_at',

        'meta',
    ];

    protected $casts = [
        'filter_json' => 'array',
        'meta' => 'array',
        'respect_opt_out' => 'boolean',
        'require_consent' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function recipients()
    {
        return $this->hasMany(SmsRecipient::class, 'campaign_id');
    }

    public function importBatches()
    {
        return $this->hasMany(SmsImportBatch::class, 'campaign_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers / Status checks
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
