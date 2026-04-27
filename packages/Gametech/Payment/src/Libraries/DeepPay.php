<?php

declare(strict_types=1);

namespace Gametech\Payment\Libraries;

use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DeepPay
{
    public function request(string $path, array $body = [], bool $withAuth = true): array
    {
        $baseUrl = rtrim((string) config('deeppay.api_url'), '/');
        $path = '/' . ltrim($path, '/');
        $url = $baseUrl . $path;

        if ($withAuth) {
            $body = $this->withAuthBody($body);
        }

        $rawBody = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($rawBody === false) {
            return [
                'success' => false,
                'code' => 500,
                'msg' => 'json encode failed: ' . json_last_error_msg(),
                'data' => null,
                'raw' => null,
            ];
        }

        $requestInfo = [
            'method' => 'POST',
            'path' => $path,
            'url' => $url,
            'body' => $this->maskPayload($body),
        ];

        $this->apiLog('info', '[DEEPPAY] HTTP Request', $requestInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int) config('deeppay.timeout', 30));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, (int) config('deeppay.connect_timeout', 15));

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->apiLog('error', '[DEEPPAY] Curl error', ['error' => $err, 'http_code' => $httpCode] + $requestInfo);

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
        $providerSuccess = is_array($json) ? (bool) data_get($json, 'success', false) : false;
        $ok = $httpCode >= 200 && $httpCode < 300 && $providerSuccess;

        $this->apiLog($ok ? 'info' : 'error', '[DEEPPAY] HTTP Response', [
                'http_code' => $httpCode,
                'success' => $ok,
                'raw' => $this->limitText($raw, 12000),
            ] + $requestInfo);

        return [
            'success' => $ok,
            'code' => $httpCode,
            'msg' => $ok ? 'success' : (is_array($json) ? (string) data_get($json, 'message', 'error') : 'invalid json response'),
            'data' => $json,
            'raw' => $raw,
            'request' => $requestInfo,
        ];
    }

    public function auth(bool $forceRefresh = false): array
    {
        /*
         * DeepPay auth code is one-time-use.
         *
         * Keep $forceRefresh in the method signature for backward compatibility,
         * but intentionally do not cache/reuse auth tokens.
         */
        $username = $this->username();

        $token = base64_encode($username . ':' . $this->apiKey());
        $resp = $this->request('/auth', [
            'username' => $username,
            'token' => $token,
        ], false);

        if (!data_get($resp, 'success')) {
            return ['success' => false, 'auth' => null, 'resp' => $resp, 'msg' => data_get($resp, 'msg', 'auth failed')];
        }

        $auth = (string) data_get($resp, 'data.data.auth', '');
        if ($auth === '') {
            return ['success' => false, 'auth' => null, 'resp' => $resp, 'msg' => 'auth token missing'];
        }

        return ['success' => true, 'auth' => $auth, 'cached' => false];
    }

    public function balance(?string $currency = null): array
    {
        return $this->request('/balance', [
            'currency' => $currency ?: $this->currency(),
        ]);
    }

    public function amountList(array $payload): array
    {
        return $this->request('/amount_list', $payload + [
                'lang' => (string) config('deeppay.lang', 'th'),
            ]);
    }

    public function deposit(array $payload): array
    {
        return $this->request('/deposit', $payload + [
                'lang' => (string) config('deeppay.lang', 'th'),
            ]);
    }

    public function withdraw(array $payload): array
    {
        return $this->request('/withdraw', $payload + [
                'lang' => (string) config('deeppay.lang', 'th'),
            ]);
    }

    public function depositTransaction(?string $txnNo, ?string $orderId): array
    {
        return $this->request('/deposit_transaction', array_filter([
            'txn_no' => $txnNo,
            'order_id' => $orderId,
            'lang' => (string) config('deeppay.lang', 'th'),
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function withdrawTransaction(?string $txnNo, ?string $orderId): array
    {
        return $this->request('/withdraw_transaction', array_filter([
            'txn_no' => $txnNo,
            'order_id' => $orderId,
            'lang' => (string) config('deeppay.lang', 'th'),
        ], fn ($v) => $v !== null && $v !== ''));
    }

    public function hashOrderId(string $orderId): string
    {
        $secret = (string) config('deeppay.secret_key');
        if (trim($secret) === '') {
            throw new InvalidArgumentException('DEEPPAY_SECRET_KEY is required');
        }

        return md5($orderId . $secret);
    }

    public function normalizeStatus(?string $status): string
    {
        $status = strtolower(trim((string) $status));

        if (in_array($status, ['completed', 'complete', 'success', 'approved'], true)) {
            return 'completed';
        }

        if (in_array($status, ['reject', 'rejected', 'failed', 'fail', 'cancel', 'cancelled'], true)) {
            return 'failed';
        }

        if (in_array($status, ['expired', 'timeout'], true)) {
            return 'expired';
        }

        return 'pending';
    }

    private function withAuthBody(array $body): array
    {
        $auth = $this->auth(true);
        if (!data_get($auth, 'success')) {
            return $body + [
                    'auth' => '',
                    'username' => $this->username(),
                ];
        }

        return $body + [
                'auth' => (string) data_get($auth, 'auth'),
                'username' => $this->username(),
            ];
    }

    private function username(): string
    {
        $username = trim((string) config('deeppay.username'));
        if ($username === '') {
            throw new InvalidArgumentException('DEEPPAY_USERNAME is required');
        }
        return $username;
    }

    private function apiKey(): string
    {
        $apiKey = trim((string) config('deeppay.api_key'));
        if ($apiKey === '') {
            throw new InvalidArgumentException('DEEPPAY_API_KEY is required');
        }
        return $apiKey;
    }

    private function currency(): string
    {
        return (string) config('deeppay.currency', 'THB');
    }

    private function apiLog(string $level, string $message, array $context = []): void
    {
        if (!(bool) config('deeppay.debug_log', true)) {
            return;
        }

        $channel = (string) config('deeppay.log_channel', 'deeppay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }

    private function maskPayload(array $payload): array
    {
        foreach (['auth', 'token', 'hash', 'pin_code'] as $key) {
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
        return substr($token, 0, 4) . '***' . substr($token, -4);
    }

    private function limitText(string $text, int $max): string
    {
        return strlen($text) <= $max ? $text : substr($text, 0, $max) . '...(truncated)';
    }
}
