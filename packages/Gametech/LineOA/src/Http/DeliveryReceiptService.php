<?php

namespace Gametech\LineOA\Http;

use Gametech\Sms\Models\SmsCampaign;
use Gametech\Sms\Models\SmsDeliveryReceipt;
use Gametech\Sms\Models\SmsRecipient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryReceiptService
{
    /**
     * Handle Vonage SMS API DLR payload.
     * - Create sms_delivery_receipts row (idempotent)
     * - Find SmsRecipient by provider_message_id == messageId
     * - Apply DLR mapping to SmsRecipient
     */
    public function handleVonageDlr(array $payload): array
    {
        $provider = 'vonage';

        // normalize keys
        $messageId = (string) Arr::get($payload, 'messageId', Arr::get($payload, 'message-id'));
        $status    = (string) Arr::get($payload, 'status');
        $errCode   = (string) Arr::get($payload, 'err-code', Arr::get($payload, 'err_code'));
        $msisdn    = (string) Arr::get($payload, 'msisdn');
        $scts      = (string) Arr::get($payload, 'scts');

        // idempotent insert
        $dlr = SmsDeliveryReceipt::firstOrCreate([
            'provider'   => $provider,
            'message_id' => $messageId,
            'status'     => $status ?: null,
            'err_code'   => $errCode ?: null,
            'scts'       => $scts ?: null,
        ], [
            'msisdn'            => $msisdn ?: null,
            'to'                => (string) Arr::get($payload, 'to'),
            'network_code'      => (string) Arr::get($payload, 'network-code'),
            'api_key'           => (string) Arr::get($payload, 'api-key'),
            'message_timestamp' => (string) Arr::get($payload, 'message-timestamp'),
            'price'             => (string) Arr::get($payload, 'price'),
            'payload'           => $payload,
            'received_at'       => now(),
            'process_status'    => 'pending',
        ]);

        // ถ้าเคย processed แล้ว ให้จบแบบสุภาพ
        if ($dlr->process_status === 'processed') {
            return ['status' => 'duplicate_ignored', 'dlr_id' => $dlr->id];
        }

        try {
            return DB::transaction(function () use ($payload, $dlr, $messageId) {

                /** @var SmsRecipient|null $recipient */
                $recipient = SmsRecipient::where('provider_message_id', $messageId)->first();

                if (! $recipient) {
                    // ไม่มี recipient ในระบบเรา (อาจเป็นข้อความนอก campaign หรือ data หลุด)
                    $dlr->process_status = 'ignored';
                    $dlr->process_error = 'Recipient not found by provider_message_id';
                    $dlr->processed_at = now();
                    $dlr->save();

                    Log::channel('sms')->warning('[VonageDLR] recipient not found', [
                        'messageId' => $messageId,
                        'payload' => $payload,
                    ]);

                    return ['status' => 'recipient_not_found', 'dlr_id' => $dlr->id];
                }

                // link dlr -> recipient/campaign/team
                $dlr->recipient_id = $recipient->id;
                $dlr->campaign_id  = $recipient->campaign_id;
                $dlr->team_id      = $recipient->team_id;

                $before = $recipient->status;

                // apply mapping + store raw payload
                $recipient->applyDeliveryReceipt($payload);
                $recipient->save();

                $after = $recipient->status;

                // update campaign counters (เฉพาะ transitions สำคัญ เพื่อลดความเสี่ยงนับเพี้ยน)
                if ($recipient->campaign_id) {
                    $this->updateCampaignCountersOnTransition($recipient->campaign_id, $before, $after);
                }

                $dlr->process_status = 'processed';
                $dlr->processed_at = now();
                $dlr->save();

                return [
                    'status' => 'processed',
                    'dlr_id' => $dlr->id,
                    'recipient_id' => $recipient->id,
                    'before' => $before,
                    'after'  => $after,
                ];
            });
        } catch (\Throwable $e) {
            $dlr->process_status = 'failed';
            $dlr->process_error = $e->getMessage();
            $dlr->processed_at = now();
            $dlr->save();

            Log::channel('sms')->error('[VonageDLR] processing failed', [
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'dlr_id' => $dlr->id];
        }
    }

    private function updateCampaignCountersOnTransition(int $campaignId, string $before, string $after): void
    {
        // นับเฉพาะ transitions ไป terminal states เพื่อไม่ทำให้ counter สั่น
        if ($before === $after) {
            return;
        }

        SmsCampaign::whereKey($campaignId)->update([
            'delivered_count' => DB::raw($after === 'delivered' ? 'delivered_count + 1' : 'delivered_count'),
            'failed_count'    => DB::raw($after === 'failed' ? 'failed_count + 1' : 'failed_count'),
        ]);
    }
}
