<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SuperlotRepository extends Repository
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
        $game = 'superlot';

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

    public function GameCurl($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . "/api/v1/" . $action;

            return Http::timeout(15)->withToken($this->auth)->post($url, $param);

        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');


        if ($response->successful()) {
            $result['main'] = true;
        }else{
            $result['main'] = false;
        }

        return $result;


    }

    public function GameCurlGet($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . "/api/v1/" . $action;

            return Http::timeout(15)->withToken($this->auth)->get($url, $param);

        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');


        if ($response->successful()) {
            $result['main'] = true;
        }else{
            $result['main'] = false;
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
        if ($this->method === 'game') {
            $free = 'N';
        } else {
            $free = 'Y';
        }
        $response = DB::table('users_superlot')
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
        $param = [
            'copy_from' => $this->agent,
            'username' => $username,
            'password' => $user_pass,
            'name' => $data['name'],
            'phone' => $data['tel']
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'members');
//        dd($response);

        if ($response['main'] == true) {
            if ($response['success'] === true) {
                DB::table('users_superlot')
                    ->where('user_name', $username)
                    ->update(['date_join' => now()->toDateString(), 'ip' => request()->ip(), 'use_account' => 'Y', 'user_update' => 'SYSTEM']);

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['user_name'] = $username;
                $return['user_pass'] = $user_pass;

            } else {

                DB::table('users_superlot')
                    ->where('user_name', $username)
                    ->update(['use_account' => 'Y']);

                $return['msg'] = $response['msg'];
                $return['success'] = false;

            }
        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
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
            'ag_username' => $this->agent,
            'ag_password' => $this->agentPass,
            'member_username' => $data['user_name'],
            'member_newpassword' => $data['user_pass']
        ];

        $response = $this->GameCurl($param, 'changepassword');

        if ($response['main'] == true) {
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
            'username' => $username
        ];

        $response = $this->GameCurlGet($param, 'members');

        if ($response['main'] == true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['balance'];
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
                'username' => $username,
                'amount' => $score
            ];

            $response = $this->GameCurl($param, 'transfer');

            if ($response['main'] == true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['member_balance_after'];
                $return['before'] = $response['member_balance_before'];

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

            $param = [
                'username' => $username,
                'amount' => $score
            ];

            $response = $this->GameCurl($param, 'transfer');


            if ($response['main'] == true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['member_balance_after'];
                $return['before'] = $response['member_balance_before'];

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
