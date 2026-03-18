<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class JokerNewRepository extends Repository
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
        $game = 'jokernew';

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

    public function addGameAccount($data): array
    {
        $result = $this->newUser();
        if ($result['success'] == true) {
            $account = $result['account'];
            $result = $this->addUser($account, $data);
        }

        return $result;
    }

    public function newUser(): array
    {
        $return['success'] = false;
        if ($this->method == 'game') {
            $free = 'N';
        } else {
            $free = 'Y';
        }

        $response = DB::table('users_joker')
            ->where('use_account', 'N')
            ->where('enable', 'Y')
            ->where('code', '<>', 0)
            ->where('freecredit', $free)
            ->select('user_name')
            ->inRandomOrder();


        if ($response->exists()) {
            $return['success'] = true;
            $return['account'] = $response->first()->user_name;
        } else {

            $return['success'] = false;
            $return['msg'] = 'ไม่สามารถลงทะเบียนรหัสเกมได้ เนื่องจาก ID เกมหมด โปรดแจ้ง Staff';
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
        $encrypt = $this->PasswordEncrypt($user_pass);
        if ($encrypt['success'] === true) {

            $curl_pass = $encrypt['password'];

            $param = [
                'username' => $username,
                'password' => $curl_pass,
                'time' => time(),
                'agentId' => $this->agent,
                'authCode' => $this->auth
            ];

            $response = $this->GameCurl($param, 'register');

            if ($response['success'] === true) {
                if ($response['status'] == true) {
                    DB::table('users_joker')
                        ->where('user_name', $username)
                        ->update(['date_join' => now()->toDateString(), 'ip' => request()->ip(), 'use_account' => 'Y', 'user_update' => 'SYSTEM']);

                    $return['msg'] = 'Complete';
                    $return['success'] = true;
                    $return['user_name'] = $response['payload']['userApp'];
                    $return['user_pass'] = $user_pass;

                } else {
                    DB::table('users_joker')
                        ->where('user_name', $username)
                        ->update(['use_account' => 'Y']);

                    $return['msg'] = $response['msg'];
                    $return['success'] = false;

                }
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

    public function changePass($data): array
    {
        $return['success'] = false;
        $username = explode('.', $data['user_name']);
        if (!empty($username[1])) {
            $username = $username[1];
        } elseif (!empty($username[0])) {
            $username = $username[0];
        }


        $encrypt = $this->PasswordEncrypt($data['user_pass']);
        if ($encrypt['success'] === true) {
            $user_pass = $encrypt['password'];

            $param = [
                'username' => $username,
                'password' => $user_pass,
                'time' => time(),
                'agentId' => $this->agent,
                'authCode' => $this->auth
            ];


            $response = $this->GameCurl($param, 'updatePassword');

            if ($response['success'] === true) {
                if ($response['status'] == true) {
                    $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
                    $return['success'] = true;
                } else {
                    $return['msg'] = $response['msg'];
                    $return['success'] = false;
                }
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

    public function viewBalance($username): array
    {
        $return['success'] = false;
        $return['score'] = 0;

        $username = explode('.', $username);
        if (!empty($username[1])) {
            $username = $username[1];
        } elseif (!empty($username[0])) {
            $username = $username[0];
        }


        $param = [
            'username' => $username,
            'time' => time(),
            'agentId' => $this->agent,
            'authCode' => $this->auth
        ];

        $response = $this->GameCurl($param, 'balance');

        if ($response['success'] === true) {
            if ($response['status'] == true) {
                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = $response['payload']['balance'];

            } else {

                $return['msg'] = $response['msg'];
                $return['connect'] = true;
                $return['success'] = false;

            }
        } else {
            $return['msg'] = $response['msg'];
            $return['connect'] = false;
            $return['success'] = false;
        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function deposit($username, $amount): array
    {
        $return['success'] = false;
        $user_name = explode('.', $username);
        if (!empty($user_name[1])) {
            $user_name = $user_name[1];
        } elseif (!empty($user_name[0])) {
            $user_name = $user_name[0];
        }

        $before = $this->viewBalance($username);
        if ($before['success'] == true) {
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
                $param = [
                    'username' => $user_name,
                    'amount' => $score,
                    'transferId' => $transID,
                    'time' => time(),
                    'agentId' => $this->agent,
                    'authCode' => $this->auth
                ];

                $response = $this->GameCurl($param, 'deposit');

                if ($response['success'] === true) {
                    if ($response['status'] == true) {
                        $return['success'] = true;
                        $return['ref_id'] = $transID;
                        $return['after'] = $response['payload']['balance'];
                        $return['before'] = $before['score'];
                    } else {
                        $return['success'] = false;
                        $return['msg'] = $response['msg'];
                    }
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }


            }
        } else {

            $return['success'] = false;
            $return['msg'] = $before['msg'];

        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }

    public function withdraw($username, $amount): array
    {
        $return['success'] = false;
        $user_name = explode('.', $username);
        if (!empty($user_name[1])) {
            $user_name = $user_name[1];
        } elseif (!empty($user_name[0])) {
            $user_name = $user_name[0];
        }
        $before = $this->viewBalance($username);
        if ($before['success'] == true) {

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

                $transID = "WD" . date('YmdHis') . rand(100, 999);
                $param = [
                    'username' => $user_name,
                    'amount' => $score,
                    'transferId' => $transID,
                    'time' => time(),
                    'agentId' => $this->agent,
                    'authCode' => $this->auth
                ];

                $response = $this->GameCurl($param, 'withdraw');


                if ($response['success'] === true) {
                    if ($response['status'] == true) {
                        $return['success'] = true;
                        $return['ref_id'] = $transID;
                        $return['after'] = $response['payload']['balance'];
                        $return['before'] = $before['score'];
                    } else {
                        $return['success'] = false;
                        $return['msg'] = $response['msg'];
                    }
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }


            }
        } else {

            $return['success'] = false;
            $return['msg'] = $before['msg'];

        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }

    public function GameCurl($param, $action)
    {
        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $this->agent . '/' . $action;

            $sign = hash('sha256', $this->agent . $this->auth . time() . $this->secretkey);
            $param['sign'] = $sign;

            return Http::timeout(15)->asForm()->post($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if($response === false){
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
        }

        $result = $response->json();
        $result['msg'] = ($result['error'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if($response->failed() || $response->clientError() || $response->serverError()){
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {
            $result['success'] = true;
        }else{
            $result['success'] = false;
        }

        return $result;

    }

    public function PasswordEncrypt($password)
    {
        $return['success'] = false;
        $return['msg'] = 'ไม่สามารถทำการ Encrypt รหัสผ่านได้';

        $param = [
            'password' => $password,
            'time' => time(),
            'agentId' => $this->agent,
            'authCode' => $this->auth
        ];

        $response = $this->GameCurl($param, 'encryptPassword');

        if ($response['success'] === true) {
            if ($response['status'] == true) {
                $return['success'] = true;
                $return['password'] = $response['payload']['text'];
                $return['msg'] = 'ทำการ Encrypt รหัสผ่านแล้ว';
            }
        } else {
            $return['success'] = false;
            $return['msg'] = $response['msg'];
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
