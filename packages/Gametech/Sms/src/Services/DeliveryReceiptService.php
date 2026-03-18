<?php

namespace Gametech\Sms\Services;

use Gametech\Sms\Models\SmsCampaign;
use Gametech\Sms\Models\SmsDeliveryReceipt;
use Gametech\Sms\Models\SmsRecipient;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeliveryReceiptService
{
    /**
     * Provider-agnostic DLR handler
     */
    public function handleProviderDlr(string $provider, array $payload): array
    {
        $provider = strtolower(trim($provider ?: 'vonage'));

        $messageId = (string) Arr::get($payload, 'messageId', Arr::get($payload, 'message-id'));
        if ($messageId === '') {
            $messageId = (string) (Arr::get($payload, 'MessageSid') ?? Arr::get($payload, 'message_id') ?? '');
        }

        $status = (string) (Arr::get($payload, 'status') ?? Arr::get($payload, 'MessageStatus') ?? Arr::get($payload, 'SmsStatus') ?? '');
        $errCode = (string) (Arr::get($payload, 'err-code', Arr::get($payload, 'err_code')) ?? Arr::get($payload, 'ErrorCode') ?? Arr::get($payload, 'error_code') ?? '');

        if ($messageId === '' || $status === '') {
            Log::channel('sms')->warning('[DLR] missing required fields', [
                'provider' => $provider,
                'payload' => $payload,
            ]);

            return ['status' => 'missing_required_fields'];
        }

        $dlr = SmsDeliveryReceipt::updateOrCreate([
            'provider' => $provider,
            'message_id' => $messageId,
        ], [
            'status' => $status ?: null,
            'err_code' => $errCode ?: null,
            'scts' => (string) Arr::get($payload, 'scts'),

            'msisdn' => (string) Arr::get($payload, 'msisdn'),
            'to' => (string) Arr::get($payload, 'to'),
            'network_code' => (string) Arr::get($payload, 'network-code'),
            'api_key' => (string) Arr::get($payload, 'api-key'),
            'message_timestamp' => (string) Arr::get($payload, 'message-timestamp'),
            'price' => (string) Arr::get($payload, 'price'),
            'payload' => $payload,
            'received_at' => now(),
            'process_status' => 'pending',
        ]);

        if ($dlr->process_status === 'processed') {
            return ['status' => 'duplicate_ignored', 'dlr_id' => $dlr->id];
        }

        try {
            return DB::transaction(function () use ($provider, $payload, $dlr, $messageId) {

                $recipient = SmsRecipient::where('provider_message_id', $messageId)->first();

                if (! $recipient) {
                    $dlr->process_status = 'ignored';
                    $dlr->process_error = 'Recipient not found by provider_message_id';
                    $dlr->processed_at = now();
                    $dlr->save();

                    Log::channel('sms')->warning('[DLR] recipient not found', [
                        'provider' => $provider,
                        'messageId' => $messageId,
                        'payload' => $payload,
                    ]);

                    return ['status' => 'recipient_not_found', 'dlr_id' => $dlr->id];
                }

                $dlr->recipient_id = $recipient->id;
                $dlr->campaign_id = $recipient->campaign_id;
                $dlr->team_id = $recipient->team_id;

                if (! $recipient->provider) {
                    $recipient->provider = $provider;
                }

                $before = (string) $recipient->status;

                $recipient->applyDeliveryReceipt($payload);
                $recipient->save();

                $after = (string) $recipient->status;

                if ($recipient->campaign_id) {
                    $this->updateCampaignCountersOnTransition((int) $recipient->campaign_id, $before, $after);

                    // ✅ สำคัญ: ปิดแคมเปญจาก “ความจริงใน recipients” ไม่อิง counter ที่อาจเพี้ยน
                    $this->finalizeCampaignIfDoneByRecipients((int) $recipient->campaign_id);
                }

                $dlr->process_status = 'processed';
                $dlr->processed_at = now();
                $dlr->save();

                return [
                    'status' => 'processed',
                    'dlr_id' => $dlr->id,
                    'recipient_id' => $recipient->id,
                    'before' => $before,
                    'after' => $after,
                ];
            });
        } catch (\Throwable $e) {
            $dlr->process_status = 'failed';
            $dlr->process_error = $e->getMessage();
            $dlr->processed_at = now();
            $dlr->save();

            Log::channel('sms')->error('[DLR] processing failed', [
                'provider' => $provider,
                'messageId' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'dlr_id' => $dlr->id];
        }
    }

    private function updateCampaignCountersOnTransition(int $campaignId, string $before, string $after): void
    {
        if ($before === $after) {
            return;
        }

        SmsCampaign::whereKey($campaignId)->update([
            'delivered_count' => DB::raw($after === 'delivered' ? 'delivered_count + 1' : 'delivered_count'),
            'failed_count' => DB::raw($after === 'failed' ? 'failed_count + 1' : 'failed_count'),
            'sent_count' => DB::raw($after === 'sent' ? 'sent_count + 1' : 'sent_count'),
        ]);
    }

    /**
     * ✅ Finalize campaign based on SmsRecipient rows (source of truth)
     * - total = count recipients (excluding soft-deleted by default scope)
     * - done  = count recipients where status in (delivered, failed)
     */
    private function finalizeCampaignIfDoneByRecipients(int $campaignId): void
    {
        $campaign = SmsCampaign::query()->lockForUpdate()->find($campaignId);
        if (! $campaign) {
            return;
        }

        if ((string) $campaign->status === 'completed') {
            return;
        }

        $total = SmsRecipient::query()
            ->where('campaign_id', $campaignId)
            ->count();

        if ($total <= 0) {
            return;
        }

        $done = SmsRecipient::query()
            ->where('campaign_id', $campaignId)
            ->whereIn('status', ['delivered', 'failed'])
            ->count();

        if ($done >= $total) {
            $campaign->status = 'completed';
            $campaign->finished_at = $campaign->finished_at ?: now();
            $campaign->save();
        }
    }

    // Backward compatibility
    public function handleVonageDlr(array $payload): array
    {
        return $this->handleProviderDlr('vonage', $payload);
    }
}
