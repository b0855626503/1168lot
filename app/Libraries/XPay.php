<?php

namespace App\Libraries;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class XPay
{
    public function create($url, $data)
    {
        $parterId = config('payment.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.topup.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)->asForm()->post($url, $data);

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

        if (! File::exists(storage_path('logs/xpay'))) {
            File::makeDirectory(storage_path('logs/xpay'));
        }
        Log::channel('xpay_deposit_create')->info('เริ่มสร้างรายการฝาก', [
            'debug' => $debug,
        ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['Success'] === 1) {
                $return['success'] = true;
                $return['data']['url'] = $result['PayPage'];
                $return['data']['oid'] = $result['oid'];
                $return['data']['param'] = $result['Params'];
                $return['msg'] = __('app.topup.create');
            } else {
                $return['msg'] = $result['Message'] ?? __('app.topup.fail');
            }

        } else {
            $result = $response->json();
            $return['msg'] = $result['Message'] ?? __('app.topup.fail');
        }

        return $return;
    }

    public function create_withdraw($url, $data)
    {
        $parterId = config('payment.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
            ->asForm()->post($url, $data);

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

        if (! File::exists(storage_path('logs/xpay'))) {
            File::makeDirectory(storage_path('logs/xpay'));
        }
        Log::channel('xpay_withdraw_create')->info('เริ่มสร้างรายการถอน', [
            'debug' => $debug,
        ]);

        if ($response->successful()) {

            $result = $response->json();

            if ($result['Success'] === 1) {
                $return['success'] = true;
                //                $return['data']['url'] = $result['PayPage'];
                $return['data']['oid'] = $result['oid'];
                //                $return['data']['param'] = $result['Params'];
                $return['msg'] = __('app.topup.create');
            } else {
                $return['msg'] = $result['Message'] ?? __('app.topup.fail');
            }

        } else {
            $result = $response->json();
            //            $return['code'] = $result['code'] ?? 1;
            $return['msg'] = $result['msg'] ?? __('app.withdraw.fail');
        }

        return $return;
    }

    public function create_cancel($url, $data)
    {
        $parterId = config('payment.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
            ->withHeaders([
                'x-wildpay-partnerid' => $parterId,
                'x-wildpay-signature' => $this->generateSignature($parterId, config('payment.secret_key'), $data),
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

        if (! File::exists(storage_path('logs/wildpay'))) {
            File::makeDirectory(storage_path('logs/wildpay'));
        }
        Log::channel('wildpay_cancel_create')->info('เริ่มสร้างรายการยกเลิก', [
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

    public function generateSignature(string $partnerId, string $secret, array $body): string
    {
        $keyString = $partnerId.':'.$this->buildKeyValueString($body);

        // นำ signature ที่ได้ไปใส่ใน x-wildpay-signature
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

    public function create_balance($url, $data)
    {
        $parterId = config('payment.partner_id') ?? '';
        $return['success'] = false;
        $return['msg'] = __('app.withdraw.fail');
        $return['code'] = 1;

        $response = Http::timeout(30)
            ->withHeaders([
                'x-wildpay-partnerid' => $parterId,
                'x-wildpay-signature' => $this->generateSignature($parterId, config('payment.secret_key'), $data),
            ])
            ->asJson()->get($url);

        //        $debug = [
        //            'json' => $response->json(),
        //            'success' => $response->successful(),
        //            'fail' => $response->failed(),
        //            'status' => $response->status(),
        //            'serverError' => $response->serverError(),
        //            'clientError' => $response->clientError(),
        //            'date' => now()->toDateTimeString(),
        //            'param' => $data,
        //        ];

        //        if (! File::exists(storage_path('logs/wildpay'))) {
        //            File::makeDirectory(storage_path('logs/wildpay'));
        //        }
        //        Log::channel('wildpay_cancel_create')->info('เริ่มสร้างรายการยกเลิก', [
        //            'debug' => $debug,
        //        ]);

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

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '20':
                $result = 'dp_ABA_KH_USD';
                break;
            case '21':
                $result = 'dp_ACLEDA_KH_USD';
                break;
            case '22':
                $result = 'dp_MAYBANK_KH_USD';
                break;
            case '23':
            case '156':
                $result = 'dp_PIPAY_KH_USD';
                break;
            case '24':
                $result = 'dp_WING_KH_USD';
                break;
            case '150':
                $result = 'dp_EMOMEY_KH_USD';
                break;
            case '151':
                $result = 'dp_PRINCEBANK_KH_USD';
                break;
            case '152':
                $result = 'dp_AMRETMFI_KH_USD';
                break;
            case '153':
                $result = 'dp_SATHAPANA_KH_USD';
                break;
            case '154':
                $result = 'dp_HATTHA_KH_USD';
                break;
            case '155':
                $result = 'dp_KBPB_KH_USD';
                break;
            case '157':
                $result = 'dp_VATTANAC_KH_USD';
                break;
            case '158':
                $result = 'dp_WINGPAY_KH_USD';
                break;
            default:
                $result = false;
                break;
        }

        return $result;

    }

    public function BanksKorea($bankcode)
    {

        switch ($bankcode) {
            case '400':
                $result = 'dp_ABA_KH_USD';
                break;
            case '402':
                $result = '003';
                break;
            case '403':
                $result = '004';
                break;
            case '404':
                $result = '011';
                break;
            case '405':
                $result = '023';
                break;
            case '407':
                $result = '034';
                break;
            case '408':
                $result = '032';
                break;
            case '409':
                $result = '064';
                break;
            case '413':
                $result = '007';
                break;
            case '415':
                $result = '088';
                break;
            case '416':
                $result = '048';
                break;
            case '417':
                $result = '020';
                break;
            case '419':
                $result = '071';
                break;
            case '420':
                $result = '050';
                break;
            case '421':
                $result = '037';
                break;
            case '422':
                $result = '035';
                break;
            case '423':
                $result = '090';
                break;
            case '424':
                $result = '089';
                break;
            case '425':
                $result = '092';
                break;
            case '426':
                $result = '081';
                break;
            case '431':
                $result = '027';
                break;

            default:
                $result = false;
                break;
        }

        return $result;

    }
}
