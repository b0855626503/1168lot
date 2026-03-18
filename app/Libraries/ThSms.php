<?php


namespace App\Libraries;

use App\Events\RealTimeMessage;
use Illuminate\Support\Facades\Http;

class ThSms
{

    public $username = null;
    public $password = null;
    public $token = null;
    private $url = 'https://thsms.com/api/';

    public function __construct()
    {
        $config = core()->getConfigData();

        $this->username = $config->sms_username;
        $this->password = $config->sms_password;
        $this->token = $config->sms_token;
    }

    public function ConnectApi($param, $action, $method = 'POST')
    {

        $response = rescue(function () use ($param, $action, $method) {
            $url = $this->url . $action;
            if ($method == 'POST') {
                return Http::timeout(15)->withToken($this->token)->acceptJson()->post($url, $param);
            } else {
                return Http::timeout(15)->withToken($this->token)->acceptJson()->get($url, $param);
            }

        }, function ($e) {
            return $e;
        }, true);

        $result['success'] = false;
        $result['message'] = '';

        if ($response->successful()) {
            $result = $response->json();
        }

        return $result;
    }

    public function checkCredit()
    {

        $param = [];
        $response = $this->ConnectApi($param, 'me', 'GET');
        if ($response['success'] === true && $response['code'] == 200) {
            $credit = $response['data']['wallet']['credit'];
            if ($credit < 50) {
                broadcast(new RealTimeMessage('ยอดเครดิตของ THSMS เหลือน้อยกว่า ' . $credit . 'เครดิต โปรดดำเนินการ'));
            }
        }

    }

    public function sendSms($mobile, $content)
    {
        $param = [
            'sender' => 'Now',
            'msisdn' => [$mobile],
            'message' => $content
        ];
//        broadcast(new RealTimeMessage('เข้าเงื่อนไขฟังชั่น'));
        $response = $this->ConnectApi($param, 'send-sms', 'POST');
//        dd($response);
        if ($response['success'] === true && $response['code'] == 200) {
            $credit = $response['data']['remaining_credit'];
            if ($credit < 50) {
                broadcast(new RealTimeMessage('ยอดเครดิตของ THSMS เหลือน้อยกว่า ' . $credit . 'เครดิต โปรดดำเนินการ'));
            }
        }

    }


}
