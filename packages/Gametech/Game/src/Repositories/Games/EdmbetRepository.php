<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class EdmbetRepository extends Repository
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
        $game = 'edmbet';

        $this->method = $method;

        $this->debug = $debug;

        $this->TIMEREQUEST = strtotime(date("Y-m-d H:i:s")) * 1000;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->urlauth = config($this->method . '.' . $game . '.apiurlauth');

        $this->urltran = config($this->method . '.' . $game . '.apiurltran');

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

            return Http::timeout(15)->withOptions(['debug' => false])->asJson()->post($url, $param);


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
//        $result['param'] = $param;
//        $result['auth'] = $this->auth;
//        dd($result);

        $result['msg'] = ($result['Message'] ?? 'พบปัญหาบางประการ');

        if ($response->successful()) {
            if (isset($result['Success']) === true || isset($result['Error']) === true) {
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
        $account = $data['user_name'];
        $result = $this->addUser($account, $data);

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
        $time = now()->getTimestampMs();

        $pass = 'qwer1234';

        $param = [
            'Username' => $username,
            'Agentname' => $this->agent,
            'Fullname' => $data['name'],
            'Password' => $pass,
            'Currency' => 'MMK',
            'Dob' => now()->toDateString(),
            'Gender' => 0,
            'Email' => '',
            'Mobile' => '',
            'Ip' => request()->ip(),
            'TimeStamp' => $time,
            'Sign' => $this->GetSign($this->agent.$username,$time),
            'CommFollowUpline' => 0,
            'PTFollowUpline' => 1
        ];

//        dd($param);

        $response = $this->GameCurl($param, $this->urlauth.'api/credit-auth/xregister');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $username;
            $return['user_pass'] = $pass;

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
        $time = now()->getTimestampMs();
        $return['score'] = 0;

        $param = [
            'AgentName' => $this->agent,
            'PlayerName' => $username,
            'TimeStamp' => $time,
            'sign' => $this->GetSign($this->agent.$username,$time)
        ];


        $response = $this->GameCurl($param, $this->urltran.'api/credit-transfer/balance');

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
        $time = now()->getTimestampMs();

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
                'TimeStamp' => $time,
                'Sign' => $this->GetSign($this->agent.$username,$time),
                'TransactionId' => $transID,
                'Remark' => '',
                'AvailableProducts' => [1,2,3,4,6,7]
            ];

//            dd($param);

            $response = $this->GameCurl($param, $this->urltran.'api/credit-transfer/deposit');
//            dd($response);

            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['Result']['BalanceAfter'];
                $return['before'] = $response['Result']['BalanceBefore'];


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
        $time = now()->getTimestampMs();

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
                'TimeStamp' => $time,
                'Sign' => $this->GetSign($this->agent.$username,$time),
                'TransactionId' => $transID,
                'Remark' => ''
            ];

            $response = $this->GameCurl($param, $this->urltran.'api/credit-transfer/withdraw');

            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $transID;
                $return['after'] = $response['Result']['BalanceAfter'];
                $return['before'] = $response['Result']['BalanceBefore'];


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


    public function login($username,$password)
    {

        $time = now()->getTimestampMs();
        $param = [
            'Username' => $username,
            'Partner' => $this->agent,
            'TimeStamp' => $time,
            'Sign' => $this->GetSign($this->agent.$username.$password,$time),
            'Domain' => route('customer.home.index'),
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


    public function GetSign($data,$time){

        return hash('sha256',strtolower($data).$time.strtolower($this->auth));

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
