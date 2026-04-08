<?php

declare(strict_types=1);

namespace Gametech\Payment\Libraries;

use Illuminate\Support\Facades\Log;

class XEPay
{
    public function request(string $path, array $data): array
    {
        $baseUrl = rtrim((string) config('xepay.api_url'), '/');
        $url = $baseUrl . '/' . ltrim($path, '/');

        $headers = [
            'Accept: application/json',
        ];

        $masked = $data;
        if (isset($masked['sign'])) {
            $masked['sign'] = $this->maskToken((string) $masked['sign'], 8, 4);
        }
        if (isset($masked['apiKey'])) {
            $masked['apiKey'] = $this->maskToken((string) $masked['apiKey'], 6, 4);
        }

        $this->apiLog('info', '[XEPAY] HTTP Request', [
            'url' => $url,
            'path' => $path,
            'payload' => $masked,
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->apiLog('error', '[XEPAY] HTTP Curl error', [
                'url' => $url,
                'error' => $err,
                'http_code' => $httpCode,
            ]);

            return [
                'success' => false,
                'code' => $httpCode ?: 500,
                'msg' => $err ?: 'curl error',
                'data' => null,
                'raw' => null,
            ];
        }

        $json = json_decode($raw, true);
        $apiSuccess = (string) data_get($json, 'Success', '') === '1';
        $message = (string) (data_get($json, 'Message') ?? data_get($json, 'message') ?? '');

        $this->apiLog(($httpCode >= 200 && $httpCode < 300) ? 'info' : 'error', '[XEPAY] HTTP Response', [
            'url' => $url,
            'http_code' => $httpCode,
            'raw' => $this->limitText($raw, 12000),
        ]);

        return [
            'success' => $apiSuccess,
            'code' => $httpCode,
            'msg' => $message,
            'data' => $json,
            'raw' => $raw,
        ];
    }

    public function createDeposit(array $payload): array
    {
        return $this->request('/pay/createOrder', $payload);
    }

    public function createWithdraw(array $payload): array
    {
        return $this->request('/payout/createOrder', $payload);
    }

    public function getBalance(): array
    {
        $payload = [
            'merNo' => (string) config('xepay.mer_no'),
            'datetime' => date('YmdHis'),
        ];
        $payload['sign'] = self::signBalance(
            $payload['merNo'],
            $payload['datetime'],
            (string) config('xepay.api_key')
        );

        return $this->request('/inquiry/getMerBalance', $payload);
    }

    public function queryDeposit(string $tradeNo): array
    {
        $payload = [
            'merNo' => (string) config('xepay.mer_no'),
            'tradeNo' => $tradeNo,
        ];
        $payload['sign'] = self::signQuery(
            $payload['merNo'],
            $payload['tradeNo'],
            (string) config('xepay.api_key')
        );

        return $this->request('/inquiry/payOrder', $payload);
    }

    public function queryWithdraw(string $tradeNo): array
    {
        $payload = [
            'merNo' => (string) config('xepay.mer_no'),
            'tradeNo' => $tradeNo,
        ];
        $payload['sign'] = self::signQuery(
            $payload['merNo'],
            $payload['tradeNo'],
            (string) config('xepay.api_key')
        );

        return $this->request('/inquiry/payoutOrder', $payload);
    }

    public static function signDeposit(string $merNo, string $tradeNo, string $orderAmount, string $apiKey): string
    {
        return md5(self::string($merNo) . self::string($tradeNo) . self::string($orderAmount) . self::string($apiKey));
    }

    public static function signWithdraw(string $merNo, string $tradeNo, string $bankCode, string $orderAmount, string $apiKey): string
    {
        return md5(self::string($merNo) . self::string($tradeNo) . self::string($bankCode) . self::string($orderAmount) . self::string($apiKey));
    }

    public static function signDepositCallback(string $tradeNo, string $topupAmount, string $apiKey): string
    {
        return md5(self::string($tradeNo) . self::string($topupAmount) . self::string($apiKey));
    }

    public static function signWithdrawCallback(string $tradeNo, string $orderAmount, string $apiKey): string
    {
        return md5(self::string($tradeNo) . self::string($orderAmount) . self::string($apiKey));
    }

    public static function signBalance(string $merNo, string $datetime, string $apiKey): string
    {
        return md5(self::string($merNo) . self::string($datetime) . self::string($apiKey));
    }

    public static function signQuery(string $merNo, string $tradeNo, string $apiKey): string
    {
        return md5(self::string($merNo) . self::string($tradeNo) . self::string($apiKey));
    }

    public function resolveBankCode($internalBankCode): string
    {
        $map = (array) config('xepay.bank_code_map', []);
        $key = (string) $internalBankCode;
        if (array_key_exists($key, $map)) {
            return trim((string) $map[$key]);
        }

        $fallback = trim((string) config('xepay.default_bank_code', ''));
        if ($fallback !== '') {
            return $fallback;
        }

        return trim((string) $internalBankCode);
    }

    private static function string($value): string
    {
        if ($value === null) {
            return '';
        }

        return (string) $value;
    }

    private function apiLog(string $level, string $message, array $context = []): void
    {
        if (!(bool) config('xepay.debug_log', true)) {
            return;
        }

        $channel = (string) config('xepay.log_channel', 'xepay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }

    private function maskToken(string $token, int $head = 6, int $tail = 4): string
    {
        $len = strlen($token);
        if ($len <= ($head + $tail + 3)) {
            return str_repeat('*', $len);
        }

        return substr($token, 0, $head) . '***' . substr($token, -$tail);
    }

    private function limitText(string $text, int $max): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max) . '...(truncated)';
    }
}
