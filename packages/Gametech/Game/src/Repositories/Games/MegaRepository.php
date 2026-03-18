<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MegaRepository extends Repository
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
        $game = 'mega';

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

            $postString = http_build_query($param, '', '&');

            $postString = json_encode($param);
            $hash = hash_pbkdf2("sha512", $postString, $this->secretkey, 1000, 64, true);
            $signature = base64_encode($hash);


            return Http::timeout(15)->asForm()->post($url, $param);


        }, function ($e) {

            return $e->response;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }


        $result = $response->json();
//        $result['msg'] = ($result['status']['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if ($response->successful()) {
            if($result['status'] === 'OK') {
                $result['success'] = true;
            }else{
                $result['success'] = false;
            }
        } else {
            $result['success'] = false;
        }

        return $result;
    }


    /**
     */
    public function addGameAccount($data): array
    {
        $result = $this->addUser('', $data);

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

        $response = DB::table('users_pgslot')
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

    /**
     */
    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = "Aa" . rand(100000, 999999);
        $param = [
            'username' => $this->agent,
            'password' => $this->agentPass,
            'membername' => $data['name'],
            'memberpassword' => $user_pass,
        ];

        $response = $this->GameCurl($param, 'newmember');

        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $response['member'];
            $return['user_pass'] = $response['memberpassword'];

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
            'username' => $this->agent,
            'password' => $this->agentPass,
            'member' => $data['user_name'],
            'membernewpassword' => $data['user_pass']
        ];

        $response = $this->GameCurl($param, 'changepass');

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
            'username' => $this->agent,
            'password' => $this->agentPass,
            'member' => $username,
        ];

        $response = $this->GameCurl($param, 'balance');


        if ($response['success'] === true) {
            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['credit'] * 10;
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

    /**
     */
    public function deposit($username, $amount): array
    {
        $return['success'] = false;

        $score = $amount / 10;

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
                'username' => $this->agent,
                'password' => $this->agentPass,
                'member' => $username,
                'amount' => $score
            ];

            $response = $this->GameCurl($param, 'deposit');

            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['membercredit'] * 10;
                $return['before'] = $response['oldcredit'];

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
     */
    public function withdraw($username, $amount): array
    {
        $return['success'] = false;


        $score = $amount / 10;

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
                'username' => $this->agent,
                'password' => $this->agentPass,
                'member' => $username,
                'amount' => $score
            ];

            $response = $this->GameCurl($param, 'withdraw');

            if ($response['success'] === true) {
                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['membercredit'] * 10;
                $return['before'] = $response['oldcredit'];
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
