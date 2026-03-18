<?php


namespace App\Libraries;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PomPayOut
{

    public function removeBOM($data) {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }
    public function BankCurl($param,$mode)
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
        if(!isset($api)){
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
        if(!isset($api)){
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

    public function check_uuid($uuid)
    {
        $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = Str::uuid()->__toString();
            $this->check_uuid($uuid);
        } else {
            return $uuid;
        }
    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = 'bkb';
                break;
            case '2':
                $result = 'kkb';
                break;
            case '3':
                $result = 'ktb';
                break;
            case '4':
                $result = 'scb';
                break;
            case '5':
                $result = 'ghb';
                break;
            case '6':
                $result = 'kpb';
                break;
            case '7':
                $result = 'cimb';
                break;
            case '19':
            case '15':
            case '10':
                $result = 'tmb';
                break;
            case '11':
                $result = 'boa';
                break;
            case '12':
                $result = 'uob';
                break;
            case '13':
                $result = 'lhb';
                break;
            case '14':
                $result = 'gsb';
                break;
            case '17':
                $result = 'baac';
                break;
            default:
                $result = '500';
                break;
        }
        return $result;

    }

}
