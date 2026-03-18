<?php


namespace App\Libraries;


use Illuminate\Support\Facades\File;

class ScbOut
{

    public function removeBOM($data) {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }
    public function BankCurl($acc_no, $action, $mode='POST')
    {

        $url = 'https://scb.z7z.work/' . $acc_no . '/' . $action;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $mode,
            CURLOPT_HTTPHEADER => array(
                'access-key:  53cb498c-8516-420f-bd65-90754e19bfbf',
            ),
        ));

        $response = curl_exec($curl);
        $response = $this->removeBOM($response);

        $api = json_decode($response, true);
        if(!isset($api)){
            $api['status'] = false;
            $api['msg'] = 'เชื่อมต่อ api ไม่ได้';
        }
        $api['date'] = now()->toDateTimeString();

        if (!File::exists(storage_path('logs/scb'))) {
            File::makeDirectory(storage_path('logs/scb'));
        }

        $path = storage_path('logs/scb/out_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true), FILE_APPEND);

        curl_close($curl);
        return $api;

    }

    public function BankCurlTrans($acc_no, $action, $param, $mode='POST')
    {

        $url = 'https://scb.z7z.work/' . $acc_no . '/' . $action;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 90,
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
                $result = 'TMB';
                break;
            case '11':
                $result = 'BAY';
                break;
            case '12':
                $result = 'UOB';
                break;
            case '13':
                $result = 'LHB';
                break;
            case '14':
                $result = 'GSB';
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
