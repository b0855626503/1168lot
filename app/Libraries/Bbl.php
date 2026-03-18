<?php


namespace App\Libraries;


use Illuminate\Support\Facades\File;

class Bbl
{

    public function BankCurl($acc_no, $action, $mode)
    {

        $url = 'https://z.z7z.work/bbl/' . $acc_no . '/api.php?action=' . $action;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $mode,
            CURLOPT_HTTPHEADER => array(
                'access-key: 916bad49-7fde-4341-9604-b8165baec89c'
            ),
        ));

        $response = curl_exec($curl);
        $api = json_decode($response, true);
        $api['date'] = now()->toDateTimeString();

        if(!File::exists(storage_path('logs/bbl'))){
            File::makeDirectory(storage_path('logs/bbl'));
        }

        $path = storage_path('logs/bbl/gettran_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true),FILE_APPEND);

        curl_close($curl);
        return $api;

    }

    public function BankCurlTrans($acc_no, $action, $param, $mode)
    {

        // print_r($acc_no);
        // print_r($param);
        // exit;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://z.z7z.work/bbl/' . $acc_no . '/api.php?action=' . $action,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $param,
            CURLOPT_HTTPHEADER => array(
                'access-key:  916bad49-7fde-4341-9604-b8165baec89c',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        $api = json_decode($response, true);
        $api['date'] = now()->toDateTimeString();
        $api = array_merge($api , json_decode($param,true));


        if(!File::exists(storage_path('logs/bbl'))){
            File::makeDirectory(storage_path('logs/bbl'));
        }

        $path = storage_path('logs/bbl/gettran_' . $acc_no . '_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($api, true),FILE_APPEND);
        curl_close($curl);
        return $api;

    }

    public function Banks($bankcode){

        switch ($bankcode) {
            case '1':
                $result = 2;
                break;
            case '2':
                $result = 4;
                break;
            case '3':
                $result = 6;
                break;
            case '4':
                $result = 14;
                break;
            case '5':
                $result = 33;
                break;
            case '6':
                $result = 69;
                break;
            case '7':
                $result = 7;
                break;
            case '8':
                $result = 66;
                break;
            case '9':
                $result = 67;
                break;
            case '10':
                $result = 11;
                break;
            case '11':
                $result = 25;
                break;
            case '12':
                $result = 24;
                break;
            case '13':
                $result = 73;
                break;
            case '14':
                $result = 30;
                break;
            case '15':
                $result = 11;
//                $result = 65;
                break;
            case '17':
                $result = 34;
                break;
            default:
                $result = '500';
                break;
        }
        return $result;

    }

}
