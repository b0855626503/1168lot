<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AmbfunRepository extends Repository
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
        $game = 'ambfun';

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
//        ksort($param);

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return  Http::timeout(15)->asJson()->post($url, $param);

        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
        $result['msg'] = ($result['message'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

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

    public function GameCurlGet($action)
    {
//        ksort($param);

        $responses =  rescue(function () use ($action) {


            $url = $this->url . $action;

            return Http::timeout(15)->get($url);


        }, function ($e) {

            return $e->responses;

        }, true);

        if ($this->debug) {
            $this->Debug($responses);
        }

        if($responses->failed() || $responses->clientError() || $responses->serverError()){
            $result['success'] = false;
            return $result;
        }


        if ($responses->successful()) {
            $result = $responses->json();
            $result['success'] = true;
        }else{
            $result = $responses->json();
            $result['success'] = false;
        }
        $result['msg'] = ($result['message'] ?? '');
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
        $return['success'] = false;
        if ($this->method === 'game') {
            $free = 'N';
        } else {
            $free = 'Y';
        }

        $response = DB::table('users_ambfun')
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
            'memberLoginName' => $username,
            'memberLoginPass' => $user_pass,
            'phoneNo' => $data['tel'],
            'contact' => $data['name'],
            'signature' => md5("$username:$user_pass:$this->agent")
        ];

        $response = $this->GameCurl($param, 'partner/member/create/' . $this->passkey);

        if ($response['success'] === true) {
            if ($response['code'] == 0) {

                DB::table('users_ambfun')
                    ->where('user_name', $username)
                    ->update(['date_join' => now()->toDateString(), 'ip' => request()->ip(), 'use_account' => 'Y', 'user_update' => 'SYSTEM']);


                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['user_name'] = $response['result']['username'];
                $return['user_pass'] = $user_pass;

                $return['debug']['json'][]['user_name'] = $response['result']['username'];
                $return['debug']['json'][]['user_pass'] = $user_pass;
            }else{
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

    /**
     */
    public function changePass($data): array
    {
        $return['success'] = false;

        $user_pass = $data['user_pass'];
        $param = [
            'password' => $user_pass,
            'signature' => md5("$user_pass:$this->agent")
        ];

        $response = $this->GameCurl($param, 'partner/member/reset-password/' . $this->passkey . '/' . $data['user_name']);


        if ($response['success'] === true) {
            if ($response['code'] == 0) {
                $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
                $return['success'] = true;
            }else{
                $return['msg'] = $response['msg'];
                $return['success'] = false;
            }
        } else {
            $return['success'] = false;
            $return['msg'] = 'เกิดข้อผิดพลาดในการ ตรวจสอบ ID Game จึงไม่สามารถทำรายการ เปลี่ยนรหัสได้';
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

        $response = $this->GameCurlGet('partner/member/credit/' . $this->passkey . '/' . $username);

        if ($response['success'] === true) {

            if ($response['code'] == 0) {

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = doubleval($response['result']['credit']);

            } else {
                $return['success'] = false;
                $return['connect'] = true;
                $return['msg'] = $response['msg'];
            }


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
                'amount' => $score,
                'signature' => md5("$score:$username:$this->agent")
            ];

            $response = $this->GameCurl($param, 'partner/member/deposit/' . $this->passkey . '/' . $username);
            if ($response['success'] === true) {
                if ($response['code'] == 0) {

                    $return['success'] = true;
                    $return['ref_id'] = $response['result']['ref'];
                    $return['after'] = $response['result']['after'];
                    $return['before'] = $response['result']['before'];

                }else{

                    $return['success'] = false;
                    $return['msg'] = $response['msg'];

                }


            }else{
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

            $param = [
                'amount' => $score,
                'signature' => md5("$score:$username:$this->agent")
            ];

            $response = $this->GameCurl($param, 'partner/member/withdraw/' . $this->passkey . '/' . $username);
            if ($response['success'] === true) {
                if ($response['code'] == 0) {

                    $return['success'] = true;
                    $return['ref_id'] = $response['result']['ref'];
                    $return['after'] = $response['result']['after'];
                    $return['before'] = $response['result']['before'];

                }else{

                    $return['success'] = false;
                    $return['msg'] = $response['msg'];

                }


            }else{
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
