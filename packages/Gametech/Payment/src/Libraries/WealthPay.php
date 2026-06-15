<?php

declare(strict_types=1);

namespace Gametech\Payment\Libraries;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class WealthPay
{
    public function createFlexPayment(array $payload): array
    {
        return $this->request('/payment-flex/create', $payload);
    }

    public function createWithdrawal(array $payload): array
    {
        return $this->request('/withdraw/create', $payload);
    }

    public function request(string $path, array $body = []): array
    {
        $baseUrl = rtrim((string) config('wealthpay.api_url'), '/');
        $path = '/'.ltrim($path, '/');
        $url = $baseUrl.$path;

        $body = $this->withAuthBody($body);

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

        // Sign: HMAC-SHA256(raw_body, secret_key) → X-Signature header (§3)
        $signature = $this->sign($rawBody);

        $requestInfo = [
            'method' => 'POST',
            'path' => $path,
            'url' => $url,
            'body' => $this->maskPayload($body),
        ];

        $this->apiLog('info', '[WEALTHPAY] HTTP Request', $requestInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Signature: '.$signature,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('wealthpay.timeout', 30));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) config('wealthpay.connect_timeout', 15));

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->apiLog('error', '[WEALTHPAY] Curl error', ['error' => $err, 'http_code' => $httpCode] + $requestInfo);

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

        if ($httpCode === 409) {
            $this->apiLog('warning', '[WEALTHPAY] HTTP 409 Conflict', [
                'http_code' => $httpCode,
                'raw' => $this->limitText($raw, 12000),
            ] + $requestInfo);

            return [
                'success' => false,
                'code' => 409,
                'conflict' => true,
                'msg' => is_array($json)
                    ? (string) (data_get($json, 'error.message') ?? data_get($json, 'error') ?? 'duplicate pending order')
                    : 'duplicate pending order',
                'data' => $json,
                'raw' => $raw,
                'request' => $requestInfo,
            ];
        }

        $providerSuccess = is_array($json) ? ((int) data_get($json, 'success', 0) === 200) : false;
        $ok = $httpCode >= 200 && $httpCode < 300 && $providerSuccess;

        $this->apiLog($ok ? 'info' : 'error', '[WEALTHPAY] HTTP Response', [
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
                    ? (string) (data_get($json, 'error.message') ?? data_get($json, 'message') ?? 'error')
                    : 'invalid json response'),
            'data' => $json,
            'raw' => $raw,
            'request' => $requestInfo,
        ];
    }

    public function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

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

        return 'pending';
    }

    /**
     * Verify X-Signature header from Wealthwave callback
     *
     * เอกสาร: https://doc-th.wealthwave.tech/?page=payment_callback
     *
     * @param  Request  $request
     */
    public function verifyCallbackSignature($request): bool
    {
        $secret = trim((string) config('wealthpay.secret_key'));
        if ($secret === '') {
            return true;
        }

        $signature = trim((string) $request->header('X-Signature', ''));
        if ($signature === '') {
            return false;
        }

        $rawBody = (string) $request->getContent();

        return hash_equals($this->sign($rawBody), $signature);
    }

    private function sign(string $rawBody): string
    {
        $secret = trim((string) config('wealthpay.secret_key'));
        if ($secret === '') {
            return '';
        }

        return hash_hmac('sha256', $rawBody, $secret);
    }

    private function withAuthBody(array $body): array
    {
        $merchantId = trim((string) config('wealthpay.merchant_id'));
        if ($merchantId === '') {
            throw new InvalidArgumentException('WEALTHPAY_MERCHANT_ID is required');
        }

        $token = trim((string) config('wealthpay.token'));
        if ($token === '') {
            throw new InvalidArgumentException('WEALTHPAY_TOKEN is required');
        }

        return [
            'merchant_id' => $merchantId,
            'token' => $token,
            'time' => time(),
        ] + $body;
    }

    private function apiLog(string $level, string $message, array $context = []): void
    {
        if (! (bool) config('wealthpay.debug_log', true)) {
            return;
        }

        $channel = (string) config('wealthpay.log_channel', 'wealthpay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }

    private function maskPayload(array $payload): array
    {
        foreach (['token', 'merchant_id'] as $key) {
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
