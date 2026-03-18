<?php

namespace Gametech\Sms\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OutboundSmsService
{
    protected string $provider;
    protected string $endpoint;
    protected int $timeout;

    public function __construct()
    {
        // ให้ตรงกับ config/sms.php
        $this->provider = (string) config('sms.default', 'vonage');

        // Vonage SMS REST endpoint
        $this->endpoint = 'https://rest.nexmo.com/sms/json';

        // ไม่เดา key ใหม่ใน config: ใช้ env ตรง ๆ
        $this->timeout = (int) env('VONAGE_TIMEOUT', 10);
    }

    /**
     * ส่ง SMS ออก
     *
     * @param  string  $to   เบอร์ปลายทาง (แนะนำ E.164)
     * @param  string  $text ข้อความ
     * @param  array   $opts options:
     *   - provider (string) default config('sms.default')
     *   - from (string) sender override
     *   - client_ref (string|int) reference อ้างอิงภายใน (เช่น recipient_id)
     *   - unicode (bool) true => ส่งแบบ unicode (ไทย/emoji)
     *   - callback_url (string) DLR callback url override
     *   - country_code (string) ค่า default '66' ใช้เมื่อเบอร์ขึ้นต้นด้วย 0
     *
     * @return array {
     *   success: bool,
     *   provider: string,
     *   provider_message_id?: string|null,
     *   to?: string|null,
     *   price?: string|null,
     *   currency?: string|null,
     *   error_code?: string,
     *   error_message?: string,
     *   raw?: array
     * }
     */
    public function send(string $to, string $text, array $opts = []): array
    {
        $provider = (string) ($opts['provider'] ?? $this->provider);

        // ตอนนี้รองรับแค่ vonage (ยังไม่เดา provider อื่น)
        if ($provider !== 'vonage') {
            return $this->fail($provider, 'UNSUPPORTED_PROVIDER', 'Unsupported SMS provider: ' . $provider);
        }

        // ให้ตรงกับ config/sms.php (credentials.*)
        $apiKey = (string) config("sms.providers.$provider.credentials.api_key");
        $apiSecret = (string) config("sms.providers.$provider.credentials.api_secret");

        $from = (string) (
            $opts['from']
            ?? config("sms.providers.$provider.credentials.from", 'GAMETECH')
        );

        if ($apiKey === '' || $apiSecret === '') {
            return $this->fail(
                $provider,
                'MISSING_CREDENTIALS',
                'Vonage credentials are missing (VONAGE_API_KEY / VONAGE_API_SECRET).'
            );
        }

        $countryCode = (string) ($opts['country_code'] ?? '66');
        $toNormalized = $this->normalizePhone($to, $countryCode);

        $payload = [
            'api_key'    => $apiKey,
            'api_secret' => $apiSecret,
            'to'         => $toNormalized,
            'from'       => $from,
            'text'       => $text,
        ];

        // unicode สำหรับไทย/emoji
        if (! empty($opts['unicode'])) {
            $payload['type'] = 'unicode';
        }

        // อ้างอิงภายใน (ไปโผล่ในบาง response / report ได้)
        if (! empty($opts['client_ref'])) {
            $payload['client-ref'] = (string) $opts['client_ref'];
        }

        // DLR callback
        // - ถ้า caller ส่ง callback_url มาให้ใช้ค่านั้น
        // - ไม่งั้นดึงจาก config/sms.php ถ้ามี
        $callbackUrl = (string) ($opts['callback_url'] ?? '');
        if ($callbackUrl === '') {
            $callbackUrl = (string) config("sms.providers.$provider.webhooks.dlr.url", '');
        }

        if ($callbackUrl !== '') {
            $payload['callback'] = $callbackUrl;
        }

        try {
            $res = Http::timeout($this->timeout)
                ->asJson()
                ->post($this->endpoint, $payload);

            if (! $res->ok()) {
                return $this->fail(
                    $provider,
                    'HTTP_ERROR',
                    'Vonage HTTP error',
                    ['status' => $res->status(), 'body' => $res->body()]
                );
            }

            $data = $res->json();

            return $this->mapVonageResponse($data);

        } catch (\Throwable $e) {
            Log::error('[OutboundSmsService] exception', [
                'provider' => $provider,
                'to'       => $toNormalized,
                'message'  => $e->getMessage(),
            ]);

            return $this->fail($provider, 'EXCEPTION', $e->getMessage());
        }
    }

    protected function mapVonageResponse(array $data): array
    {
        if (empty($data['messages'][0]) || ! is_array($data['messages'][0])) {
            return $this->fail('vonage', 'INVALID_RESPONSE', 'Invalid Vonage response', $data);
        }

        $msg = $data['messages'][0];

        // status = 0 => success
        if ((string) ($msg['status'] ?? '') === '0') {
            return [
                'success'             => true,
                'provider'            => 'vonage',
                'provider_message_id' => $msg['message-id'] ?? null,
                'to'                  => $msg['to'] ?? null,
                'price'               => $msg['message-price'] ?? null,
                'currency'            => isset($msg['message-price']) ? 'EUR' : null,
                'raw'                 => $data,
            ];
        }

        return $this->fail(
            'vonage',
            (string) ($msg['status'] ?? 'UNKNOWN'),
            (string) ($msg['error-text'] ?? 'Send SMS failed'),
            $data,
            $msg['message-id'] ?? null
        );
    }

    protected function fail(string $provider, string $code, string $message, array $raw = [], ?string $providerMessageId = null): array
    {
        return [
            'success'             => false,
            'provider'            => $provider,
            'error_code'          => $code,
            'error_message'       => $message,
            'provider_message_id' => $providerMessageId,
            'raw'                 => $raw,
        ];
    }

    /**
     * normalize เบอร์:
     * - ตัดอักขระที่ไม่ใช่ตัวเลข/+ ออก
     * - ถ้าขึ้นต้นด้วย 0 => แปลงเป็น +{countryCode}
     * - ถ้าเป็นตัวเลขล้วน (ไม่มี +) => เติม + นำหน้า
     */
    protected function normalizePhone(string $phone, string $countryCode = '66'): string
    {
        $p = preg_replace('/[^0-9+]/', '', $phone) ?? '';

        if ($p === '') {
            return $p;
        }

        if (Str::startsWith($p, '0')) {
            $p = '+' . ltrim($countryCode, '+') . substr($p, 1);
        } elseif (! Str::startsWith($p, '+')) {
            // ถ้า user ส่งมาเป็น 6685... หรือ 855... ให้เติม + นำหน้าไว้ก่อน
            $p = '+' . $p;
        }

        return $p;
    }
}
