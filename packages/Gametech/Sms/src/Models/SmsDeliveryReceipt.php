<?php

namespace Gametech\Sms\Models;

use Gametech\Sms\Contracts\SmsDeliveryReceipt as SmsDeliveryReceiptContract;
use Illuminate\Database\Eloquent\Model;

class SmsDeliveryReceipt extends Model implements SmsDeliveryReceiptContract
{
    protected $table = 'sms_delivery_receipts';

    protected $fillable = [
        'team_id',
        'campaign_id',
        'recipient_id',

        'provider',
        'message_id',
        'msisdn',
        'to',
        'network_code',
        'status',
        'err_code',
        'scts',
        'api_key',
        'message_timestamp',
        'price',

        'payload',
        'received_at',

        'process_status',
        'processed_at',
        'process_error',
    ];

    protected $casts = [
        'payload' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function recipient()
    {
        return $this->belongsTo(SmsRecipient::class, 'recipient_id');
    }

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }
}
