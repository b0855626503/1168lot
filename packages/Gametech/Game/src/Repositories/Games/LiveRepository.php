<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class LiveRepository extends Repository
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
        $game = 'live22';

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
        $response =  rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(30)->withHeaders([
                'Cache-Control' => 'no-store'
            ])->withOptions([
                'verify' => false,
                'debug' => false
            ])->asJson()->post($url, $param);


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

        if ($response->successful()) {
            $result = $response->json();
        }else{
            $result = $response->json();
            $result['success'] = false;

        }

        $result['msg'] = ($result['error'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        return $result;


    }

    /**
     */
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
        $return['success'] = true;
        $return['account'] = '';

//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true, 'account' => ''];
//        }

        return $return;
    }

    /**
     */
    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = "Aa" . rand(100000, 999999);
        $param = [
            'agent_id' => $this->agent,
            'password' => $this->agentPass,
            'preset_password' => $user_pass,
            'client_ip' => request()->server('SERVER_ADDR')
        ];

        $response = $this->GameCurl($param, 'createmember');


        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $response['player_id'];
            $return['user_pass'] = $response['player_password'];

        } else {
            $return['success'] = false;
            $return['msg'] = $response['msg'];
        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }


    /**
     */
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

    /**
     */
    public function viewBalance($username): array
    {
        $return['success'] = false;
        $return['score'] = 0;

        $param = [
            'agent_id' => $this->agent,
            'password' => $this->agentPass,
            'player_id' => $username,
            'client_ip' => request()->server('SERVER_ADDR')
        ];

        $response = $this->GameCurl($param, 'getbalance');


        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['balance'];

        } else {
            $return['connect'] = true;
            $return['success'] = false;
            $return['msg'] = $response['msg'];

        }


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }
        return $return;
    }

    /**
     */
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
                'agent_id' => $this->agent,
                'password' => $this->agentPass,
                'player_id' => $username,
                'amount' => $score,
                'client_ip' => request()->server('SERVER_ADDR')
            ];

            $response = $this->GameCurl($param, 'deposit');


            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['after_balance'];
                $return['before'] = $response['before_balance'];
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

    /**
     */
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
                'agent_id' => $this->agent,
                'password' => $this->agentPass,
                'player_id' => $username,
                'amount' => $score,
                'client_ip' => request()->server('SERVER_ADDR')
            ];

            $response = $this->GameCurl($param, 'withdrawal');


            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['after_balance'];
                $return['before'] = $response['before_balance'];
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
