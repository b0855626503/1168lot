<?php

namespace Gametech\Sms\Jobs;

use Gametech\Sms\Models\SmsRecipient;
use Gametech\Sms\Services\OutboundSmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;

    public function __construct(public int $recipientId) {}

    public function handle(OutboundSmsService $service): void
    {
        $recipient = SmsRecipient::with('campaign')->find($this->recipientId);

        if (! $recipient) {
            throw new ModelNotFoundException('SmsRecipient not found');
        }

        // กันยิงซ้ำ
        if (! in_array((string) $recipient->status, ['queued'], true)) {
            return;
        }

        // กัน race: lock queued -> sending + increment attempts แบบ atomic
        $affected = SmsRecipient::query()
            ->whereKey($recipient->getKey())
            ->where('status', 'queued')
            ->update([
                'status'   => 'sending',
                'attempts' => DB::raw('COALESCE(attempts, 0) + 1'),
            ]);

        if ($affected < 1) {
            return; // มี worker อื่นแทรกไปแล้ว
        }

        $recipient->refresh();

        if (! $recipient->campaign) {
            $recipient->markFailed('CAMPAIGN_NOT_FOUND', 'SmsCampaign not found for this recipient');
            $recipient->save();
            return;
        }

        // ใช้ field จริงตาม schema
        $to = trim((string) ($recipient->phone_e164 ?: $recipient->phone_raw ?: ''));
        $text = $this->resolveCampaignMessage($recipient->campaign);

        if ($to === '' || $text === '') {
            Log::warning('[SendSmsJob] Missing recipient phone or campaign message', [
                'recipient_id'     => $recipient->getKey(),
                'campaign_id'      => $recipient->campaign_id,
                'phone_e164'       => $recipient->phone_e164,
                'phone_raw'        => $recipient->phone_raw,
                'message_len'      => strlen((string) $text),
                'campaign_message' => $recipient->campaign->getAttribute('message'),
            ]);

            $recipient->markFailed('INVALID_PAYLOAD', 'Missing recipient phone or campaign message');
            $recipient->save();
            return;
        }

        // Provider: ถ้า campaign มี provider ให้ใช้ ไม่งั้น fallback default
        $provider = (string) (
            $recipient->campaign->provider
            ?? config('sms.default', 'vonage')
        );

        // Sender: campaign > provider default
        $from = (string) (
        $recipient->campaign->sender_name
            ?: config("sms.providers.$provider.credentials.from")
            ?: config('sms.providers.vonage.credentials.from', 'GAMETECH')
        );

        // DLR callback จาก config (ถ้า provider รองรับ)
        $callbackUrl = (string) config(
            "sms.providers.$provider.webhooks.dlr.url",
            ''
        );

        $result = $service->send($to, $text, [
            'provider'     => $provider,
            'from'         => $from,
            'client_ref'   => (string) $recipient->getKey(),
            'unicode'      => true, // ไทย/emoji แนะนำเปิด
            'callback_url' => $callbackUrl ?: null,
        ]);

        if (($result['success'] ?? false) === true) {
            // บันทึก provider ที่ใช้จริง (ถ้ายังไม่มี)
            if (! $recipient->provider) {
                $recipient->provider = (string) ($result['provider'] ?? $provider);
            }

            $recipient->markSent($result['provider_message_id'] ?? null);
            $recipient->save();
            return;
        }

        // failed
        if (! $recipient->provider) {
            $recipient->provider = (string) ($result['provider'] ?? $provider);
        }

        $recipient->markFailed(
            $result['error_code'] ?? null,
            $result['error_message'] ?? null
        );
        $recipient->save();
    }

    /**
     * เผื่อ schema แคมเปญใช้ชื่อคอลัมน์ไม่ตรงกัน 100%
     */
    private function resolveCampaignMessage($campaign): string
    {
        foreach (['message', 'text', 'content', 'body'] as $key) {
            $val = trim((string) ($campaign->getAttribute($key) ?? ''));
            if ($val !== '') {
                return $val;
            }
        }

        return '';
    }
}
