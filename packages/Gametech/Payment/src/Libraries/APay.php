<?php

namespace Gametech\Payment\Libraries;

use Firebase\JWT\JWT;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class APay
{
    public function create($url, $data)
    {
        $parterId = config('apay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.topup.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
            ->asJson()->post($url, $data);

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
            'param' => $data,
        ];

        if (!File::exists(storage_path('logs/apay'))) {
            File::makeDirectory(storage_path('logs/apay'));
        }
        Log::channel('apay_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => $debug,
        ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['success'] === true) {
                $return['success'] = true;
                $return['data'] = $result['data'];
                $return['msg'] = $result['message'] ?? __('app.topup.success');
            } else {
                $return['msg'] = $result['message'] ?? __('app.topup.fail');
            }

        } else {
            $result = $response->json();
            $return['msg'] = $result['message'] ?? __('app.topup.fail');
        }

        return $return;
    }

    public function JwT($time)
    {
        $merchantId = config('apay.merchant_no');
        $clientId = config('apay.client_id');
        $secretKey = config('apay.secret_key');
        $timestamp = $time;
        $payload = array(
            "merchantId" => $merchantId,
            "clientId" => $clientId,
            "iat" => $timestamp
        );
        $jwt = JWT::encode($payload, $secretKey, 'HS256');
        return $jwt;
    }

    public function create_withdraw($url, $data)
    {
        $parterId = config('apay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)->asJson()->post($url, $data);

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
            'param' => $data,
        ];

        if (!File::exists(storage_path('logs/apay'))) {
            File::makeDirectory(storage_path('logs/apay'));
        }
        Log::channel('apay_withdraw_create')->info('เริ่มสร้างรายการถอน', [
            'debug' => $debug,
        ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['success'] === true) {
                $return['success'] = true;
                $return['data'] = $result['data'];
                $return['msg'] = $result['message'] ?? __('app.topup.success');
            } else {
                $return['msg'] = $result['message'] ?? __('app.topup.fail');
            }

        } else {
            $result = $response->json();
            $return['msg'] = $result['message'] ?? __('app.topup.fail');
        }

        return $return;
    }

    public function generateSignature(string $partnerId, string $secret, array $body): string
    {
        $keyString = $partnerId . ':' . $this->buildKeyValueString($body);

        // นำ signature ที่ได้ไปใส่ใน x-apay-signature
        return hash_hmac('sha256', $keyString, $secret);
    }

    private function buildKeyValueString(array $obj): string
    {
        $flat = $this->flattenObject($obj);
        ksort($flat); // sort keys alphabetically

        $keyValuePairs = [];
        foreach ($flat as $key => $value) {
            // handle boolean to string
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $keyValuePairs[] = "{$key}={$value}";
        }

        return implode('&', $keyValuePairs);
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

    public function create_cancel($url, $data)
    {
        $parterId = config('apay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
            ->withHeaders([
                'x-apay-partnerid' => $parterId,
                'x-apay-signature' => $this->generateSignature($parterId, config('apay.secret_key'), $data),
            ])
            ->asJson()->patch($url, $data);

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
            'param' => $data,
        ];

        if (!File::exists(storage_path('logs/apay'))) {
            File::makeDirectory(storage_path('logs/apay'));
        }
        Log::channel('apay_cancel_create')->info('เริ่มสร้างรายการยกเลิก', [
            'debug' => $debug,
        ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['code'] === 0) {
                $return['success'] = true;
                $return['code'] = $result['code'];
                $return['data'] = $result['data'];
                $return['msg'] = 'Success';
            } else {
                $return['msg'] = $result['msg'] ?? __('app.withdraw.fail');
            }

        } else {
            $result = $response->json();
            $return['code'] = $result['code'] ?? 1;
            $return['msg'] = $result['msg'] ?? __('app.withdraw.fail');
        }

        return $return;
    }

    public function create_balance($url, $data)
    {
        $parterId = config('apay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)->asJson()->post($url, $data);

                $debug = [
                    'json' => $response->json(),
                    'success' => $response->successful(),
                    'fail' => $response->failed(),
                    'status' => $response->status(),
                    'serverError' => $response->serverError(),
                    'clientError' => $response->clientError(),
                    'date' => now()->toDateTimeString(),
                    'param' => $data,
                ];

        //        if (! File::exists(storage_path('logs/apay'))) {
        //            File::makeDirectory(storage_path('logs/apay'));
        //        }
                Log::channel('apay_deposit_create')->info('เริ่มสร้างรายการยกเลิก', [
                    'balance' => $debug,
                ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['success'] === true) {
                $return['success'] = true;
                $return['data'] = $result['data'];
                $return['msg'] = $result['message'] ?? __('app.status.success');
            } else {
                $return['msg'] = $result['message'] ?? __('app.status.error');
            }

        } else {
            $result = $response->json();
            $return['msg'] = $result['message'] ?? __('app.status.error');
        }

        return $return;
    }

    public function create_auth($url, $data)
    {
        $return = ['success' => false, 'code' => 1, 'msg' => __('app.topup.fail')];

        $response = Http::timeout(10)
            ->retry(2, 250) // retry 2 ครั้ง เว้น 250ms
            ->asJson()
            ->post($url, $data);

        // (เหมือนเดิม) … แต่อย่า log token แบบดิบ ๆ
        // แนะนำ mask ค่าที่เป็นความลับก่อนเก็บ log

        if ($response->successful()) {
            $result = $response->json();
            if (!empty($result['success'])) {
                $return['success'] = true;
                $return['data']    = $result['data'] ?? [];
                $return['msg']     = $result['message'] ?? __('app.topup.success');
            } else {
                $return['msg'] = $result['message'] ?? __('app.topup.fail');
            }
        } else {
            $result = $response->json();
            $return['msg'] = $result['message'] ?? __('app.topup.fail');
        }

        return $return;
    }


    public function auth(): ?string
    {
        $cacheKey  = 'apay:token';
        $lockKey   = 'apay:token:lock';
        $graceSec  = 20; // เผื่อก่อนหมดอายุ

        // 1) ถ้ามี cache และยังไม่ใกล้หมด → ใช้เลย
        if ($cached = Cache::get($cacheKey)) {
            if (!empty($cached['token']) && !empty($cached['exp_ts'])) {
                if (now()->lt(Carbon::createFromTimestamp($cached['exp_ts'])->subSeconds($graceSec))) {
                    return $cached['token'];
                }
            }
        }

        // 2) Single-flight refresh
        $lock = Cache::lock($lockKey, 10);
        try {
            $lock->block(5); // รอคิวได้สูงสุด 5 วิ

            // ตรวจซ้ำหลังได้ lock เผื่อคนอื่นเพิ่งใส่ cache สำเร็จ
            if ($cached = Cache::get($cacheKey)) {
                if (!empty($cached['token']) && !empty($cached['exp_ts'])) {
                    if (now()->lt(Carbon::createFromTimestamp($cached['exp_ts'])->subSeconds($graceSec))) {
                        return $cached['token'];
                    }
                }
            }

            // 3) ยิงขอ token ใหม่
            $param = [
                'username' => config('apay.username'),
                'token'    => base64_encode(config('apay.username').':'.config('apay.api_key')),
            ];
            $url = rtrim(config('apay.api_url'), '/') . '/auth';

            $res = $this->create_auth($url, $param);
            if (!$res['success']) {
                // ห้าม recursion — ให้โยนออกไปให้ caller ตัดสินใจ retry ตามเหมาะสม
                Log::warning('APay auth failed', ['reason' => $res['msg'] ?? 'unknown']);
                return null;
            }

            $data  = $res['data'] ?? [];
            $token = $data['auth'] ?? $data['token'] ?? null;

            // ===== คำนวณวันหมดอายุ =====
            // รองรับหลาย format จากปลายทาง:
            // - expires_in (วินาที), หรือ
            // - expire_at/expires_at (timestamp หรือ datetime string), หรือ
            // - timestamp (ถ้าเป็น "เวลาปัจจุบันของเซิร์ฟเวอร์" → บวก 300s)
            $nowTs = now()->timestamp;

            if (isset($data['expires_in'])) {
                $expTs = $nowTs + (int) $data['expires_in'];
            } elseif (isset($data['expire_at']) || isset($data['expires_at'])) {
                $raw   = $data['expire_at'] ?? $data['expires_at'];
                $expTs = is_numeric($raw) ? (int) $raw : Carbon::parse($raw)->timestamp;
            } elseif (isset($data['timestamp'])) {
                // สมมติว่า timestamp นี้คือ "ตอนนี้" ของฝั่งเขา → อายุ 5 นาที
                $expTs = ((int) $data['timestamp'] + 300);
            } else {
                // ไม่บอกอะไรมาเลย → สมมติ 5 นาที
                $expTs = $nowTs + 300;
            }

            // ตั้ง TTL ใน Cache โดยหัก grace ออก
            $ttl = max(1, $expTs - $nowTs - $graceSec);
            Cache::put($cacheKey, ['token' => $token, 'exp_ts' => $expTs], now()->addSeconds($ttl));

            return $token;
        } finally {
            optional($lock)->release();
        }
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = '002';
                break;
            case '2':
                $result = '004';
                break;
            case '3':
                $result = '006';
                break;
            case '4':
                $result = '014';
                break;
            case '6':
                $result = '069';
                break;
            case '7':
                $result = '022';
                break;
//            case '8':
//                $result = 'IBANK';
//                break;
//            case '9':
//                $result = 'TISCO';
//                break;
            case '10':
            case '15':
            case '19':
                $result = '011';
                break;
            case '11':
                $result = '025';
                break;
            case '12':
                $result = '024';
                break;
            case '14':
                $result = '030';
                break;
            case '17':
                $result = '034';
                break;
            case '18':
                $result = '000';
                break;

            default:
                $result = false;
                break;
        }

        return $result;

    }
}
