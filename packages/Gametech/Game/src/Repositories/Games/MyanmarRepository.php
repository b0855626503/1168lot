<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class MyanmarRepository extends Repository
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
        $game = 'seamless';

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

            $url = $action;

            return Http::timeout(15)->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->agent . ':' . $this->secretkey)
            ])->withOptions(['debug' => false])->asJson()->post($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if ($response === false) {
//            $result['main'] = false;
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
        }


        $result = $response->json();

        $result['msg'] = ($result['message'] ?? 'พบปัญหาบางประการ');

        if ($response->successful()) {
            if ($result['code'] == 0) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }

        } else {
            $result['success'] = false;
        }

        return $result;

    }

    public function GameCurlGet($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(15)->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->agent . ':' . $this->secretkey)
            ])->asJson()->get($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        if ($response === false) {
//            $result['main'] = false;
            $result['success'] = false;
            $result['msg'] = 'เชื่อมต่อไม่ได้';
            return $result;
        }

        $result = $response->json();

//        dd($result);

        $result['msg'] = ($result['message'] ?? 'พบปัญหาบางประการ');


        if ($response->successful()) {
            if ($result['code'] == 0) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }
        } else {
            $result['success'] = false;
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
        $return['success'] = true;
        $return['account'] = '';

        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;


        $param = [
            'Username' => $data['username'],
            'Agentname' => $this->agent,
            'Fullname' => $data['name'],
            'Password' => $data['user_pass'],
            'Currency' => 'THB',
            'Dob' => now()->toDateString(),
            'Gender' => 0,
            'Email' => '',
            'Mobile' => '',
            'Ip' => request()->server('SERVER_ADDR'),
            'TimeStamp' => now()->getTimestampMs(),
            'Sign' => $this->auth,
            'CommFollowUpline' => 0,
            'PTFollowUpline' => 1
        ];


        $response = $this->GameCurl($param, $this->urlauth.'api/credit-auth/xregister');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $data['username'];
            $return['user_pass'] = $data['user_pass'];

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
            'AgentName' => $this->agent,
            'PlayerName' => $username,
            'TimeStamp' => now()->getTimestampMs(),
            'sign' => $this->auth
        ];


        $response = $this->GameCurlGet($param, $this->urltran.'api/credit-transfer/balance');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['Balance'];

        } else {
            $return['msg'] = 'เกิดข้อผิดพลาด';
            $return['connect'] = true;
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
        } elseif (empty($username) || !$username || is_null($username)) {
            $return['msg'] = "เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = "DP" . date('YmdHis') . rand(100, 999);
            $param = [
                'AgentName' => $this->agent,
                'PlayerName' => $username,
                'Amount' => $score,
                'TimeStamp' => now()->getTimestampMs(),
                'Sign' => $this->auth,
                'TransactionId' => $transID,
                'Remark' => '',
                'AvailableProducts' => [1,2,3,4,6]
            ];


            $response = $this->GameCurl($param, $this->urltran.'api/credit-transfer/deposit');

            if ($response['success'] === true) {

                $newres = $this->viewBalance($username);

                if ($newres['success'] === true) {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = $newres['score'];
                    $return['before'] = $newres['score'] - $score;
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }

            } else {
                $return['msg'] = 'พบข้อผิดพลาด ลองใหม่ในภายหลัง';
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

            $transID = "WD" . date('YmdHis') . rand(100, 999);
            $param = [
                'AgentName' => $this->agent,
                'PlayerName' => $username,
                'Amount' => $score,
                'TimeStamp' => now()->getTimestampMs(),
                'Sign' => $this->auth,
                'TransactionId' => $transID,
                'Remark' => ''
            ];

            $response = $this->GameCurl($param, $this->urltran.'api/credit-transfer/withdraw');

            if ($response['success'] === true) {

                $newres = $this->viewBalance($username);

                if ($newres['success'] === true) {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = $newres['score'];
                    $return['before'] = $newres['score'] + $score;
                } else {
                    $return['success'] = false;
                    $return['msg'] = $response['msg'];
                }

            } else {
                $return['msg'] = 'พบข้อผิดพลาด ลองใหม่ในภายหลัง';
                $return['success'] = false;
            }


        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;
    }


    public function login($username)
    {
        $param = [
            'Username' => $username,
            'Partner' => $this->agent,
            'TimeStamp' => now()->getTimestampMs(),
            'Sign' => $this->auth,
            'Domain' => 'https://demo-auto.168csn.com',
            'Lang' => 'en-us',
            'IsMobile' => false,
            'Ip' => request()->server('SERVER_ADDR'),
        ];

        $response = $this->GameCurl($param, $this->urlauth.'api/credit-auth/login');
//        dd($response);
        $return['msg'] = $response['msg'];

        if ($response['success'] === true) {

                $return['success'] = true;
                $return['url'] = $response['RedirectUrl'];

        } else {
            $return['success'] = false;
        }

        if ($this->debug) {
            return ['debug' => $this->responses, 'success' => true];
        }

        return $return;

    }

    public function gameLog($data): array
    {
        $return['success'] = false;

//        $param = [
//            'username' => $data['username'],
//            'productId' => $data['productId'],
//            'startTime' => $data['startTime'],
//            'endTime' => $data['endTime'],
//            'offset' => $data['offset'],
//            'limit' => $data['limit'],
//        ];

        $param = [
            'productId' => $data['productId'],
            'startTime' => $data['startTime'],
            'endTime' => $data['endTime'],
            'nextId' => $data['nextId']
        ];


//        dd($product_id);

        $response = $this->GameCurlGet($param, 'seamless/betTransactionsV2');
        if ($response['success'] === true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['data'] = $response['data']['txns'];

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
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
