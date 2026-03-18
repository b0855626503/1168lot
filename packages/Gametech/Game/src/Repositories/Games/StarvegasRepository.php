<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class StarvegasRepository extends Repository
{
    protected $responses;

    protected $method;

    protected $debug;

    protected $url;

    protected $agent;

    protected $agentPass;

    protected $passkey;

    protected $secretkey;

    protected $login;

    protected $auth;

    public function __construct($method, $debug, App $app)
    {
        $game = 'starvegas';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->agent = config($this->method . '.' . $game . '.agent');

        $this->agentPass = config($this->method . '.' . $game . '.agent_pass');

        $this->login = config($this->method . '.' . $game . '.login');

        $this->auth = config($this->method . '.' . $game . '.auth');

        $this->passkey = config($this->method . '.' . $game . '.passkey');

        $this->secretkey = config($this->method . '.' . $game . '.secretkey');

        $this->merchantname = config($this->method . '.' . $game . '.merchantname');

        $this->merchantAdminName = config($this->method . '.' . $game . '.merchant_admin_name');

        $this->merchantAdminPass = config($this->method . '.' . $game . '.merchant_admin_pass');

        $this->merchantApi = config($this->method . '.' . $game . '.merchant_api');

        $this->responses = [];

        parent::__construct($app);
    }

    public function addGameAccount($data): array
    {
//        $result = $this->newUser();
//        if ($result['success'] == true) {
//            $account = $result['account'];
        $result = $this->addUser($data['user_name'], $data);
//        }

        return $result;
    }

    public function newUser(): array
    {
        $return['success'] = false;
        if ($this->method === 'game') {
            $free = 'N';
        } else {
            $free = 'Y';
        }

        $str = "";
        $characters = range('0', '9');
        $max = count($characters) - 1;
        for ($i = 0; $i < 6; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        $user_name = strtolower($this->merchantname . '_' . $str);
        $return['success'] = true;
        $return['account'] = $user_name;

//        $result = $this->checkUser($user_name);
//        if($result['success'] == true){
//            $return['success'] = true;
//            $return['account'] = $user_name;
//        }else{
//            $return['success'] = false;
//        }

//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true, 'account' => ''];
//        }

        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;


//        $user_pass = "Aa" . rand(100000, 999999);
        $user_pass = $data['user_pass'];

        $param = [
            'AdminName' => $this->merchantAdminName,
            'KioskName' => $this->merchantname,
            'Password' => $user_pass,
            'PlayerAccount' => $username
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'player/create');
        if ($response['success'] == true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $user_pass;

        } else {

            $return['msg'] = $response['msg'];
            $return['success'] = false;

        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }

    public function GameCurl($param, $action)
    {
        $result['success'] = false;
        $result['msg'] = '';

        ksort($param);

        $response = rescue(function () use ($param, $action) {

            $sign = $param;

            $sign['PrivateKey'] = $this->merchantApi;

            $postString = "";
            foreach ($sign as $keyR => $value) {
                $postString .= $keyR . '=' . $value . '&';
            }
            $postString = substr($postString, 0, -1);
            $encrypt = base64_encode(hash("sha256", $postString, true));


            $url = $this->url . $action;

            return Http::timeout(30)->withHeaders([
                'merchantName' => $this->merchantname,
                'sign' => $encrypt
            ])->withOptions(['debug' => false, 'verify' => false])->post($url, $param);


        }, function ($e) {

            return $e->response;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

//        if ($response === false) {
//            $result['success'] = false;
//            $result['msg'] = 'เชื่อมต่อไม่ได้';
//            return $result;
//        }

        if ($response->successful()) {
            $result = $response->json();
            $result['msg'] = $result['Message'];
            if ($result['Code'] == 0) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }
        } else {
            $result = $response->json();
            $result['success'] = false;
            $result['msg'] = $result['Message'];
        }


        return $result;

    }

    public function Debug($response, $custom = false)
    {

        if (!$custom) {
            $return['body'] = $response->body();
            $return['json'] = $response->json();
            $return['successful'] = $response->successful();
            $return['failed'] = $response->failed();
            $return['clientError'] = $response->clientError();
            $return['serverError'] = $response->serverError();
        } else {
            $return['body'] = json_encode($response);
            $return['json'] = $response;
            $return['successful'] = 1;
            $return['failed'] = 1;
            $return['clientError'] = 1;
            $return['serverError'] = 1;
        }

        $this->responses[] = $return;


    }

    public function checkUser($username)
    {
        $param = [
            'PlayerAccount' => $username
        ];

        $response = $this->GameCurl($param, 'player/exists');

        if ($response['success'] === true) {
            $return['success'] = true;

        } else {
            $return['success'] = false;
        }

        return $return;

    }

    public function changePass($data): array
    {
        $return['success'] = false;

        $param = [
            'PlayerAccount' => $data['user_name'],
            'Password' => $data['user_pass']
        ];

        $response = $this->GameCurl($param, 'player/resetpassword');

        if ($response['success'] === true) {
            $return['success'] = true;
            $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function viewBalance($username): array
    {
        $return['success'] = false;
        $return['score'] = 0;


        $param = [
            'PlayerAccount' => $username
        ];

        $response = $this->GameCurl($param, 'player/info');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['Data']['Balance'];


        } else {
            $return['msg'] = $response['msg'];
            $return['connect'] = false;
            $return['success'] = false;
        }


        if ($this->debug) {
            $return['debug'] = $this->responses;
            $return['success'] = true;
        }

        return $return;
    }

    public function deposit($username, $amount): array
    {
        $return['success'] = false;

        $score = $amount;

        if ($score < 0) {
            $return['msg'] = "เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = "เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = "DP" . date('YmdHis') . rand(100, 999);
            $score = $score * 1;
            $param = array(
                'ExternalTransactionId' => $transID,
                'PlayerAccount' => $username,
                'Amount' => (int)$score
            );

            $response = $this->GameCurl($param, 'v1/transaction/create');
//
            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['Data']['CreditNow'];
                $return['before'] = $response['Data']['CreditBefore'];
                $return['msg'] = $response['msg'];
            } else {
                $return['msg'] = $response['msg'];
                $return['success'] = false;
            }


        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function withdraw($username, $amount): array
    {
        $return['success'] = false;


        $score = $amount;

        if ($score < 1) {
            $return['msg'] = "เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = "เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $score = $score * -1;
            $transID = "WD" . date('YmdHis') . rand(100, 999);

            $param = array(
                'ExternalTransactionId' => $transID,
                'PlayerAccount' => $username,
                'Amount' => (int)$score
            );

            $response = $this->GameCurl($param, 'v1/transaction/create');
//
            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['Data']['CreditNow'];
                $return['before'] = $response['Data']['CreditBefore'];
                $return['msg'] = $response['msg'];
            } else {
                $return['msg'] = $response['msg'];
                $return['success'] = false;
            }


        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }


    /**
     * Specify Model class name
     *
     * @return mixed
     */
    function model(): string
    {
        return 'Gametech\Game\Contracts\User';
    }
}
