<?php

declare(strict_types=1);

namespace Gametech\Payment\Libraries;

use Exception;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class SmkPay
{
    /**
     * Core HTTP request
     * - JSON body canonicalize ด้วย JCS (RFC8785) ก่อน sign และส่งจริง
     * - Canonical string: METHOD\nPATH\nTIMESTAMP\nJCS_BODY
     */
    public function request(string $method, string $path, ?array $body = null, array $query = []): array
    {
        $baseUrl = rtrim((string) config('smkpay.api_url'), '/');
        $path = '/' . ltrim($path, '/');

        $url = $baseUrl . $path;
        if (!empty($query)) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }

        $timestamp = time();
        $apiKey = (string) config('smkpay.api_key');
        $secretKey = (string) config('smkpay.secret_key');

        $jcsBody = '';
        if ($body !== null) {
            $jcsBody = self::canonicalizeJSON($body);
        }

        $signature = self::generateSignature([
            'secret' => $secretKey,
            'method' => strtoupper(trim($method)),
            'path' => $path,
            'timestamp' => $timestamp,
            'jcs_body' => $jcsBody,
        ]);

        $headers = [
            'Content-Type: application/json',
            'X-Api-Key: ' . $apiKey,
            'X-Timestamp: ' . (string) $timestamp,
            'X-Signature: ' . $signature,
        ];

        $requestInfo = [
            'method' => strtoupper(trim($method)),
            'path' => $path,
            'url' => $url,
            'timestamp' => $timestamp,
            'has_body' => $body !== null,
            'headers' => $this->maskHeaders($headers),
            'body' => $jcsBody,
        ];

        $this->apiLog('info', '[SMKPAY] HTTP Request', $requestInfo);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper(trim($method)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jcsBody);
        }

        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $this->apiLog('error', '[SMKPAY] HTTP Curl error', [
                    'error' => $err,
                    'http_code' => $httpCode,
                ] + $requestInfo);

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

        $respInfo = [
            'http_code' => $httpCode,
            'success' => $ok,
            'raw' => $this->limitText($raw, 12000),
        ];

        $this->apiLog($ok ? 'info' : 'error', '[SMKPAY] HTTP Response', $respInfo + $requestInfo);

        return [
            'success' => $ok,
            'code' => $httpCode,
            'msg' => $ok
                ? 'success'
                : (is_array($json)
                    ? ((string) (data_get($json, 'message') ?? data_get($json, 'msg') ?? data_get($json, 'error.message') ?? 'error'))
                    : 'error'),
            'data' => $json,
            'raw' => $raw,
            'request' => $requestInfo,
        ];
    }

    // ===== API wrappers (ตาม Prepare) =====

    public function getCustomer(?string $email, ?string $phoneNumber): array
    {
        $query = [];
        if (!empty($email)) {
            $query['email'] = $email;
        }
        if (!empty($phoneNumber)) {
            $query['phone_number'] = $phoneNumber;
        }

        return $this->request('GET', '/v1/customers', null, $query);
    }

    public function createCustomer(array $payload): array
    {
        return $this->request('POST', '/v1/customers', $payload);
    }

    public function getCustomerAccount(string $accountIdentifier, string $accountPlatform, string $currencyCode = 'THB'): array
    {
        return $this->request('GET', '/v1/customers/accounts', null, [
            'account_identifier' => $accountIdentifier,
            'account_platform' => $accountPlatform,
            'currency_code' => $currencyCode,
        ]);
    }

    public function createCustomerAccount(string $customerId, array $payload): array
    {
        return $this->request('POST', '/v1/customers/' . $customerId . '/accounts', $payload);
    }

    /**
     * PATCH Update customer (มีใน Prepare)
     */
    public function updateCustomer(string $customerId, array $payload): array
    {
        return $this->request('PATCH', '/v1/customers/' . $customerId, $payload);
    }

    /**
     * PATCH Update account (มีใน Prepare)
     */
    public function updateCustomerAccount(string $customerId, string $accountId, array $payload): array
    {
        return $this->request('PATCH', '/v1/customers/' . $customerId . '/accounts/' . $accountId, $payload);
    }

    /**
     * Create payin (ตาม Prepare)
     */
    public function createPayin(array $payload): array
    {
        return $this->request('POST', '/v1/payins', $payload);
    }

    /**
     * Get limits (min/max deposit/withdraw) per currency
     * GET /v1/me/limits?currency_code[]=THB
     *
     * @param array<int, string> $currencyCodes
     */
    public function getLimits(array $currencyCodes = ['THB']): array
    {
        return $this->request('GET', '/v1/me/limits', null, [
            'currency_code' => $currencyCodes,
        ]);
    }

    // ===== Logging helpers =====

    private function apiLog(string $level, string $message, array $context = []): void
    {
        $enabled = (bool) config('smkpay.debug_log', true);
        if (!$enabled) {
            return;
        }

        $channel = (string) config('smkpay.log_channel', 'smkpay_api');

        try {
            Log::channel($channel)->{$level}($message, $context);
        } catch (\Throwable $e) {
            Log::{$level}($message, $context);
        }
    }

    private function maskHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $h) {
            $out[] = $this->maskHeaderLine($h);
        }
        return $out;
    }

    private function maskHeaderLine(string $line): string
    {
        if (!str_contains($line, ':')) {
            return $line;
        }

        [$k, $v] = array_map('trim', explode(':', $line, 2));
        $kl = strtolower($k);

        if ($kl === 'x-api-key') {
            return $k . ': ' . $this->maskToken($v, 6, 4);
        }

        if ($kl === 'x-signature') {
            return $k . ': ' . $this->maskToken($v, 8, 6);
        }

        return $line;
    }

    private function maskToken(string $token, int $head, int $tail): string
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

    // ===== Signature (JCS / RFC8785) =====

    public static function generateSignature(array $param): string
    {
        self::validateSignatureParam($param);

        $canonical = implode("\n", [
            strtoupper(trim((string) $param['method'])),
            trim((string) $param['path']),
            (string) $param['timestamp'],
            (string) ($param['jcs_body'] ?? ''),
        ]);

        return hash_hmac('sha256', $canonical, (string) $param['secret']);
    }

    public static function validateSignatureParam(array $param): void
    {
        if (empty(trim((string) ($param['secret'] ?? '')))) {
            throw new InvalidArgumentException('secret key is required');
        }
        if (empty(trim((string) ($param['method'] ?? '')))) {
            throw new InvalidArgumentException('HTTP method is required');
        }
        if (empty(trim((string) ($param['path'] ?? '')))) {
            throw new InvalidArgumentException('API path is required');
        }
        if (!isset($param['timestamp']) || (int) $param['timestamp'] <= 0) {
            throw new InvalidArgumentException('timestamp must be positive');
        }
        if (!array_key_exists('jcs_body', $param)) {
            throw new InvalidArgumentException('jcs_body is required (can be empty string)');
        }
    }

    // ===== JSON Canonicalization Scheme (RFC 8785) =====

    public static function canonicalizeJSON($obj): string
    {
        if ($obj === null) {
            return '';
        }

        $jsonString = json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($jsonString === false) {
            throw new Exception('Failed to encode JSON: ' . json_last_error_msg());
        }

        $parsed = json_decode($jsonString, true);
        if ($parsed === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to parse JSON: ' . json_last_error_msg());
        }

        return self::canonicalizeValue($parsed);
    }

    public static function canonicalizeValue($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            if (is_infinite($value) || is_nan($value)) {
                throw new Exception('Infinite and NaN values are not allowed in JSON');
            }

            if (is_int($value) || floor($value) == $value) {
                return (string) (int) $value;
            }

            $encoded = json_encode($value);
            if ($encoded === false) {
                throw new Exception('Failed to encode number');
            }
            return $encoded;
        }

        if (is_string($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE);
            if ($encoded === false) {
                throw new Exception('Failed to encode string');
            }
            return $encoded;
        }

        if (is_array($value)) {
            if (array_keys($value) === range(0, count($value) - 1)) {
                $items = array_map([self::class, 'canonicalizeValue'], $value);
                return '[' . implode(',', $items) . ']';
            }

            return self::canonicalizeObject($value);
        }

        if (is_object($value)) {
            $array = json_decode(json_encode($value), true);
            return self::canonicalizeObject($array);
        }

        throw new Exception('Unsupported value type: ' . gettype($value));
    }

    public static function canonicalizeObject($obj): string
    {
        if (!is_array($obj)) {
            throw new Exception('Expected associative array for object canonicalization');
        }

        ksort($obj, SORT_STRING);

        $pairs = [];
        foreach ($obj as $key => $value) {
            $canonicalKey = self::canonicalizeValue((string) $key);
            $canonicalValue = self::canonicalizeValue($value);
            $pairs[] = $canonicalKey . ':' . $canonicalValue;
        }

        return '{' . implode(',', $pairs) . '}';
    }
}
