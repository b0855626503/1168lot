<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SlotxRepository extends Repository
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
        $game = 'slotx';

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

        $response =  rescue(function () use ($param, $action) {
            $postString = Arr::query($param);

            $signature = base64_encode(hash_hmac("sha1", $postString, $this->secretkey, true));
            $signature = urlencode($signature);
            $url = $this->url . "?AppID=" . $this->login . "&Signature=$signature";


            return Http::timeout(15)->asForm()->post($url, $param);



        }, function ($e) {

            return $e->response;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

//        if($response === false){
////            $result['main'] = false;
//            $result['success'] = false;
//            $result['msg'] = 'เชื่อมต่อไม่ได้';
//            return $result;
//        }

        $result = $response->json();
        $result['msg'] = ($result['Message'] ?? 'พบปัญหาบางประการ');


        if ($response->successful()) {
            $result['success'] = true;
        }else{
            $result['success'] = false;
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
        if ($result['success'] === true) {
            $account = $result['account'];
            $result = $this->addUser($account, $data);
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
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

        $response = DB::table('users_slotx')
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
            'Method' => 'CU',
            'Timestamp' => time(),
            'Username' => $username
        ];

        $response = $this->GameCurl($param, '');


        if ($response['success'] === true) {
            if ($response['Status'] === 'Created') {
                $this->changePass([
                    'user_name' => $username,
                    'user_pass' => $user_pass
                ]);

                DB::table('users_slotx')
                    ->where('user_name', $username)
                    ->update(['date_join' => now()->toDateString(), 'ip' => request()->ip(), 'use_account' => 'Y', 'user_update' => 'SYSTEM']);

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['user_name'] = $username;
                $return['user_pass'] = $user_pass;

            } else {

                DB::table('users_slotx')
                    ->where('user_name', $username)
                    ->update(['use_account' => 'Y']);

                $return['msg'] = $response['msg'];
                $return['success'] = false;

            }
        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }


//        if ($this->debug) {
//            return ['debug' => $this->responses, 'success' => true];
//        }
        return $return;
    }

    public function changePass($data): array
    {
        $return['success'] = false;

        $param = [
            'Method' => 'SP',
            'Password' => $data['user_pass'],
            'Timestamp' => time(),
            'Username' => $data['user_name']
        ];

        $response = $this->GameCurl($param, '');

        if ($response['success'] === true) {
            if ($response['Status'] === 'OK') {
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
            'Method' => 'GC',
            'Timestamp' => time(),
            'Username' => $username
        ];

        $response = $this->GameCurl($param, '');

        if ($response['success'] === true) {
            if ($response['Username'] === $username) {
                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = $response['Credit'];

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
                'Amount' => $score,
                'Method' => 'TC',
                'RequestID' => $transID,
                'Timestamp' => time(),
                'Username' => $username
            ];

            $response = $this->GameCurl($param, '');

            if ($response['success'] === true) {
                if ($response['Username'] === $username) {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = $response['Credit'];
                    $return['before'] = $response['BeforeCredit'];
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
                'Amount' => $score,
                'Method' => 'TC',
                'RequestID' => $transID,
                'Timestamp' => time(),
                'Username' => $username
            ];

            $response = $this->GameCurl($param, '');


            if ($response['success'] === true) {
                if ($response['Username'] === $username) {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = $response['Credit'];
                    $return['before'] = $response['BeforeCredit'];
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
