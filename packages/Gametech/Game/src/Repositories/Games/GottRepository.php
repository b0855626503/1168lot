<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GottRepository extends Repository
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
        $game = 'gott7';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->url2 = config($this->method . '.' . $game . '.apiurl2');

        $this->agent = config($this->method . '.' . $game . '.agent');

        $this->agentPass = config($this->method . '.' . $game . '.agent_pass');

        $this->login = config($this->method . '.' . $game . '.login');

        $this->auth = config($this->method . '.' . $game . '.auth');

        $this->passkey = config($this->method . '.' . $game . '.passkey');

        $this->secretkey = config($this->method . '.' . $game . '.secretkey');

        $this->responses = [];

        parent::__construct($app);
    }


    public function Debug($response = '', $custom = false)
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

    public function cipherText()
    {

        $cipher = "AES-256-ECB";
        $plaintext = $this->login . '||' . now()->getTimestamp();
        $key = md5($this->secretkey);

        $chiperRaw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA);
        return trim(base64_encode($chiperRaw));

    }

    public function passWord($password)
    {

        $cipher = "AES-256-ECB";
        $plaintext = $password . '||' . now()->getTimestamp();
        $key = md5($this->secretkey);

        $chiperRaw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA);
        return trim(base64_encode($chiperRaw));

    }

    public function GameCurl($param, $action)
    {


        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;


            return Http::timeout(15)->withOptions(['debug' => false])->asForm()->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }


        $result = $response->json();
//        $result['param'] = $param;
//        $result['timestamp'] = now()->getTimestamp();
//        dd($result);

        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if ($response->failed() || $response->clientError() || $response->serverError()) {
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {

            if ($result['status'] === "200") {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }

        } else {
            $result['success'] = false;

        }
        return $result;


    }

    public function GameCurlAuth($param, $action)
    {


        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;


            return Http::timeout(15)->withOptions(['debug' => false])->asForm()->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }


        $result = $response->json();
//        $result['param'] = $param;
//        $result['timestamp'] = now()->getTimestamp();
//        dd($result);

        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if ($response->failed() || $response->clientError() || $response->serverError()) {
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {


                $result['success'] = true;


        } else {
            $result['success'] = false;

        }
        return $result;


    }

    public function GameCurlGet($param, $action)
    {


        $response = rescue(function () use ($param, $action) {

            $url = $this->url2 . $action;


            return Http::timeout(15)->withOptions(['debug' => false])->get($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }


        $result['body'] = $response->body();
        $result['param'] = $param;
        $result['json'] = $response->json();
        $result['url'] = $this->url2 . $action;
        dd($result);

        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if ($response->failed() || $response->clientError() || $response->serverError()) {
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {

            $result['success'] = true;

        } else {
            $result['success'] = false;

        }
        return $result;


    }


    public function addGameAccount($data): array
    {
//        dd($data);

        $result = $this->newUser($data);
        if ($result['success'] === true) {
            $account = $result['account'];
            $result = $this->addUser($account, $data);
        }
//        $account = $data['user_name'];
//        $result = $this->addUser($account, $data);

        return $result;
    }

    public function newUser($data): array
    {

        $str = "";
        $characters = array_merge(range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < 5; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        $user_name = strtolower($this->agent) . $str;

        $response = DB::table('games_user')->where('user_name', $user_name);

        if ($response->exists()) {
            $return['success'] = false;

        } else {
            $return['success'] = true;
            $return['account'] = $user_name;
        }


//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true, 'account' => ''];
//        }
        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = "Aa" . rand(100000, 999999);
//        $user_pass = $data['user_pass'];
        $param = [
            'certId' => $this->auth,
            'ciphertext' => $this->cipherText(),
            'uplineUserID' => $this->agent,
            'loginName' => $username,
            'name' => $data['name'],
            'password' => $user_pass
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'newMember');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $user_pass;

        } else {
            $return['success'] = false;
            $return['msg'] = $response['msg'];
        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }

    public function changePass($data): array
    {
        $return['success'] = false;

        $param = [
            'certKey' => $this->secretkey,
            'agentID' => $this->agent,
            'userID' => $data['user_name'],
            'webSite' => 'GOTT7',
            'password' => $data['user_pass']
        ];


        $response = $this->GameCurl($param, 'resetPassword');

        if ($response['success'] === true) {

            $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
            $return['success'] = true;

        } else {
            $return['success'] = false;
            $return['msg'] = $response['msg'];
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
            'certId' => $this->auth,
            'ciphertext' => $this->cipherText(),
            'userID' => $username
        ];

        $response = $this->GameCurl($param, 'queryBalance');
//        dd($response);

        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = doubleval($response['balance']);

        } else {

            $return['success'] = false;
            $return['connect'] = true;
            $return['msg'] = $response['msg'];

        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
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
            $transID = "DP" . date('YmdHis');

            $param = [
                'certId' => $this->auth,
                'ciphertext' => $this->cipherText(),
                'userID' => $username,
                'isDeposit' => 1,
                'adjBalance' => $score
            ];

//            dd($param);

            $response = $this->GameCurl($param, 'adjustBalance');

            if ($response['success'] === true) {
//                $after = $this->viewBalance($username);
                $return['success'] = true;
                $return['ref_id'] = $response['creditAllocId'];
                $return['after'] = $response['afterBalance'];
                $return['before'] = $response['beforeBalance'];
            } else {

                $return['success'] = false;
                $return['msg'] = $response['msg'];

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
            $transID = "WD" . date('YmdHis');
            $param = [
                'certId' => $this->auth,
                'ciphertext' => $this->cipherText(),
                'userID' => $username,
                'isDeposit' => 0,
                'adjBalance' => $score
            ];

            $response = $this->GameCurl($param, 'adjustBalance');

            if ($response['success'] === true) {
//                $after = $this->viewBalance($username);
                $return['success'] = true;
                $return['ref_id'] = $response['creditAllocId'];
                $return['after'] = $response['afterBalance'];
                $return['before'] = $response['beforeBalance'];;
            } else {
                $return['success'] = false;
                $return['msg'] = $response['msg'];
            }


        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function getToken($username, $password)
    {

        $param = [
            'certId' => $this->auth,
            'ciphertext' => $this->cipherText(),
            'userID' => $username,
            'password' => $this->passWord($password)
        ];

        $response = $this->GameCurlAuth($param, 'authtokenByUserPwd');
//        dd($response);
        return $response['accessToken'];

    }

    public function login($username, $password)
    {
        $token = $this->getToken($username, $password);

//        $time = now()->getTimestampMs();
        $param = [
            'accessToken' => $token,
            'loginName' => $username
        ];

        if($token) {
            $return['success'] = true;
            $return['url'] = $this->url2 . 'loginByApi?accessToken=' . $token . '&loginName=' . strtolower($username);
        }
//        $response = $this->GameCurlGet($param, 'loginByApi');
////        dd($response);
//////        $return['msg'] = $response['msg'];
////
//        if ($response['success'] === true) {
//
//            $return['success'] = true;
//            $return['url'] = $response['RedirectUrl'];
//
//        } else {
//            $return['success'] = false;
//        }

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
