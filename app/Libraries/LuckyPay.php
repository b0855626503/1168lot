<?php


namespace App\Libraries;


use Gametech\Payment\Models\BankAccount;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class LuckyPay
{

    public function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }

    public function GetSign($uuid,$time)
    {
        $clientCode = config('app.luckypay_client');
        $chainName = "BANK";
        $coinUnit = "THB";
        $privateKey = config('app.luckypay_private');
        $currentTime = $time;
        $clientNo = $uuid;
        $signStr = $clientCode . "&" . $chainName . "&" . $coinUnit . "&" . $clientNo . "&" . $currentTime . $privateKey;
        return md5($signStr);
    }

    public function create($url, $data)
    {
        $return['success'] = false;


        $response = Http::timeout(30)->asForm()->post($url, $data);


        $api = $response->json();

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
        ];

        if (!File::exists(storage_path('logs/luckypay'))) {
            File::makeDirectory(storage_path('logs/luckypay'));
        }
        $path = storage_path('logs/luckypay/out_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($debug, true), FILE_APPEND);


        if ($response->successful()) {

            $result = $response->json();
            if ($result['success'] === true) {
                $return['success'] = true;
                $return['url'] = $result['data']['payUrl'];
            }

        }


        return $return;
    }

    public function create_payout($url, $data)
    {
        $return['success'] = false;
        $return['message'] = '';


        $response = Http::timeout(30)->asForm()->post($url, $data);


        $api = $response->json();

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
        ];

        if (!File::exists(storage_path('logs/luckypay'))) {
            File::makeDirectory(storage_path('logs/luckypay'));
        }
        $path = storage_path('logs/luckypay/payout_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($debug, true), FILE_APPEND);


        if ($response->successful()) {

            $result = $response->json();
            if ($result['success'] === true) {
                $return['success'] = true;
                $return['message'] = 'Complete';
                $return['data'] = $result['data'];

            }else{
                $return['message'] = $result['message'];
            }

        }else{
            $result = $response->json();
            $return['message'] = $result['message'];

        }


        return $return;
    }

    public function imga($algorythm, $w = false, $h = false)
    {
        if (!$w) $w = rand(250, 750);
        if (!$h) $h = rand(250, 750);
        $im = imagecreatetruecolor($w, $h);

        //Here some fancy function is called
        if (method_exists($this, 'img_' . $algorythm)) {
            $this->{'img_' . $algorythm}($im, $w, $h);
        }

        // store the image
        $filepath = storage_path('hengtmp/' . uniqid() . '.png');
        imagepng($im, $filepath);
        imagedestroy($im);

        $headers = array(
            'Content-Type' => 'image/png'
        );

        // respond with the image then delete it
        return response()->file($filepath, $headers)->deleteFileAfterSend(true);
    }

    public function BankCurl($param, $mode)
    {

        $url = config('app.pompay_url_payout');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_CUSTOMREQUEST => $mode
        ));

        $response = curl_exec($curl);
        $response = $this->removeBOM($response);

        $api = json_decode($response, true);
        if (!isset($api)) {
            $api['status'] = false;
        }
        $api['date'] = now()->toDateTimeString();

        if (!File::exists(storage_path('logs/pompay'))) {
            File::makeDirectory(storage_path('logs/pompay'));
        }

        $path = storage_path('logs/pompay/out_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true), FILE_APPEND);

        curl_close($curl);
        return $api;

    }

    public function BankCurlTrans($acc_no, $action, $param, $mode)
    {

        $url = 'http://139.59.120.209:8080/' . $acc_no . '/' . $action;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'access-key:  53cb498c-8516-420f-bd65-90754e19bfbf'
            ),
        ));

        $response = curl_exec($curl);
        $response = $this->removeBOM($response);
        $api = json_decode($response, true);
        if (!isset($api)) {
            $api['status'] = false;
        }
        $api['param'] = $param;
//        $api['date'] = now()->toDateTimeString();
//        $api = array_merge($api, json_decode($param, true));


        if (!File::exists(storage_path('logs/scb'))) {
            File::makeDirectory(storage_path('logs/scb'));
        }

        $path = storage_path('logs/scb/out_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true), FILE_APPEND);
        curl_close($curl);
        return $api;

    }

    public function check_uuid($uuid,$id)
    {

        $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'LUK'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid($uuid,$id);
        } else {
            return $uuid;
        }
    }

    public function check_uuid2($uuid,$id)
    {

        $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'LUK'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid2($uuid,$id);
        } else {
            return $uuid;
        }
    }
    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = 'BBL';
                break;
            case '2':
                $result = 'KBANK';
                break;
            case '3':
                $result = 'KTB';
                break;
            case '4':
                $result = 'SCB';
                break;
            case '5':
                $result = 'GHB';
                break;
            case '6':
                $result = 'KK';
                break;
            case '7':
                $result = 'CIMB';
                break;
            case '19':
            case '15':
            case '10':
                $result = 'TTB';
                break;
            case '11':
                $result = 'BAY';
                break;
            case '12':
                $result = 'UOBT';
                break;
            case '13':
                $result = 'LHBANK';
                break;
            case '14':
                $result = 'GOV';
                break;
            case '17':
                $result = 'BAAC';
                break;
            default:
                $result = '500';
                break;
        }
        return $result;

    }


}
