<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class PlusRepository extends Repository
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
        $game = '100plus';

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

            $agentid = $this->agent;
            $agentpass = $this->agentPass;
            $secretkey = $this->secretkey;
            $key = $agentpass . $secretkey;

            $message = json_encode($param, JSON_UNESCAPED_UNICODE);
            $hash = hash_hmac('SHA256', $message, $key);


            $url = $this->url . $action . '?hash=' . $hash . '&from=' . $agentid . '&secret=' . $agentpass;


            return Http::timeout(15)->withHeaders([
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-store'
            ])->post($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if($response === false){
//            $result['main'] = false;
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
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

//        dd($result);

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

        $user_pass = "Aa" . rand(100000, 999999);
        $param = [
            'type' => 'player',
            'account' => $this->agent,
            'password' => $user_pass,
            'name' => $data['name'],
            'tel' => $data['tel'],
            'desc' => 'Player',
        ];

        $response = $this->GameCurl($param, 'addAccount');

        if ($response['success'] === true) {
            if ($response['status'] === 'success') {

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['user_name'] = $response['account'];
                $return['user_pass'] = $user_pass;

            } else {
                $return['success'] = false;
                $return['msg'] = $response['msg'];
            }
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
            'account' => $data['user_name'],
            'password' => $data['user_pass'],
            'name' => $data['name'],
            'tel' => '0000000000',
            'desc' => 'Player',
        ];


        $response = $this->GameCurl($param, 'editAccount');

        if ($response['success'] === true) {
            if ($response['status'] === 'success') {

                $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
                $return['success'] = true;

            } else {
                $return['success'] = false;
                $return['msg'] = $response['msg'];
            }
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
            'account' => $username,
        ];


        $response = $this->GameCurl($param, 'getAccount');

        if ($response['success'] === true) {
            if ($response['status'] == 'success') {
                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = $response['score'] * 10;

            } else {
                $return['connect'] = true;
                $return['success'] = false;
                $return['msg'] = $response['msg'];
            }
        } else {
            $return['connect'] = false;
            $return['success'] = false;
            $return['msg'] = $response['msg'];
        }


        if ($this->debug) {
            $return['debug'] =  $this->responses;
            $return['success'] =  true;
            return $return;
        }

        return $return;
    }

    public function deposit($username, $amount): array
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
            $transID = "DP" . date('YmdHis');
            $before = $this->viewBalance($username);

            if ($before['success'] == true) {
                $param = [
                    'account' => $username,
                    'setScore' => strval($score)
                ];

                $response = $this->GameCurl($param, 'setScore');


                if ($response['success'] === true) {
                    if ($response['status'] == 'success') {

                        $after = $this->viewBalance($username);

                        if ($after['success'] == true) {

                            $return['success'] = true;
                            $return['ref_id'] = (isset($response['acc']) ?: $transID);
                            $return['after'] = $after['score'];
                            $return['before'] = $before['score'];

                        } else {

                            $return['success'] = false;
                            $return['msg'] = $after['msg'];
                        }

                    } else {
                        $return['success'] = false;
                        $return['msg'] = $response['msg'];
                    }
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }


            } else {

                $return['success'] = false;
                $return['msg'] = $before['msg'];
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
            $before = $this->viewBalance($username);
            if ($before['success'] == true) {
                $score = ($score * -1);

                $param = [
                    'account' => $username,
                    'setScore' => strval($score)
                ];

                $response = $this->GameCurl($param, 'setScore');



                if ($response['success'] === true) {
                    if ($response['status'] === 'success') {

                        $after = $this->viewBalance($username);
                        if ($after['success'] == true) {

                            $return['success'] = true;
                            $return['ref_id'] = (isset($response['acc']) ?: $transID);
                            $return['after'] = $after['score'];
                            $return['before'] = $before['score'];

                        } else {

                            $return['success'] = false;
                            $return['msg'] = $after['msg'];
                        }

                    } else {
                        $return['success'] = false;
                        $return['msg'] = $response['msg'];
                    }
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }


            } else {

                $return['success'] = false;
                $return['msg'] = $before['msg'];
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
