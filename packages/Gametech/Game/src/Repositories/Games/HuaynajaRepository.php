<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;

class HuaynajaRepository extends Repository
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
        $game = 'huaynaja';

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

            $url = $this->url . $action;

            return Http::timeout(15)->asJson()->post($url, $param);


        }, function ($e) {

            return false;

        }, true);

        if ($this->debug) {
            $this->Debug($response);
        }

        $result = $response->json();
        $result['msg'] = ($result['Msg'] ?? 'พบข้อผิดพลาดในการเชื่อมต่อ');

        if ($response->failed() || $response->clientError() || $response->serverError()) {
            $result['success'] = false;
            return $result;
        }


        if ($response->successful()) {
            $result['success'] = true;
        } else {
            $result['success'] = false;
        }

        return $result;


    }

    public function GameCurlGet($param, $action)
    {

        $responses = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

//            return Http::timeout(15)->withOptions(['debug' => true])->withBody($param,'application/json')->get($url);
            return Http::timeout(15)->contentType("application/json")->send('GET', $url, [
                'json' => $param
            ]);


        }, function ($e) {

            return $e->responses;

        }, true);

        if ($this->debug) {
            $this->Debug($responses);
        }

        if ($responses->failed() || $responses->clientError() || $responses->serverError()) {
            $result['success'] = false;
            return $result;
        }


        if ($responses->successful()) {
            $result = $responses->json();
            $result['success'] = true;
        } else {
            $result = $responses->json();
            $result['success'] = false;
        }
        $result['msg'] = ($result['Msg'] ?? '');
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

        $user_pass = "Aa" . rand(100000, 999999);
        $param = [
            'UsernameAPI' => $this->agent,
            'PasswordAPI' => $this->agentPass,
            'MemberPassword' => $user_pass,
            'UplineUsername' => $this->login,
            'Mobile' => $data['tel'],
            'AccountName' => $data['name'],
            'AccountNo' => $data['acc_no'],
            'BankCode' => strtolower($data['bank']['shortcode'])
        ];

//        dd($param);

        $response = $this->GameCurl($param, 'newMember');
//        dd($response);

        if ($response['success'] === true) {
            if ($response['Status'] == 'OK') {

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['user_name'] = $response['MemberUsername'];
                $return['user_pass'] = $user_pass;

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

    public function changePass($data): array
    {
        $return['success'] = false;
        $return['msg'] = 'ไม่มีบริการ เปลี่ยนรหัสผ่าน';


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
            'UsernameAPI' => $this->agent,
            'PasswordAPI' => $this->agentPass,
            'MemberUsername' => $username
        ];


        $response = $this->GameCurlGet($param, 'balance');

        if ($response['success'] === true) {
            if ($response['Status'] == 'OK') {
                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = str_replace(',', '', $response['Credit']);
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
            $transID = "DP" . date('YmdHis') . rand(100, 999);

            $param = [
                'UsernameAPI' => $this->agent,
                'PasswordAPI' => $this->agentPass,
                'MemberUsername' => $username,
                'Amount' => $score
            ];

            $response = $this->GameCurl($param, 'deposit');

            if ($response['success'] === true) {
                if ($response['Status'] == 'OK') {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = str_replace(',', '', $response['MemberCredit']);
                    $return['before'] = str_replace(',', '', $response['OldCredit']);
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
//            $score = $score * -1;
            $transID = "WD" . date('YmdHis') . rand(100, 999);

            $param = [
                'UsernameAPI' => $this->agent,
                'PasswordAPI' => $this->agentPass,
                'MemberUsername' => $username,
                'Amount' => $score
            ];

            $response = $this->GameCurl($param, 'withdraw');


            if ($response['success'] === true) {
                if ($response['Status'] == 'OK') {
                    $return['success'] = true;
                    $return['ref_id'] = $transID;
                    $return['after'] = str_replace(',', '', $response['MemberCredit']);
                    $return['before'] = str_replace(',', '', $response['OldCredit']);
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
