<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class BiogamingRepository extends Repository
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
        $game = 'biogaming';

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

          return Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store'
            ])->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
//        dd($result);
        $result['msg'] = ($result['msg'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if($response->failed() || $response->clientError() || $response->serverError()){
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {
            if($result['code'] === 0){
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
//        dd($result);
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
        $account = $data['user_name'];
        $result = $this->addUser($account, $data);

        return $result;
    }

    public function newUser(): array
    {
        $return['success'] = true;
        $return['account'] = '';

//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true, 'account' => ''];
//        }
        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = $data['user_pass'] ?? "Aa" . rand(100000, 999999);

        $param = [
            'apitoken' => $this->secretkey,
            'data' => [
                'agent_username' => $this->agent,
                'member_copy' => $this->login,
                'member_detail' => [
                    'username' => $username,
                    'password' => $user_pass,
                    'name' => $data['name'],
                    'tel' => $data['tel'],
                    'email' => 'test@test.com'
                ]
            ]
        ];

        $response = $this->GameCurl($param, 'api/member/profile/register');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $response['res']['username'];
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
            'agent_id' => $this->agent,
            'password' => $this->agentPass,
            'player_id' => $data['user_name'],
            'new_password' => $data['user_pass'],
            'client_ip' => request()->server('SERVER_ADDR')
        ];

        $response = $this->GameCurl($param, 'updatepassword');

        if ($response['success'] == true) {

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
            'apitoken' => $this->secretkey,
            'data' => [
                'agent_username' => $this->agent,
                'username' => $username,
            ]
        ];

        $response = $this->GameCurl($param, 'api/member/profile/balance');


        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = doubleval($response['res']['credit']);

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
                'apitoken' => $this->secretkey,
                'data' => [
                    'agent_username' => $this->agent,
                    'username' => $username,
                    'type' => 1,
                    'credit' => $score,
                ]
            ];

            $response = $this->GameCurl($param, 'api/member/profile/deposit_withdraw');


            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['res']['after_credit'];
                $return['before'] = $response['res']['before_credit'];
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
                'apitoken' => $this->secretkey,
                'data' => [
                    'agent_username' => $this->agent,
                    'username' => $username,
                    'type' => 2,
                    'credit' => $score,
                ]
            ];

            $response = $this->GameCurl($param, 'api/member/profile/deposit_withdraw');


            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['res']['after_credit'];
                $return['before'] = $response['res']['before_credit'];
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

    public function login($data)
    {

//        dd($data);
        $param = [
            'agent' => urlencode(base64_encode($this->agent)),
            'username' => urlencode(base64_encode($data['username'])),
            'password' => urlencode(base64_encode($data['password'])),
            'type' => $data['gameCode'],
            'host' => 'https://demo-auto.168csn.com',
        ];

        $postString = "";
        foreach ($param as $keyR => $value) {
            $postString .= $keyR . '=' . $value . '&';
        }
        $postString = substr($postString, 0, -1);

//        $postString = http_build_query($param, '', '&');
//        dd($postString);
        $return['success'] = true;
        $return['url'] = $this->url.'api/member/auth/api_login?'.$postString;
//        $response = $this->GameCurlGet($postString, 'api/member/auth/login');
//        dd($response);
//        $return['msg'] = $response['msg'];
//
//        if ($response['success'] == true) {
//            if ($response['status'] == 'success') {
//                $return['success'] = true;
//                $return['url'] = $response['gameUrl'];
//            } else {
//                $return['success'] = false;
//
//            }
//
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
