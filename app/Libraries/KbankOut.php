<?php


namespace App\Libraries;


use Illuminate\Support\Facades\File;

class KbankOut
{

    public function BankCurl($acc_no, $action, $mode)
    {

        $url = 'https://kbanks.z7z.work/' . $acc_no . '/' . $action . '.php';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $mode,
            CURLOPT_HTTPHEADER => array(
                'access-key:  ca23e34e-74fb-477a-a64b-e58b9ef4b51e',
            ),
        ));

        $response = curl_exec($curl);
        $api = json_decode($response, true);
        if(!isset($api)){
            $api['status'] = false;
        }
        $api['date'] = now()->toDateTimeString();

        if (!File::exists(storage_path('logs/kbank'))) {
            File::makeDirectory(storage_path('logs/kbank'));
        }

        $path = storage_path('logs/kbank/out_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true), FILE_APPEND);

        curl_close($curl);
        return $api;

    }

    public function BankCurlTrans($acc_no, $action, $param, $mode)
    {

        $url = 'https://kbanks.z7z.work/' . $acc_no . '/' . $action . '.php';

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'access-key:  ca23e34e-74fb-477a-a64b-e58b9ef4b51e'
            ),
        ));

        $response = curl_exec($curl);
        $api = json_decode($response, true);
        if(!isset($api)){
            $api['status'] = false;
        }
//        $api['date'] = now()->toDateTimeString();
//        $api = array_merge($api, json_decode($param, true));


        if (!File::exists(storage_path('logs/kbank'))) {
            File::makeDirectory(storage_path('logs/kbank'));
        }

        $path = storage_path('logs/kbank/out_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true), FILE_APPEND);
        curl_close($curl);
        return $api;

    }

    public function Banks($bankcode)
    {

        switch ($bankcode) {
            case '1':
                $result = '003';
                break;
            case '2':
                $result = '001';
                break;
            case '3':
                $result = '004';
                break;
            case '4':
                $result = '010';
                break;
            case '5':
                $result = '025';
                break;
            case '6':
                $result = '023';
                break;
            case '7':
                $result = '018';
                break;
            case '8':
                $result = '028';
                break;
            case '9':
                $result = '029';
                break;
            case '19':
            case '15':
            case '10':
                $result = '007';
                break;
            case '11':
                $result = '017';
                break;
            case '12':
                $result = '016';
                break;
            case '13':
                $result = '020';
                break;
            case '14':
                $result = '022';
                break;
            case '17':
                $result = '026';
                break;
            default:
                $result = '500';
                break;
        }
        return $result;

    }

}
