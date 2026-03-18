<?php

namespace Gametech\LineOA\Models;

use DateTimeInterface;
use Gametech\LineOA\Contracts\LineWebhookLog as LineWebhookLogContract;
use Illuminate\Database\Eloquent\Model;

class LineWebhookLog extends Model implements LineWebhookLogContract
{
    protected $table = 'line_webhook_logs';

    /** ฟิลด์ที่ fill ได้ (ต้องมี line_account_id ตาม error) */
    // อนุญาตให้ fill ได้ทุกฟิลด์ที่เราใช้ใน controller
    protected $fillable = [
        'line_account_id',
        'line_conversation_id',
        'line_contact_id',
        'line_message_id',
        'event_type',
        'event_id',
        'request_id',
        'ip',
        'user_agent',
        'headers',
        'body',
        'http_status',
        'is_processed',
        'processed_at',
        'error_message',
    ];

    // แนะนำให้ cast พวกนี้ จะได้ใช้งานสะดวก
    protected $casts = [
        'headers'      => 'array',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    /** รูปแบบวันที่เวลา */
    protected $dateFormat = 'Y-m-d H:i:s';

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

}
