<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class BetflixRepository extends Repository
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
        $game = 'betflix';

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

    public function GameCurl($param, $action)
    {


        $response = rescue(function () use ($param, $action) {
//            $postString = Arr::query($param);
//            $postString = http_build_query($param, '', '&');

            $url = $this->url . $action;

            return Http::timeout(15)->withHeaders([
                'x-api-betflix' => $this->secretkey,
                'x-api-key' => $this->passkey
            ])->asForm()->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

//        dd($response);

        if ($response === false) {
            $result['main'] = false;
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
        }

        $result = $response->json();

        if ($response->successful()) {
            $result['main'] = true;
            $result['success'] = true;
        } else {
            $result['main'] = false;
            $result['success'] = false;

        }

//        dd($result);
        return $result;


    }

    public function addGameAccount($data): array
    {
        $result = $this->newUser();

        if ($result['success'] === true) {
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

        $response = DB::table('users_betflix')
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

        $user_pass = $data['user_pass'] ?? "Aa" . rand(100000, 999999);

        $param = [
            'apiKey' => $this->passkey,
            'username' => $username,
            'password' => $user_pass,
            'name' => $data['name'],
            'tel' => $data['tel'],
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'register');

        if ($response['success'] === true) {

            DB::table('users_betflix')
                ->where('user_name', $username)
                ->update(['date_join' => now()->toDateString(), 'ip' => request()->ip(), 'use_account' => 'Y', 'user_update' => 'SYSTEM']);

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $response['data']['username'];
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

    public function changePass($data): array
    {
        $return['success'] = false;

        $param = [
            'apiKey' => $this->passkey,
            'username' => $data['user_name'],
            'new_password' => $data['user_pass']
        ];

        $response = $this->GameCurl($param, 'changePassword');

        if ($response['success'] === true) {
            $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
            $return['success'] = true;
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

        $response = $this->GameCurl($param, 'balance');


        if ($response['main'] == true && $response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $score = $response['data']['balance'];
            $return['score'] = $score;
        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
            $return['connect'] = true;
        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }

    public function deposit($username, $amount): array
    {
        $return['success'] = false;

        $ip = request()->ip();
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
            $transID = "DP" . date('YmdHis');
            $param = [
                'username' => $username,
                'amount' => $score,
                'ref' => $transID
            ];

            $response = $this->GameCurl($param, 'transfer');


            if ($response['main'] == true && $response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['data']['balance'];
                $return['before'] = $response['data']['before_balance'];
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

        $ip = request()->ip();
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
            $transID = "WD" . date('YmdHis');
            $param = [
                'username' => $username,
                'amount' => $score,
                'ref' => $transID
            ];

            $response = $this->GameCurl($param, 'transfer');


            if ($response['main'] == true && $response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['data']['balance'];
                $return['before'] = $response['data']['before_balance'];
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
