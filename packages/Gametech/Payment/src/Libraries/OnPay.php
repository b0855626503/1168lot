<?php

namespace Gametech\Payment\Libraries;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class OnPay
{
    public function create($url, $data)
    {
        $parterId = config('onpay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.topup.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
                ->withHeaders([
                        'x-api-key' => config('onpay.secret_key'),
                ])
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

        if (! File::exists(storage_path('logs/onpay'))) {
            File::makeDirectory(storage_path('logs/onpay'));
        }
        Log::channel('onpay_deposit_create')->info('เริ่มสร้างรายการฝาก', [
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
        $merchantId = config('onpay.merchant_no');
        $clientId = config('onpay.client_id');
        $secretKey = config('onpay.secret_key');
        $timestamp = $time;
        $payload = [
                'merchantId' => $merchantId,
                'clientId' => $clientId,
                'iat' => $timestamp,
        ];
        $jwt = JWT::encode($payload, $secretKey, 'HS256');

        return $jwt;
    }

    public function create_withdraw($url, $data)
    {
        $parterId = config('onpay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
                ->withHeaders([
                        'x-api-key' => config('onpay.secret_key'),
                ])
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

        if (! File::exists(storage_path('logs/onpay'))) {
            File::makeDirectory(storage_path('logs/onpay'));
        }
        Log::channel('onpay_withdraw_create')->info('เริ่มสร้างรายการถอน', [
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
        $keyString = $partnerId.':'.$this->buildKeyValueString($body);

        // นำ signature ที่ได้ไปใส่ใน x-onpay-signature
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
            $fullKey = $prefix !== '' ? $prefix.'.'.$key : $key;

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
        $parterId = config('onpay.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
                ->withHeaders([
                        'x-onpay-partnerid' => $parterId,
                        'x-onpay-signature' => $this->generateSignature($parterId, config('onpay.secret_key'), $data),
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

        if (! File::exists(storage_path('logs/onpay'))) {
            File::makeDirectory(storage_path('logs/onpay'));
        }
        Log::channel('onpay_cancel_create')->info('เริ่มสร้างรายการยกเลิก', [
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
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        // retry 3 ครั้ง แบบ backoff (2s/5s/10s)
        $sleepsMs = [2000, 5000, 10000];

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = Http::timeout(30)
                        ->withOptions([
                            // สำคัญ: รองรับ Laravel เก่าที่ไม่มี connectTimeout()
                                'connect_timeout' => 10,

                            // กัน redirect loop: ไม่ให้ตาม redirect อัตโนมัติ
                                'allow_redirects' => false,
                        ])
                        ->withHeaders([
                                'x-api-key' => config('onpay.secret_key'),
                                'Accept' => 'application/json',
                        ])
                        ->asJson()
                        ->get($url, $data);

                // ===== ดัก redirect ให้รู้ว่าโดนพาไปไหน =====
                if (in_array($response->status(), [301, 302, 307, 308], true)) {
                    $location = (string) $response->header('Location');
                    Log::warning('OnPay balance redirect detected', [
                            'attempt' => $attempt,
                            'url' => $url,
                            'status' => $response->status(),
                            'location' => $location,
                            'content_type' => (string) $response->header('Content-Type'),
                            'body_snippet' => mb_substr((string) $response->body(), 0, 200),
                    ]);

                    $return['msg'] = "Redirect {$response->status()} ไปยัง {$location}";
                    $return['code'] = $response->status();

                } elseif ($response->successful()) {

                    $result = $response->json();

                    if (($result['success'] ?? false) === true) {
                        $return['success'] = true;
                        $return['data'] = $result['data'] ?? [];
                        $return['msg'] = $result['message'] ?? __('app.topup.success');
                        $return['code'] = 0;

                        return $return;
                    }

                    // ได้ 200 แต่ payload ไม่ success
                    $return['msg'] = $result['message'] ?? __('app.topup.fail');
                    $return['code'] = $response->status();

                } else {
                    // ไม่ successful
                    $result = $response->json();
                    $return['msg'] = $result['message'] ?? __('app.status.error');
                    $return['code'] = $response->status();

                    Log::warning('OnPay balance request failed', [
                            'attempt' => $attempt,
                            'url' => $url,
                            'status' => $response->status(),
                            'content_type' => (string) $response->header('Content-Type'),
                            'body_snippet' => mb_substr((string) $response->body(), 0, 200),
                    ]);
                }
            } catch (Throwable $e) {
                // timeout / connection / dns / ฯลฯ
                $return['msg'] = $e->getMessage();
                $return['code'] = 1;

                Log::warning('OnPay balance exception', [
                        'attempt' => $attempt,
                        'url' => $url,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                ]);
            }

            // ===== backoff ก่อนลองใหม่ =====
            if ($attempt < 3) {
                usleep($sleepsMs[$attempt - 1] * 1000);
            }
        }

        return $return;
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
            case '5':
                $result = '033';
                break;
            case '6':
                $result = '069';
                break;
            case '7':
                $result = '022';
                break;
            case '8':
                $result = '066';
                break;
            case '9':
                $result = '067';
                break;
            case '10':
            case '19':
                $result = '011';
                break;
            case '15':
                $result = '065';
                break;
            case '11':
                $result = '025';
                break;
            case '12':
                $result = '024';
                break;
            case '13':
                $result = '073';
                break;
            case '14':
                $result = '030';
                break;
            case '17':
                $result = '034';
                break;
            default:
                $result = false;
                break;
        }

        return $result;

    }
}
