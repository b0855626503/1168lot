<?php

namespace Gametech\Sms\Models;

use Gametech\Sms\Contracts\SmsRecipient as SmsRecipientContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Arr;

class SmsRecipient extends Model implements SmsRecipientContract
{
    use SoftDeletes;

    protected $table = 'sms_recipients';

    protected $fillable = [
        'team_id',
        'campaign_id',
        'import_batch_id',

        'source_type',
        'source_id',

        'phone_e164',
        'phone_raw',
        'country_code',

        'first_name',
        'last_name',

        'has_consent',
        'consent_at',
        'is_opted_out',
        'opted_out_at',

        'status',

        'provider',
        'provider_message_id',
        'attempts',

        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',

        'error_code',
        'error_message',

        'recipient_fingerprint',
        'meta',

        // DLR fields
        'dlr_status_raw',
        'dlr_err_code',
        'dlr_scts',
        'dlr_received_at',
        'dlr_payload',
    ];

    protected $casts = [
        'has_consent' => 'boolean',
        'is_opted_out' => 'boolean',
        'consent_at' => 'datetime',
        'opted_out_at' => 'datetime',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'dlr_received_at' => 'datetime',
        'meta' => 'array',
        'dlr_payload' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(SmsCampaign::class, 'campaign_id');
    }

    public function importBatch()
    {
        return $this->belongsTo(SmsImportBatch::class, 'import_batch_id');
    }

    public function deliveryReceipts()
    {
        return $this->hasMany(SmsDeliveryReceipt::class, 'recipient_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Status helpers (outbound)
    |--------------------------------------------------------------------------
    */

    public function markQueued(): void
    {
        $this->status = 'queued';
        $this->queued_at = now();
    }

    public function markSent(?string $providerMessageId = null): void
    {
        $this->status = 'sent';
        if ($providerMessageId) {
            $this->provider_message_id = $providerMessageId;
        }
        $this->sent_at = now();
    }

    public function markDelivered(): void
    {
        $this->status = 'delivered';
        $this->delivered_at = now();
    }

    public function markFailed(?string $code = null, ?string $message = null): void
    {
        $this->status = 'failed';
        $this->failed_at = now();
        $this->error_code = $code;
        $this->error_message = $message;
    }

    /*
    |--------------------------------------------------------------------------
    | Apply DLR (provider callback)
    |--------------------------------------------------------------------------
    |
    | เป้าหมาย:
    | - เก็บ raw DLR ลงฟิลด์ที่มีอยู่จริง (dlr_*)
    | - map status ผ่าน config('sms.dlr_status_map.{provider}')
    | - ป้องกัน downgrade: ถ้า delivered แล้วจะไม่กลับเป็น failed
    | - ไม่เขียนทับเวลาเดิม ถ้ามีอยู่แล้ว (ใช้ ?: now())
    |
    */

    public function applyDeliveryReceipt(array $dlr): void
    {
        $provider = strtolower((string) ($this->provider ?: config('sms.default', 'vonage')));

        // รองรับ key ยอดนิยมหลายเจ้า (ไม่ผูก vonage-only)
        $rawStatus = strtolower((string) (
            Arr::get($dlr, 'status')
            ?? Arr::get($dlr, 'MessageStatus')
            ?? Arr::get($dlr, 'SmsStatus')
            ?? ''
        ));

        // error code ของแต่ละเจ้าจะต่างกันได้ → เก็บเป็น string เฉย ๆ
        $errCode = (string) (
            Arr::get($dlr, 'err-code')
            ?? Arr::get($dlr, 'err_code')
            ?? Arr::get($dlr, 'error_code')
            ?? Arr::get($dlr, 'ErrorCode')
            ?? ''
        );

        $scts = (string) (
            Arr::get($dlr, 'scts')
            ?? Arr::get($dlr, 'Scts')
            ?? ''
        );

        // persist raw
        $this->dlr_status_raw = $rawStatus !== '' ? $rawStatus : null;
        $this->dlr_err_code = $errCode !== '' ? $errCode : null;
        $this->dlr_scts = $scts !== '' ? $scts : null;
        $this->dlr_received_at = now();
        $this->dlr_payload = $dlr;

        // terminal state guard
        $isDeliveredAlready = ((string) $this->status === 'delivered');
        $isFailedAlready = ((string) $this->status === 'failed');

        // map provider raw status -> our canonical status
        $mapped = (string) config("sms.dlr_status_map.$provider.$rawStatus", '');

        // err-code non-zero โดยทั่วไปถือว่า fail (แต่ห้าม downgrade delivered)
        $isErr = ($errCode !== '' && $errCode !== '0');

        if ($isErr && ! $isDeliveredAlready) {
            $this->status = 'failed';
            $this->failed_at = $this->failed_at ?: now();

            // เติม error เฉพาะตอนยังว่าง เพื่อลดการเขียนทับ
            $this->error_code = $this->error_code ?: $errCode;
            $this->error_message = $this->error_message ?: ('DLR err-code=' . $errCode);

            return;
        }

        // ถ้าไม่มี mapping ก็ไม่ไปยุ่งกับ status (แต่ยังเก็บ raw ไว้แล้ว)
        if ($mapped === '') {
            return;
        }

        // ป้องกัน downgrade: delivered คือ final
        if ($isDeliveredAlready) {
            return;
        }

        if ($mapped === 'delivered') {
            $this->status = 'delivered';
            $this->delivered_at = $this->delivered_at ?: now();
            return;
        }

        if ($mapped === 'failed') {
            $this->status = 'failed';
            $this->failed_at = $this->failed_at ?: now();

            // ถ้ายังไม่มี error_code/message ให้เติมจากสถานะ
            if (! $this->error_code) {
                $this->error_code = 'DLR_STATUS_' . strtoupper($rawStatus ?: 'FAILED');
            }
            if (! $this->error_message) {
                $this->error_message = 'DLR status=' . ($rawStatus ?: 'failed');
            }

            return;
        }

        // sent / in-transit states
        if ($mapped === 'sent') {
            if (! $isFailedAlready) {
                $this->status = 'sent';
                $this->sent_at = $this->sent_at ?: now();
            }
            return;
        }

        // default: do nothing
    }
}
