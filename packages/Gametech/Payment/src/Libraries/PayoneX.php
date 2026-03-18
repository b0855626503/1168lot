<?php

namespace Gametech\Payment\Libraries;

use Firebase\JWT\JWT;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class PayoneX
{
    /**
     * NOTE (ห้ามเปลี่ยนพฤติกรรมเดิม):
     * - ทุกเมธอด public ที่ระบบเรียกต้องอยู่ครบ
     * - โครง return ต้องสอดคล้องกับ Controller ที่ใช้งานจริง:
     *   - auth($url) -> ['success'=>bool,'token'=>string,'msg'=>string]
     *   - create/create_customer/checkDeposit -> ['success'=>bool,'data'=>array,'msg'=>string]
     * - Logging channel เดิมคงไว้
     *
     * ปรับปรุง:
     * - log ทุก request แบบ start/end + duration + payload (mask)
     * - ถ้า log channel ที่ระบุไม่มี -> fallback ไป channel "api" -> default/stack
     * - รองรับ Laravel ที่ไม่มี PendingRequest::connectTimeout() โดย fallback ไป withOptions(['connect_timeout'=>...])
     * - mockErrorResponse ต้องคืน Illuminate\Http\Client\Response เสมอ (ไม่ใช่ Promise) และ status ต้องอยู่ 1xx-5xx
     * - กันเคส response หลุดมาเป็น PromiseInterface: wait() แล้ว wrap เป็น Response
     */

    public function create($url, $data, $token)
    {
        $return = [
            'success' => false,
            'msg' => __('app.topup.fail'),
        ];

        $headers = [
            'Authorization' => $token,
        ];

        $response = $this->requestJson('payonex_deposit_create', 'เริ่มสร้างรายการฝาก', 'POST', $url, $data, $headers);

        $this->logDebug('payonex_deposit_create', 'เริ่มสร้างรายการฝาก', $response, $data);

        return $this->parseSuccessDataMessage($response, $return, __('app.topup.success'), __('app.topup.fail'));
    }

    public function create_customer($url, $data, $token)
    {
        $return = [
            'success' => false,
            'msg' => __('app.topup.fail'),
        ];

        $headers = [
            'Authorization' => $token,
        ];

        $response = $this->requestJson('payonex_deposit_create', 'เริ่ม สร้างรายการลูกค้า', 'POST', $url, $data, $headers);

        $this->logDebug('payonex_deposit_create', 'เริ่ม สร้างรายการลูกค้า', $response, $data);

        return $this->parseSuccessDataMessage($response, $return, __('app.topup.success'), __('app.topup.fail'));
    }

    public function create_withdraw($url, $data, $token)
    {
        $return = [
            'success' => false,
            'msg' => __('app.withdraw.fail'),
        ];

        $headers = [
            'Authorization' => $token,
        ];

        $response = $this->requestJson('payonex_withdraw_create', 'เริ่มสร้างรายการถอน', 'POST', $url, $data, $headers);

        $this->logDebug('payonex_withdraw_create', 'เริ่มสร้างรายการถอน', $response, $data);

        return $this->parseSuccessDataMessage($response, $return, __('app.topup.success'), __('app.topup.fail'));
    }

    public function checkDeposit($url, $param, $token)
    {
        $return = [
            'success' => false,
            'msg' => __('app.status.error'),
        ];

        $headers = [
            'Authorization' => $token,
        ];

        $response = $this->requestJson('payonex_checkDeposit', 'เชคข้อมูลการฝาก (API)', 'GET', $url, $param, $headers);

        $this->logDebug('payonex_checkDeposit', 'เชคข้อมูลการฝาก (API)', $response, $param);

        return $this->parseSuccessDataMessage($response, $return, __('app.status.success'), __('app.status.error'));
    }

    public function auth($url)
    {
        $return = [
            'success' => false,
            'msg' => __('app.status.error'),
            'token' => null,
        ];

        $data = [
            'accessKey' => config('payonex.access_key'),
            'secretKey' => config('payonex.secret_key'),
        ];

        $response = $this->requestJson('payonex_auth', 'เริ่ม Auth เพื่อรับ Token', 'POST', $url, $data, []);

        $this->logDebug('payonex_auth', 'เริ่ม Auth เพื่อรับ Token', $response, $data);

        if (!$response->successful()) {
            $json = $this->safeJson($response);
            $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: __('app.status.error'));
            return $return;
        }

        $json = $this->safeJson($response);

        $ok = (bool) ($json['success'] ?? false);
        if (!$ok) {
            $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: __('app.status.error'));
            return $return;
        }

        $token = $json['token']
            ?? data_get($json, 'data.token')
            ?? data_get($json, 'data.accessToken')
            ?? data_get($json, 'data.access_token');

        if (!$token) {
            $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: __('app.status.error'));
            return $return;
        }

        $return['success'] = true;
        $return['token'] = (string) $token;
        $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: __('app.status.success'));

        return $return;
    }

    public function create_auth($url)
    {
        $return = [
            'success' => false,
            'msg' => __('app.topup.fail'),
        ];

        $data = [
            'accessKey' => config('payonex.access_key'),
            'secretKey' => config('payonex.secret_key'),
        ];

        $response = $this->requestJson('payonex_auth', 'เริ่ม Auth เพื่อรับ Token (create_auth)', 'POST', $url, $data, []);

        $this->logDebug('payonex_auth', 'เริ่ม Auth เพื่อรับ Token (create_auth)', $response, $data);

        return $this->parseSuccessDataMessage($response, $return, __('app.topup.success'), __('app.topup.fail'));
    }

    public function JwT($time)
    {
        $merchantId = config('payonex.merchant_no');
        $clientId = config('payonex.client_id');
        $secretKey = config('payonex.secret_key');
        $timestamp = $time;

        $payload = [
            "merchantId" => $merchantId,
            "clientId" => $clientId,
            "iat" => $timestamp,
        ];

        return JWT::encode($payload, $secretKey, 'HS256');
    }

    public function generateSignature(string $partnerId, string $secret, array $body): string
    {
        $keyString = $partnerId . ':' . $this->buildKeyValueString($body);
        return hash_hmac('sha256', $keyString, $secret);
    }

    public function create_cancel($url, $data)
    {
        $parterId = config('payonex.partner_id') ?? '';

        $return = [
            'success' => false,
            'msg' => __('app.withdraw.fail'),
            'code' => 1,
        ];

        $headers = [
            'x-payonex-partnerid' => $parterId,
            'x-payonex-signature' => $this->generateSignature($parterId, config('payonex.secret_key'), $data),
        ];

        $response = $this->requestJson('payonex_cancel_create', 'เริ่มสร้างรายการยกเลิก', 'PATCH', $url, $data, $headers);

        $this->logDebug('payonex_cancel_create', 'เริ่มสร้างรายการยกเลิก', $response, $data);

        if ($response->successful()) {
            $result = $this->safeJson($response);

            if (($result['code'] ?? null) === 0) {
                $return['success'] = true;
                $return['code'] = $result['code'];
                $return['data'] = $result['data'] ?? null;
                $return['msg'] = 'Success';
            } else {
                $return['msg'] = (string) (($result['msg'] ?? null) ?: __('app.withdraw.fail'));
            }

            return $return;
        }

        $result = $this->safeJson($response);
        $return['code'] = (int) ($result['code'] ?? 1);
        $return['msg'] = (string) (($result['msg'] ?? null) ?: __('app.withdraw.fail'));

        return $return;
    }

    public function create_balance($url, $token)
    {
        $return = [
            'success' => false,
            'msg' => __('app.withdraw.fail'),
        ];

        $headers = [
            'Authorization' => $token,
        ];

        $response = $this->requestJson('payonex_balance', 'ดึงยอดคงเหลือ (API)', 'GET', $url, [], $headers);

        if ($response->successful()) {
            $result = $this->safeJson($response);

            if (($result['success'] ?? false) === true) {
                $return['success'] = true;
                $return['data'] = $result['data'] ?? null;
                $return['msg'] = (string) (($result['message'] ?? null) ?: __('app.status.success'));
            } else {
                $return['msg'] = (string) (($result['message'] ?? null) ?: __('app.status.error'));
            }

            return $return;
        }

        $result = $this->safeJson($response);
        $return['msg'] = (string) (($result['message'] ?? null) ?: __('app.status.error'));

        return $return;
    }

    public function Banks($bankcode)
    {
        switch ((string) $bankcode) {
            case '1':  $result = 'BBL'; break;
            case '2':  $result = 'KBANK'; break;
            case '3':  $result = 'KTB'; break;
            case '4':  $result = 'SCB'; break;
            case '5':  $result = 'GHB'; break;
            case '6':  $result = 'KK'; break;
            case '7':  $result = 'CIMB'; break;
            case '8':  $result = 'IBANK'; break;
            case '9':  $result = 'TISCO'; break;
            case '10':
            case '15':
            case '19': $result = 'TTB'; break;
            case '11': $result = 'BAY'; break;
            case '12': $result = 'UOBT'; break;
            case '13': $result = 'LHB'; break;
            case '14': $result = 'GSB'; break;
            case '17': $result = 'BAAC'; break;
            default:   $result = false; break;
        }

        return $result;
    }

    // =========================
    // Private helpers
    // =========================

    private function ensureLogDir(): void
    {
        $dir = storage_path('logs/payonex');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }
    }

    private function logger(string $channel): LoggerInterface
    {
        $channels = (array) config('logging.channels', []);
        $default = (string) (config('logging.default') ?: 'stack');

        if ($channel !== '' && array_key_exists($channel, $channels)) {
            try { return Log::channel($channel); } catch (Throwable) {}
        }

        if (array_key_exists('api', $channels)) {
            try { return Log::channel('api'); } catch (Throwable) {}
        }

        if ($default !== '' && array_key_exists($default, $channels)) {
            try { return Log::channel($default); } catch (Throwable) {}
        }

        try {
            return Log::getLogger();
        } catch (Throwable) {
            return new class implements LoggerInterface {
                public function emergency($message, array $context = []): void {}
                public function alert($message, array $context = []): void {}
                public function critical($message, array $context = []): void {}
                public function error($message, array $context = []): void {}
                public function warning($message, array $context = []): void {}
                public function notice($message, array $context = []): void {}
                public function info($message, array $context = []): void {}
                public function debug($message, array $context = []): void {}
                public function log($level, $message, array $context = []): void {}
            };
        }
    }

    private function requestJson(string $channel, string $label, string $method, string $url, array $data = [], array $headers = []): Response
    {
        $this->ensureLogDir();

        $requestId = $this->makeRequestId();
        $method = strtoupper(trim((string) $method));

        $t0 = microtime(true);
        $exception = null;
        $response = null;

        $this->logApiEvent($channel, 'start', [
            'request_id' => $requestId,
            'label' => $label,
            'method' => $method,
            'url' => $url,
            'headers' => $this->sanitizeHeaders($headers),
            'payload' => $this->sanitizePayload($data),
        ]);

        try {
            $client = Http::timeout(30)->acceptJson()->asJson();

            // Laravel บางเวอร์ชันไม่มี connectTimeout() -> fallback ไป Guzzle option
            if (method_exists($client, 'connectTimeout')) {
                $client = $client->connectTimeout(10);
            } else {
                $client = $client->withOptions(['connect_timeout' => 10]);
            }

            if (!empty($headers)) {
                $client = $client->withHeaders($headers);
            }

            if ($method === 'GET') {
                $response = $client->get($url, $data);
            } elseif ($method === 'POST') {
                $response = $client->post($url, $data);
            } elseif ($method === 'PATCH') {
                $response = $client->patch($url, $data);
            } else {
                $response = $client->send($method, $url, ['json' => $data]);
            }
        } catch (ConnectionException $e) {
            $exception = $e;
        } catch (RequestException $e) {
            $exception = $e;
        } catch (Throwable $e) {
            $exception = $e;
        }

        // กันเคส response หลุดมาเป็น PromiseInterface
        if ($response instanceof PromiseInterface) {
            try {
                $psr = $response->wait();
                if ($psr instanceof ResponseInterface) {
                    $response = new Response($psr);
                } else {
                    $response = null;
                }
            } catch (Throwable $e) {
                $exception = $exception ?: $e;
                $response = null;
            }
        }

        $t1 = microtime(true);
        $durationMs = (int) round(($t1 - $t0) * 1000);

        if (!$response instanceof Response) {
            $response = $this->mockErrorResponse($exception);
        }

        $this->logApiEvent($channel, 'end', [
            'request_id' => $requestId,
            'label' => $label,
            'method' => $method,
            'url' => $url,
            'duration_ms' => $durationMs,
            'status' => $response->status(),
            'ok' => $response->successful(),
            'failed' => $response->failed(),
            'body' => $this->truncateString((string) $response->body(), 4000),
            'json' => $this->safeJson($response),
            'exception' => $exception ? [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
            ] : null,
        ]);

        return $response;
    }

    private function mockErrorResponse(?Throwable $e): Response
    {
        $payload = [
            'success' => false,
            'message' => $e ? (string) $e->getMessage() : 'Request failed',
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($body === false) {
            $body = '{"success":false,"message":"Request failed"}';
        }

        // ✅ status ต้องอยู่ในช่วง 1xx-5xx
        $psr = new Psr7Response(500, ['Content-Type' => 'application/json'], $body);

        return new Response($psr);
    }

    private function logApiEvent(string $channel, string $phase, array $context): void
    {
        try {
            $this->logger($channel)->info("[PayoneX][{$phase}]", $context);
        } catch (Throwable) {
            try { Log::info("[PayoneX][{$phase}]", $context); } catch (Throwable) {}
        }
    }

    private function logDebug(string $channel, string $message, Response $response, array $param = []): void
    {
        $this->ensureLogDir();

        $debug = [
            'json' => $this->safeJson($response),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
            'param' => $this->sanitizePayload($param),
            'body' => $this->truncateString((string) $response->body(), 4000),
        ];

        try {
            $this->logger($channel)->info($message, ['debug' => $debug]);
        } catch (Throwable) {
            try { Log::info($message, ['debug' => $debug]); } catch (Throwable) {}
        }
    }

    private function parseSuccessDataMessage(Response $response, array $return, string $defaultSuccessMsg, string $defaultFailMsg): array
    {
        if (!$response->successful()) {
            $json = $this->safeJson($response);
            $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: $defaultFailMsg);
            return $return;
        }

        $json = $this->safeJson($response);

        if (($json['success'] ?? false) === true) {
            $return['success'] = true;
            $return['data'] = $json['data'] ?? null;
            $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: $defaultSuccessMsg);
            return $return;
        }

        $return['msg'] = (string) (($json['message'] ?? $json['msg'] ?? null) ?: $defaultFailMsg);
        return $return;
    }

    private function safeJson(Response $response): array
    {
        try {
            $json = $response->json();
            return is_array($json) ? $json : (array) $json;
        } catch (Throwable) {
            return [];
        }
    }

    private function makeRequestId(): string
    {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable) {
            return (string) mt_rand(10000000, 99999999);
        }
    }

    private function sanitizeHeaders(array $headers): array
    {
        $out = [];

        foreach ($headers as $k => $v) {
            $key = (string) $k;
            $val = is_scalar($v) ? (string) $v : '[non-scalar]';

            $lower = strtolower($key);
            if ($lower === 'authorization') {
                $out[$key] = $this->maskToken($val);
                continue;
            }

            if (str_contains($lower, 'signature') || str_contains($lower, 'secret') || str_contains($lower, 'token')) {
                $out[$key] = '[masked]';
                continue;
            }

            $out[$key] = $this->truncateString($val, 500);
        }

        return $out;
    }

    private function sanitizePayload(array $payload): array
    {
        $maskedKeys = [
            'password','pass','secret','secretKey','secret_key',
            'accessKey','access_key','token','accessToken','access_token',
            'authorization','signature',
        ];

        $out = [];
        foreach ($payload as $k => $v) {
            $key = (string) $k;
            $lower = strtolower($key);

            $shouldMask = in_array($key, $maskedKeys, true) || in_array($lower, array_map('strtolower', $maskedKeys), true);

            if ($shouldMask) {
                $out[$key] = '[masked]';
                continue;
            }

            if (is_string($v)) {
                $out[$key] = $this->truncateString($v, 2000);
            } elseif (is_scalar($v) || $v === null) {
                $out[$key] = $v;
            } elseif (is_array($v)) {
                $out[$key] = $this->truncateArray($v, 2, 80);
            } else {
                $out[$key] = '[non-serializable]';
            }
        }

        return $out;
    }

    private function truncateArray(array $arr, int $depth, int $maxItems): array
    {
        if ($depth <= 0) return ['_truncated' => true];

        $out = [];
        $i = 0;

        foreach ($arr as $k => $v) {
            $i++;
            if ($i > $maxItems) { $out['_more'] = true; break; }

            if (is_array($v)) {
                $out[$k] = $this->truncateArray($v, $depth - 1, $maxItems);
            } elseif (is_string($v)) {
                $out[$k] = $this->truncateString($v, 500);
            } elseif (is_scalar($v) || $v === null) {
                $out[$k] = $v;
            } else {
                $out[$k] = '[non-serializable]';
            }
        }

        return $out;
    }

    private function truncateString(string $s, int $max): string
    {
        if (mb_strlen($s) <= $max) return $s;
        return mb_substr($s, 0, $max) . '...';
    }

    private function maskToken(string $token): string
    {
        $t = trim($token);
        if ($t === '') return '';

        $parts = preg_split('/\s+/', $t);
        if (count($parts) >= 2 && strtolower($parts[0]) === 'bearer') {
            return 'Bearer ' . $this->maskToken($parts[1]);
        }

        $len = strlen($t);
        if ($len <= 12) return str_repeat('*', $len);

        return substr($t, 0, 6) . str_repeat('*', max(0, $len - 10)) . substr($t, -4);
    }

    private function buildKeyValueString(array $obj): string
    {
        $flat = $this->flattenObject($obj);
        ksort($flat);

        $pairs = [];
        foreach ($flat as $key => $value) {
            if (is_bool($value)) $value = $value ? 'true' : 'false';
            $pairs[] = "{$key}={$value}";
        }

        return implode('&', $pairs);
    }

    private function flattenObject(array $obj, string $prefix = ''): array
    {
        $result = [];

        foreach ($obj as $key => $value) {
            $fullKey = $prefix !== '' ? $prefix . '.' . $key : $key;

            if (is_array($value) && array_values($value) !== $value) {
                $result = array_merge($result, $this->flattenObject($value, $fullKey));
            } else {
                $result[$fullKey] = $value;
            }
        }

        return $result;
    }
}
