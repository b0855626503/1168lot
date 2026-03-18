<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\API\Models\GameListProxy;
use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Http;
use Jenssegers\Agent\Agent;

class GoalkubRepository extends Repository
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
        $game = 'goalkub';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->agent = config($this->method . '.' . $game . '.agent');

        $this->web = config($this->method . '.' . $game . '.web');

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

        $result['msg'] = ($result['msg'] ?? 'พบปัญหาบางประการ');

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
            if ($result['code'] === 0) {
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

        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $str = "";
        $characters = array_merge(range('a', 'z'), range('0', '9'));
        $max = count($characters) - 1;
        for ($i = 0; $i < 9; $i++) {
            $rand = mt_rand(0, $max);
            $str .= $characters[$rand];
        }

        $username = $this->agent . $str;

//        dd($data);

        $param = [
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $username,
            'name' => $data['name'],
            'password' => $data['user_pass'],
            'web' => $this->web,
        ];

//        dd($param);


        $response = $this->GameCurl($param, 'ext/createUser/' . $this->auth . '/' . $this->agent);

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $username;
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
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $data['user_name'],
            'password' => $data['user_pass'],
            'web' => $this->web,
        ];


        $response = $this->GameCurl($param, 'ext/changePassword/' . $this->auth . '/' . $this->agent);


        if ($response['success'] === true) {

                $return['msg'] = 'เปลี่ยนรหัสผ่านเกม เรียบร้อย';
                $return['success'] = true;

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
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $username,
            'web' => $this->web,
        ];


        $response = $this->GameCurl($param, 'ext/getProfileAndCredit/' . $this->auth . '/' . $this->agent);

//        dd($response);
        if ($response['success'] === true) {

                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
//                $return['score'] = $response['data']['balance'];
                $return['score'] = number_format($response['data']['balance'], 2, '.', '');


        } else {
            $return['msg'] = 'ไม่สามารถเชื่อมต่อ api ได้';
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
        } elseif (empty($username) || !$username || is_null($username)) {
            $return['msg'] = "เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก";
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = "DP" . date('YmdHis') . rand(100, 999);
            $param = [
                'agentUsername' => $this->agent,
                'key' => $this->secretkey,
                'username' => $username,
                'balance' => (float)$score,
                'isDp' => true,
                'web' => $this->web
            ];

//            dd($param);


            $response = $this->GameCurl($param, 'ext/deposit/' . $this->auth . '/' . $this->agent);


            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $response['data']['refId'];
                $return['after'] = $response['data']['afterCredit'];
                $return['before'] = $response['data']['beforeCredit'];


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


        $score = (float)$amount;

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
                'agentUsername' => $this->agent,
                'key' => $this->secretkey,
                'username' => $username,
                'balance' => (float)$score,
                'web' => $this->web
            ];

//            dd($param);

            $response = $this->GameCurl($param, 'ext/withdrawal/' . $this->auth . '/' . $this->agent);

//            dd($response);
            if ($response['success'] === true) {

                $return['success'] = true;
                $return['ref_id'] = $response['data']['refId'];
                $return['after'] = $response['data']['afterCredit'];
                $return['before'] = $response['data']['beforeCredit'];


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

    public function gameList($product_id): array
    {
        $return['success'] = false;

        $param = ['productId' => $product_id];

//        dd($product_id);

        $response = $this->GameCurlGet($param, 'games');
        if ($response['success'] === true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $response['data']['games'];

            foreach ($return['games'] as $item) {

                GameListProxy::firstOrCreate(
                    ['product' => $product_id, 'code' => $item['code']],
                    ['category' => $item['category'], 'type' => $item['type'], 'img' => $item['img'], 'name' => $item['name'], 'rank' => $item['rank'], 'providerCode' => $item['providerCode']]
                );
            }


        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

//        dd($return);

        return $return;
    }


    public function login($username, $password)
    {
        $param = [
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $username,
            'web' => $this->web
        ];

        $response = $this->GameCurl($param, 'ext/redirectLogin/' . $this->auth . '/' . $this->agent);
        if ($response['success'] === true) {
            $return['success'] = true;

            $return['url'] = $response['url'];
        }else{
            $return['success'] = false;
            $return['msg'] = 'ไม่สามารถเข้าระบบได้';
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

    public function outStanding($username, $product_id): array
    {
        $return['success'] = false;
        $amount = 0;
        $param = [
            'username' => $username
        ];


        $response = $this->GameCurlGet($param, 'outstanding');
        if ($response['success'] === true) {
            foreach ($response['data'] as $item) {
                if ($item['productId'] == $product_id) {
                    $amount = $item['outstanding'];
                    break;
                }
            }

            if ($amount > 0) {
                $return['success'] = true;
            } else {
                $return['success'] = false;
            }

            $return['msg'] = $response['msg'];
            $return['amount'] = $amount;

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

        return $return;
    }


    public function outStandings($username): array
    {
        $return['success'] = false;
        $amount = 0;
        $param = [
            'username' => $username
        ];


        $response = $this->GameCurlGet($param, 'outstanding');
        if ($response['success'] === true) {
            foreach ($response['data'] as $item) {
                $amount += $item['outstanding'];
            }

            if ($amount > 0) {
                $return['success'] = true;
            } else {
                $return['success'] = false;
            }

            $return['msg'] = $response['msg'];
            $return['amount'] = $amount;

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
