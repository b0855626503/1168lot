<?php


namespace App\Libraries;


use Gametech\Payment\Models\BankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class HengPay
{

    public function removeBOM($data)
    {
        if (0 === strpos(bin2hex($data), 'efbbbf')) {
            return substr($data, 3);
        }
        return $data;
    }

    public function GetToken()
    {
        $url = 'https://api.amulet168.co/api/v2/auth/token';
        $header = [
            'user-secrete' => config('app.hengpay_secret')
        ];

        if (!File::exists(storage_path('logs/hengpay'))) {
            File::makeDirectory(storage_path('logs/hengpay'));
        }

        $response = Http::timeout(30)->withHeaders($header)->post($url);
//        dd($response);
//        $path = storage_path('logs/hengpay/out_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($response, true), FILE_APPEND);
        if ($response->successful()) {
            $result = $response->json();
            $path = storage_path('logs/hengpay/out_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($result, true), FILE_APPEND);
            if ($result['responseCode'] === '000') {
                BankAccount::where('banks', 100)->update(['token' => $result['access_token']]);
                return $result['access_token'];
            }
        }else{
            return $this->GetToken();
        }
    }

    public function create($token, $data)
    {
        $result['success'] = false;
        $result['url'] = '';

        if (config('app.user_url') === '') {
            $baseurl = 'https://' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = 'https://' . config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $url = 'https://api.amulet168.co/api/v2/order/create/qrcode';
        $header = [
            'x-access-token' => $token,
            'callback-url' => $baseurl.'/hengpay/callback'
        ];
        $response = Http::timeout(30)->withHeaders($header)->post($url, $data);

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

        $path = storage_path('logs/hengpay/out_' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r($debug, true), FILE_APPEND);



        if ($response->successful()) {
            if (isset($api['responseCode'])) {
                $token = $this->GetToken();
                return $this->create($token, $data);
            }
            $url = $response->body();
            $result['success'] = true;
            $result['url'] = base64_encode($url);
        } else {
            if($response->status() == '401'){
                if ($api['responseMesg'] === 'Invalid Token.') {
                    $token = $this->GetToken();
                    return $this->create($token, $data);
                }
            }
        }


        return $result;
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
