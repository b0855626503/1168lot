<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class UfabetRepository extends Repository
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
        $game = 'ufabet';

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


            $url = $this->url . $action;

            return Http::timeout(30)->withHeaders([
                'x-api-key' => '2b6e6699-1a5c-448c-a5b8-47d1038eb2b4'
            ])->asJson()->post($url, $param);


        }, function ($e) {

            return $e;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
//        dd($result);
        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if($response->failed() || $response->clientError() || $response->serverError()){
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {
            if ($result['status'] == 'success') {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }
        }else{
            $result['success'] = false;
        }

        return $result;


    }


    /**
     */
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

        $len = Str::length($this->agent);
        $char = (16 - $len);
        $string = Str::random($char);
        $username = Str::lower($this->agent . $string);

        $response = DB::table('games_user')
            ->where('user_name', $username)
            ->where('enable', 'Y');

        if ($response->exists()) {
            $return = $this->newUser();
        } else {
            $return['success'] = true;
            $return['account'] = Str::lower($string);
        }


        return $return;
    }

    /**
     */
    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = "Aa" . rand(100000, 999999);

        $param = [
            'agentUsername' => $this->agent,
            'agentPassword' => $this->agentPass,
            'username' => $username,
            'password' => $user_pass,
            'contact' => $data['tel']
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'auth');
//        dd($response);

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';

            $return['user_name'] = $response['ufa_username'];
            $return['user_pass'] = $response['ufa_password'];
            if ($response['ufa_username']) {
                $return['success'] = true;
            }

            $return['debug']['json'][]['user_name'] = $response['ufa_username'];
            $return['debug']['json'][]['user_pass'] = $response['ufa_password'];

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
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

        $user_name = $data['user_name'];
        $user_pass = $data['user_pass'];


        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        $return['msg'] = 'เปลี่ยนรหัสผ่านเกมเฉพาะที่เวบนี้เท่านั้น เรียบร้อยแล้ว ระบบไม่สามารถเปลี่ยนรหัสผ่านที่ เวบผู้ให้บริการเกม ได้เนื่องจาก ทางผู้ให้บริการเกมไม่มีบริการดังกล่าว โปรดแน่ใจว่า รหัสผ่่านที่เวบของเรา และของเวบผู้ให้บริการเกม เหมือนกัน เพื่อใช้งาน ระบบ Login อัตโนมัติไปยังเวบ ผู้ให้บริการเกม';
        $return['success'] = true;

        return $return;
    }

    /**
     */
    public function viewBalance($username): array
    {

        $return['success'] = false;
        $return['score'] = 0;

        $param = [
            'agentUsername' => $this->agent,
            'agentPassword' => $this->agentPass,
            'username' => $username
        ];

        $response = $this->GameCurl($param, 'user/credit');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = doubleval($response['current_credit']);

        } else {

            $return['success'] = false;
            $return['connect'] = false;
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
                'agentUsername' => $this->agent,
                'agentPassword' => $this->agentPass,
                'username' => $username,
                'credit' => $score
            ];

            $response = $this->GameCurl($param, 'user/credit/add');
            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['current_credit'];
                $return['before'] = $response['old_credit'];
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
                'agentUsername' => $this->agent,
                'agentPassword' => $this->agentPass,
                'username' => $username,
                'credit' => $score
            ];

            $response = $this->GameCurl($param, 'user/credit/del');
            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['after_balance'];
                $return['before'] = $response['before_balance'];
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

    public function login($username, $password)
    {
        $param = [
            'username' => $username,
            'password' => $password
        ];

        $response = $this->GameCurl($param, 'auth/login');
//        dd($response);
        $return['msg'] = $response['msg'];

        if ($response['success'] === true) {
            if ($response['status'] == 'success') {
                $return['success'] = true;
                $return['url'] = $response['gameUrl'];
            } else {
                $return['success'] = false;

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
