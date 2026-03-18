<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Jenssegers\Agent\Agent as Agent;

class AmbslotRepository extends Repository
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
        $game = 'ambslot';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

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

    public function GameCurl($param, $action)
    {


        $response =  rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            $password = json_encode($param);
            $iterations = 1000;
            $secret = $this->secretkey;
            $hash = hash_pbkdf2("sha512", $password, $secret, $iterations, 64, true);
            $hash = base64_encode($hash);

//            dd($param.'<br>'.$hash);


            return Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'x-ambslot-signature' => $hash
            ])->withOptions(['debug' => false])->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }



        $result = $response->json();
//        dd($response);
        $result['msg'] = ($result['status']['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if($response->failed() || $response->clientError() || $response->serverError()){
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {
            if($result['status']['code'] === 0){
                $result['success'] = true;
            }else{
                $result['success'] = false;
            }

        }else{
            $result['success'] = false;

        }
        return $result;


    }

    public function GameCurlGet($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(15)->withOptions(['debug' => false])->asJson()->get($url, $param);

        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }


        $result = $response->json();
        $result['msg'] = ($result['msg'] ?? 'พบปัญหาบางประการ');


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
        for ($i = 0; $i < 3; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        $user_name = $data['user_name'] . $str;

        $return['success'] = true;
        $return['account'] = $user_name;

//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true, 'account' => ''];
//        }
        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = $data['user_pass'] ?? "Aa" . rand(100000, 999999);
//        $user_pass = $data['user_pass'];
        $param = [
            'username' => $username,
            'password' => $user_pass,
            'agent' => $this->agent
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'transfer/create');

        if ($response['success'] === true && $response['status']['code'] === 0) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $user_pass;

        } else {
            $return['success'] = false;
            $return['msg'] = $response['status']['message'];
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
            'username' => $data['user_name'],
            'password' => $data['user_pass'],
            'agent' => $this->agent
        ];


        $response = $this->GameCurl($param, 'transfer/password');

        if ($response['success'] === true && $response['status']['code'] === 0) {

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
            'username' => $username,
            'agent' => $this->agent
        ];

        $response = $this->GameCurl($param, 'transfer/balance');


        if ($response['success'] === true && $response['status']['code'] === 0) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = doubleval($response['data']['balance']);

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
                'username' => $username,
                'amount' => (float)$score,
                'agent' => $this->agent,
            ];

//            dd($param);

            $response = $this->GameCurl($param, 'transfer/deposit');

            if ($response['success'] === true && $response['status']['code'] === 0) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['data']['balance']['after'];
                $return['before'] = $response['data']['balance']['before'];
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
                'username' => $username,
                'amount' => (double)$score,
                'agent' => $this->agent,
            ];

            $response = $this->GameCurl($param, 'transfer/withdraw');

            if ($response['success'] === true && $response['status']['code'] === 0) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['data']['balance']['after'];
                $return['before'] = $response['data']['balance']['before'];
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

    public function gameList($product_id): array
    {
        $return['success'] = false;

        $param = [];

//        dd($product_id);

        $response = $this->GameCurlGet($param, 'api/member/auth/cat_detail');
        if ($response['success'] === true) {

            $games = collect($response['cat_detail'])->map(function ($items) {
                $items['img'] = Storage::url('game_img/' . $items['name'].'.png');
                $items['code'] = $items['name'];
                return $items;

            });
            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $games;

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

//        dd($return);

        return $return;
    }

    public function login($username,$password)
    {

        $Agent = new Agent();

        $param = [
            'username' => $username,
            'password' => $password,
            'agent' => $this->agent,
        ];


        $response = $this->GameCurl($param, 'transfer/launch/lobby');
//        dd($response);
//        $return['msg'] = $response['msg'];
//
        if ($response['success'] === true && $response['status']['code'] === 0) {
            $return['success'] = true;
            if ($Agent->isMobile()) {
                $return['url'] = $response['data']['urlMobile'];
            }else{
                $return['url'] = $response['data']['url'];
            }

        } else {
            $return['success'] = false;
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
