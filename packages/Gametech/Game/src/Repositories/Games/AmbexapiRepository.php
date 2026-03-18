<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\API\Models\GameListProxy;
use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class AmbexapiRepository extends Repository
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

    protected $key;

    protected $prefix;

    protected $web;

    public function __construct($method, $debug, App $app)
    {
        $game = 'ambexapi';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->agent = config($this->method . '.' . $game . '.agent');

        $this->agentPass = config($this->method . '.' . $game . '.agent_pass');

        $this->login = config($this->method . '.' . $game . '.login');

        $this->auth = config($this->method . '.' . $game . '.auth');

        $this->passkey = config($this->method . '.' . $game . '.passkey');

        $this->secretkey = config($this->method . '.' . $game . '.secretkey');

        $this->prefix = config($this->method . '.' . $game . '.prefix');

        $this->web = config($this->method . '.' . $game . '.web');

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

            $url = $this->url . $action . '/' . $this->prefix . '/' . $this->agent;

            return Http::timeout(60)->withHeaders([
                'Content-Type' => 'application/json'
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

    public function GameCurlPg($param, $action)
    {

        $response = rescue(function () use ($param, $action) {

            $url = 'https://test.ambsuperapi.com/' . $action;

            return Http::timeout(60)->withHeaders([
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


        $response = DB::table('games_user')->select('code')->latest('code');

        if ($response->exists()) {
            $code = $response->first();

            $user_name = $this->agent . Str::padLeft(($code + 1), 7, '0');

            $responses = DB::table('games_user')->where('user_name', $user_name);
            if ($responses->exists()) {
                $return['success'] = true;
                $return['account'] = $user_name;
            } else {
                return $this->newUser();

            }


        } else {

            $user_name = $this->agent . Str::padLeft((0 + 1), 7, '0');

            $responses = DB::table('games_user')->where('user_name', $user_name);
            if ($responses->exists()) {
                $return['success'] = true;
                $return['account'] = $user_name;
            } else {
                return $this->newUser();

            }

        }

        return $return;
    }

    public function addUser($username, $data): array
    {
        $return['success'] = false;

        $user_pass = $data['user_pass'] ?? "Aa" . rand(100000, 999999);

        $param = [
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $username,
            'name' => $data['name'],
            'password' => $user_pass,
            'web' => $this->web,
        ];


        $response = $this->GameCurl($param, 'ext/createUser');

        $path = storage_path('logs/seamless/register' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- TRANSACTION --', true), FILE_APPEND);
        file_put_contents($path, print_r($response, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
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

    public function viewBalance($username, $product_id): array
    {
        $return['success'] = false;
        $return['score'] = 0;

        $param = [
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'username' => $username,
            'web' => $this->web,
        ];


        $response = $this->GameCurlGet($param, 'ext/getProfileAndCredit');

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['connect'] = true;
            $return['score'] = $response['data']['balance'];

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

    public function deposit($username, $amount, $product_id): array
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
                'balance' => $score,
                'isDp' => true,
                'web' => $this->web,
            ];


            $response = $this->GameCurl($param, 'ext/deposit');

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

    public function withdraw($username, $amount, $product_id): array
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
                'agentUsername' => $this->agent,
                'key' => $this->secretkey,
                'username' => $username,
                'balance' => $score,
                'isDp' => true,
                'web' => $this->web,
            ];

            $response = $this->GameCurl($param, 'ext/withdrawal');

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

    public function gameList_($product_id): array
    {
        $return['success'] = false;

        $param = ['productId' => $product_id];

//        dd($product_id);

        $response = $this->GameCurlGet($param, 'seamless/games');
        if ($response['success'] == true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $response['data']['games'];

            foreach ($return['games'] as $item) {
                GameListProxy::updateOrCreate(
                    ['product' => $product_id, 'game' => $item['code']]
                );
            }

        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

//        dd($return);

        return $return;
    }

    public function gameList($product_id): array
    {
        $return['success'] = false;

        $param = [
            'agentUsername' => $this->agent,
            'key' => $this->secretkey,
            'tab' => $product_id,
            'web' => $this->web,
        ];



        $response = $this->GameCurlGet($param, 'ext/gameList');

//        dd($response);

        if ($response['success'] === true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $response['data'];

            foreach ($return['games'] as $item) {

//                GameListProxy::firstOrCreate(
//                    ['product' => $product_id, 'code' => $item['code'] , 'game' => $item['code']],
//                    ['category' => $item['category'], 'type' => $item['type'], 'img' => $item['img'], 'name' => $item['name'], 'rank' => $item['rank']]
//                );

                GameListProxy::updateOrCreate(
                    ['product' => $product_id, 'code' => $item['code'], 'game' => $item['code']],
                    ['category' => $item['category'], 'type' => $item['type'], 'img' => $item['img'], 'name' => $item['name'], 'rank' => $item['rank']]
                );
            }


        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

//        dd($return);

        return $return;
    }

    public function login($data)
    {
        $pid = Str::upper($data['productId']);
        $return['game'] = $pid;
        $Agent = new Agent();

        if ($pid == 'COCKFIGHT') {
            $mobile = true;
        } else {
            if ($Agent->isMobile()) {
                $mobile = true;
            } else {
                $mobile = false;
            }
        }

        $return['success'] = false;
        $response = [];
        $member = DB::table('members')->select('user_name')->where('session_id', request()->session()->getId())->first();
//        dd($member);

        if ($member) {


            if ($member->user_name == $data['username']) {


                if ($pid == 'RELAX') {
                    $session = Str::limit(request()->session()->getId(), 20, '');
                } else {
                    $session = request()->session()->getId();
                }
                $param = [
                    'username' => $data['username'],
                    'productId' => Str::upper($data['productId']),
                    'gameCode' => $data['gameCode'],
                    'isMobileLogin' => $mobile,
                    'language' => app()->getLocale() == 'kh' ? 'en' : app()->getLocale(),
                    'sessionToken' => $session
                ];

//                dd($param);

//                $path = storage_path('logs/seamless/login_' . now()->format('Y_m_d') . '.log');
//                file_put_contents($path, print_r($param, true), FILE_APPEND);

//                if($pid = 'PGSOFT2'){
//                    $response = $this->GameCurlPg($param, 'seamless/logIn');
//
//                }else{
//                    $response = $this->GameCurl($param, 'seamless/logIn');
//                }

                $response = $this->GameCurl($param, 'seamless/logIn');


//                dd($response);
//                $path = storage_path('logs/seamless/login_' . now()->format('Y_m_d') . '.log');
//                file_put_contents($path, print_r($response, true), FILE_APPEND);


                if ($response['success'] === true && isset($response['data']['url'])) {
                    $return['success'] = true;
                    $return['url'] = $response['data']['url'];
                } else {
                    $return['success'] = false;
                }
            } else {
                $return['success'] = false;
            }
        }

        $return['api'] = $response;

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
