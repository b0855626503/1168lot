<?php


namespace App\Libraries;


use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class Coingecko
{

    public function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }

    public function convert()
    {
        $return['success'] = false;


        $url = 'https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=thb';
        $response = Http::timeout(30)->get($url);


        $api = $response->json();

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString()
        ];

        if (!File::exists(storage_path('logs/coingecko'))) {
            File::makeDirectory(storage_path('logs/coingecko'));
        }
        $path = storage_path('logs/coingecko/rate_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($debug, true), FILE_APPEND);
        file_put_contents($path, print_r($debug, true), FILE_APPEND);


        if ($response->successful()) {

            $result = $response->json();
            $return['success'] = true;
            $return['rate'] = $result['tether']['thb'];


        }


        return $return;
    }

    public function create_payout($url, $data)
    {
        $return['success'] = false;
        $return['message'] = '';


        $hash = config('app.ezpay_hash');
        $response = Http::timeout(30)->withHeaders(['hash-validation' => $hash])->post($url, $data);



        $api = $response->json();

        $debug = [
            'json' => $response->json(),
            'success' => $response->successful(),
            'fail' => $response->failed(),
            'status' => $response->status(),
            'serverError' => $response->serverError(),
            'clientError' => $response->clientError(),
            'date' => now()->toDateTimeString(),
            'param' => $data,
            'hash' => $hash,
        ];
        

        if (!File::exists(storage_path('logs/ezpay'))) {
            File::makeDirectory(storage_path('logs/ezpay'));
        }

        $path = storage_path('logs/ezpay/payout_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($debug, true), FILE_APPEND);


        if ($response->successful()) {

            $result = $response->json();
            if ($result['status'] === 'success') {
                $return['success'] = true;
                $return['message'] = 'Complete';
                $return['data'] = $result['data'];
            } else{
                $return['message'] = $result['message'];
            }

        }else{
            $result = $response->json();
            $return['message'] = $result['message'];

        }


        return $return;
    }



    public function check_uuid($uuid,$id,$subMerId)
    {

        $data = app('Gametech\Payment\Repositories\WithdrawRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'WDR-'.$subMerId.'-'.date('is').str_pad($id, 4, "0", STR_PAD_LEFT);
//            $uuid = 'PAY'.str_pad($id, 7, "0", STR_PAD_LEFT).'X'.date('His');
            return $this->check_uuid($uuid,$id,$subMerId);
        } else {
            return $uuid;
        }
    }

    public function check_uuid2($uuid,$id,$subMerId)
    {

        $data = app('Gametech\Payment\Repositories\WithdrawSeamlessRepository')->findOneWhere(['txid' => $uuid]);
        if (isset($data)) {
            $uuid = 'WDR-'.$subMerId.'-'.date('is').str_pad($id, 4, "0", STR_PAD_LEFT);
            return $this->check_uuid2($uuid,$id,$subMerId);
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
                $result = 'KKP';
                break;
            case '7':
                $result = 'CIMB';
                break;
            case '8':
                $result = 'IBANK';
                break;
            case '9':
                $result = 'TISCO';
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
                $result = 'UOB';
                break;
            case '13':
                $result = 'LH';
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
