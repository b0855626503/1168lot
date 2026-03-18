<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class NxRepository extends Repository
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
        $game = 'nx';

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


            return Http::timeout(15)->asForm()->post($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if($response === false){
            $result['main'] = false;
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
        }else{
            $result['msg'] = '';
        }

        $result = $response->json();

        if ($response->successful()) {
            $result['main'] = true;
        } else {
            $result['main'] = false;

        }
        return $result;


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

        $str = "";
        $characters = array_merge(range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < 3; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        $user_name = $this->agent . $str;


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
            'certKey' => CERTKEYNX,
            'agentID' => $this->agent,
            'targetUserID' => $username,
            'name' => $data['name'],
            'brand' => 'NX',
            'transferDayValue' => 0,
            'copyPlayerID' => MEMBERNX
        ];

        $response = $this->GameCurl($param, 'newMemberByCopy');


        if ($response['success'] === true) {

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

    public function changePass($data): array
    {
        $return['success'] = false;


        $param = [
            'certKey' => CERTKEYNX,
            'agentID' => $this->agent,
            'userID' => $data['user_name'],
            'brand' => 'NX',
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
            'certKey' => CERTKEYNX,
            'agentID' => $this->agent,
            'userID' => $username,
            'brand' => 'NX'
        ];

        $response = $this->GameCurl($param, 'queryBalance');
//        dd($response);


        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['connect'] = true;
            $return['success'] = true;
            $score = $response['result']['balance'];
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
                'certKey' => CERTKEYNX,
                'agentID' => $this->agent,
                'userID' => $username,
                'brand' => 'NX',
                'isDeposit' => 1,
                'adjBalance' => $score
            ];

            $response = $this->GameCurl($param, 'adjustBalance');


            if ($response['success'] === true) {
                $after = $this->viewBalance($username);
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $after['score'];
                $return['before'] = $after['score'] - $score;
                $return['msg'] = 'Complete';
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
            $transID = "WD" . date('YmdHis');
            $param = [
                'certKey' => CERTKEYNX,
                'agentID' => $this->agent,
                'userID' => $username,
                'brand' => 'NX',
                'isDeposit' => 0,
                'adjBalance' => $score
            ];

            $response = $this->GameCurl($param, 'adjustBalance');


            if ($response['success'] === true) {
                $after = $this->viewBalance($username);
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $after['score'];
                $return['before'] = $after['score'] + $score;
                $return['msg'] = 'Complete';
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
