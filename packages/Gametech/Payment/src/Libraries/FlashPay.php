<?php

declare(strict_types=1);

namespace Gametech\Payment\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FlashPay
{
    /**
     * Create deposit (PromptPay QR)
     *
     * POST /api/v1/payment/create
     */
    public function createFlexPayment(array $payload): array
    {
        return $this->request('/payment/create', $payload);
    }

    /**
     * Create withdrawal request
     *
     * POST /api/v1/withdrawal/request/create
     * Requires Idempotency-Key header
     */
    public function createWithdrawal(array $payload): array
    {
        $headers = [];

        if (isset($payload['__idempotency_key'])) {
            $headers['Idempotency-Key'] = (string) $payload['__idempotency_key'];
            unset($payload['__idempotency_key']);
        }

        return $this->request('/withdrawal/request/create', $payload, $headers);
    }

    /**
     * Core HTTP client — X-API-Key header auth (no HMAC signing for outgoing)
     */
    public function request(string $path, array $body = [], array $extraHeaders = [], string $method = 'POST'): array
    {
        $baseUrl = rtrim((string) config('flashpay.api_url'), '/');
        $path = '/'.ltrim($path, '/');
        $url = $baseUrl.$path;

        $rawBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($rawBody === false) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => 'json encode failed: '.json_last_error_msg(),
                'data' => null,
                'raw' => null,
            ];
        }

        $apiKey = trim((string) config('flashpay.api_key'));
        $headers = array_merge([
            'Content-Type: application/json',
            'X-API-Key: '.$apiKey,
        ], array_map(
            fn (string $k, string $v): string => $k.': '.$v,
            array_keys($extraHeaders),
            array_values($extraHeaders),
        ));

        $requestInfo = [
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'body' => $this->maskPayload($body),
            'headers' => $extraHeaders,
        ];

        $this->apiLog('info', '[FLASHPAY] HTTP Request', $requestInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);

        if ($method === 'GET') {
            if (! empty($body)) {
                $url .= '?'.http_build_query($body);
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        } elseif ($method === 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        } else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('flashpay.timeout', 30));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) config('flashpay.connect_timeout', 15));

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->apiLog('error', '[FLASHPAY] Curl error', ['error' => $err, 'http_code' => $httpCode] + $requestInfo);

            return [
                'success' => false,
                'code' => $httpCode ?: 500,
                'msg' => $err ?: 'curl error',
                'data' => null,
                'raw' => null,
                'request' => $requestInfo,
            ];
        }

        $json = json_decode($raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300;

        $this->apiLog($ok ? 'info' : 'error', '[FLASHPAY] HTTP Response', [
            'http_code' => $httpCode,
            'success' => $ok,
            'raw' => $this->limitText($raw, 12000),
        ] + $requestInfo);

        return [
            'success' => $ok,
            'code' => $httpCode,
            'msg' => $ok
                ? 'success'
                : (is_array($json)
                    ? (string) (data_get($json, 'error.message') ?? data_get($json, 'error') ?? data_get($json, 'message') ?? 'error')
                    : 'invalid json response'),
            'data' => $json,
            'raw' => $raw,
            'request' => $requestInfo,
        ];
    }

    /**
     * Normalize provider status → internal status
     *
     * FlashPay deposit: PENDING | PAID | FAILED | EXPIRED | CANCELLED
     * FlashPay withdraw: PENDING_APPROVAL | APPROVED | REJECTED
     */
    public function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        // Deposit statuses
        if (in_array($status, ['paid', 'completed', 'complete', 'success', 'approved'], true)) {
            return 'completed';
        }

        if (in_array($status, ['cancelled', 'canceled', 'cancel'], true)) {
            return 'cancelled';
        }

        if (in_array($status, ['fail', 'failed', 'reject', 'rejected'], true)) {
            return 'failed';
        }

        if (in_array($status, ['expired', 'timeout'], true)) {
            return 'expired';
        }

        // pending, pending_approval
        return 'pending';
    }

    /**
     * Verify X-Webhook-Signature header from FlashPay callback
     *
     * HMAC-SHA256 of raw body with webhook_secret
     */
    public function verifyCallbackSignature($request, string $secretConfigKey = 'flashpay.webhook_secret'): bool
    {
        $secret = trim((string) config($secretConfigKey));
        if ($secret === '') {
            return true; // not configured → skip verification
        }

        $signature = trim((string) $request->header('X-Webhook-Signature', ''));
        if ($signature === '') {
            return false;
        }

        $rawBody = (string) $request->getContent();

        return hash_equals(
            hash_hmac('sha256', $rawBody, $secret),
            $signature
        );
    }

    private function apiLog(string $level, string $message, array $context = []): void
    {
        if (! (bool) config('flashpay.debug_log', true)) {
            return;
        }

        $channel = (string) config('flashpay.log_channel', 'flashpay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }

    private function maskPayload(array $payload): array
    {
        foreach (['api_key', 'webhook_secret'] as $key) {
            if (array_key_exists($key, $payload)) {
                $payload[$key] = $this->maskToken((string) $payload[$key]);
            }
        }

        return $payload;
    }

    private function maskToken(string $token): string
    {
        $len = strlen($token);
        if ($len <= 10) {
            return str_repeat('*', max($len, 6));
        }

        return substr($token, 0, 4).'***'.substr($token, -4);
    }

    private function limitText(string $text, int $max): string
    {
        return strlen($text) <= $max ? $text : substr($text, 0, $max).'...(truncated)';
    }
}
