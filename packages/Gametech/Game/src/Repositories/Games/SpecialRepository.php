<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\API\Models\GameListProxy;
use Gametech\API\Traits\LogSeamless;
use Gametech\Core\Eloquent\Repository;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;

class SpecialRepository extends Repository
{
    use LogSeamless;

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
    protected $gamesub;

    protected $sign;

    public function __construct($method, $debug, App $app)
    {
        $game = 'special';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method . '.' . $game . '.apiurl');

        $this->agent = config($this->method . '.' . $game . '.agent');

        $this->agentPass = config($this->method . '.' . $game . '.agent_pass');

        $this->login = config($this->method . '.' . $game . '.login');

        $this->auth = config($this->method . '.' . $game . '.auth');

        $this->passkey = config($this->method . '.' . $game . '.passkey');

        $this->secretkey = config($this->method . '.' . $game . '.secretkey');

        $this->sign = '';

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

    public function GameCurlGet_($param, $action)
    {

//        dd($this->url . $action);

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(15)->withOptions(['debug' => false])->asJson()->get($url, $param);


        }, function ($e) {

            return false;

        }, false);

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

        if ($response->successful()) {
            if (is_null($result)) {
                $result['msg'] = 'พบปัญหาบางประการ';
                $result['success'] = false;
            } else {
                $result['msg'] = ($result['message'] ?? 'พบปัญหาบางประการ');
                if ($result['code'] == 0) {
                    $result['success'] = true;
                } else {
                    $result['success'] = false;
                }

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


//        $param = [
//            'username' => $data['username'],
//            'productId' => 'JOKER'
//        ];


//        $response = $this->GameCurl($param, 'seamless/member');

//        $path = storage_path('logs/seamless/register' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- TRANSACTION --', true), FILE_APPEND);
//        file_put_contents($path, print_r($response, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


//        if ($response['success'] === true) {

        $return['msg'] = 'Complete';
        $return['success'] = true;
        $return['user_name'] = $data['username'];
        $return['user_pass'] = '';

//        } else {
//            $return['msg'] = $response['msg'];
//            $return['success'] = false;
//
//        }


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

    public function GameCurl($param, $action)
    {
        $datenow = now()->toDateTimeString();

        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(15)->withOptions(['debug' => false])->withHeaders(['x-sign' => $this->sign])->asJson()->post($url, $param);


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

//        $param['date'] = $datenow;
//        $path = storage_path('logs/seamless/curl' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- curl --', true), FILE_APPEND);
//        file_put_contents($path, print_r($result, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        $result['msg'] = ($result['message'] ?? 'พบปัญหาบางประการ');

        if ($response->successful()) {

            if ($result['status'] === true) {
                $result['success'] = true;
            } else {
                $result['success'] = false;
            }

        } else {
            $result['success'] = false;
        }

        return $result;

    }

    public function viewBalance($username, $product_id): array
    {
        $return['success'] = false;
        $return['score'] = 0;

        $param = [
            'username' => $username,
            'productId' => $product_id
        ];


        $response = $this->GameCurlGet($param, 'balance');

        if ($response['success'] === true) {
            if ($response['data']['status'] == 'SUCCESS') {
                $return['msg'] = 'Complete';
                $return['success'] = true;
                $return['connect'] = true;
                $return['score'] = $response['balance'];

            } else {
                $return['msg'] = 'เกิดข้อผิดพลาด';
                $return['connect'] = true;
                $return['success'] = false;
            }
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

    public function GameCurlGet($param, $action)
    {

//        dd($this->url . $action);


        $response = rescue(function () use ($param, $action) {

            $url = $this->url . $action;

            return Http::timeout(15)->withOptions(['debug' => false])->withHeaders(['x-sign' => $this->sign])->asJson()->post($url, $param);


        }, function ($e) {

            return false;

        }, false);

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
//        dd($result);

        if ($response->successful()) {
            $result['success'] = true;
        } else {
            $result['success'] = false;
        }


        return $result;

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
                'username' => $username,
                'amount' => $score,
                'transactionRef' => $transID,
                'productId' => $product_id
            ];


            $response = $this->GameCurl($param, 'deposit');

            if ($response['success'] === true) {
                if ($response['data']['status'] == 'SUCCESS') {
                    $return['success'] = true;
                    $return['ref_id'] = $response['data']['txId'];
                    $return['after'] = $response['data']['balance'];
                    $return['before'] = $response['data']['beforeBalance'];

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
                'username' => $username,
                'amount' => $score,
                'transactionRef' => $transID,
                'productId' => $product_id
            ];

            $response = $this->GameCurl($param, 'withdraw');

            if ($response['success'] === true) {
                if ($response['data']['status'] == 'SUCCESS') {
                    $return['success'] = true;
                    $return['ref_id'] = $response['data']['txId'];
                    $return['after'] = $response['data']['balance'];
                    $return['before'] = $response['data']['beforeBalance'];

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

        if (config('app.user_url') === '') {
            $baseurl = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }




        $param = [
            'currency' => 'THB'
        ];


        $sign = $this->getSign($param);
        $this->sign = $sign['sign'];

//        dd($param);

        $response = $this->GameCurlGet($param, 'game/list');

//        dd($response);

        if ($response['success'] === true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $response['list'];

//            $path = storage_path('logs/seamless/hotdog_game_'.now()->format('Y_m_d').'.log');
            //                file_put_contents($path, print_r($param, true), FILE_APPEND);

            //                dd($response);
            //                $path = storage_path('logs/seamless/seamlesslogin_' . now()->format('Y_m_d') . '.log');
//            file_put_contents($path, print_r($response, true), FILE_APPEND);

            foreach ($return['games'] as $item) {

//                GameListProxy::firstOrCreate(
//                    ['product' => $product_id, 'code' => $item['code'] , 'game' => $item['code']],
//                    ['category' => $item['category'], 'type' => $item['type'], 'img' => $item['img'], 'name' => $item['name'], 'rank' => $item['rank']]
//                );

                GameListProxy::updateOrCreate(
                    ['product' => $product_id, 'code' => $item['game']],
                    ['category' => 'EGAMES', 'type' => 'SLOT', 'img' => $item['image_url_300x300'], 'name' => $item['name'], 'rank' => 1 , 'game' => $item['game'] ]
                );
            }


        } else {
            $return['msg'] = $response['msg'];
            $return['success'] = false;
        }

//        dd($return);

        return $return;
    }

    public function getSign($param)
    {

        if (config('app.user_url') === '') {
            $baseurl = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }
        $secret = $this->secretkey;
        $requestBody = $param;
        $requestJson = json_encode($requestBody);
        $sign = hash_hmac('sha256', $requestJson, $secret);



        return [
            'param'=> $requestBody,
            'json_encode'=> $requestJson,
            'secertkey'=> $secret,
            'sign' => $sign
        ];


    }

    public function gameListPC($product_id): array
    {
        $return['success'] = false;

        if (config('app.user_url') === '') {
            $baseurl = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $param = [
            'token' => $this->secretkey,
            'productCode' => 'PRAGMATIC',
            'urlWebsite' => $baseurl,
        ];

//        dd($product_id);

        $response = $this->GameCurlGet($param, 'seamless/games');

//        dd($response);

        if ($response['success'] === true) {

            $return['success'] = true;
            $return['msg'] = $response['msg'];
            $return['games'] = $response['data']['games'];

            foreach ($return['games'] as $item) {

//                GameListProxy::firstOrCreate(
//                    ['product' => $product_id, 'code' => $item['code'] , 'game' => $item['code']],
//                    ['category' => $item['category'], 'type' => $item['type'], 'img' => $item['img'], 'name' => $item['name'], 'rank' => $item['rank']]
//                );

                GameListProxy::updateOrCreate(
                    ['product' => 'PRAGMATIC', 'code' => $item['gameCode'], 'game' => $item['gameCode']],
                    ['category' => $item['gameType'], 'type' => $item['gameType'], 'img' => $item['imgUrl'], 'name' => $item['gameName'], 'rank' => $item['mode']]
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
//        $Agent = new Agent();
//
//        if ($pid == 'COCKFIGHT') {
//            $mobile = true;
//        } else {
//            if ($Agent->isMobile()) {
//                $mobile = true;
//            } else {
//                $mobile = false;
//            }
//        }

        if (config('app.user_url') === '') {
            $baseurl = (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        } else {
            $baseurl = config('app.user_url') . '.' . (is_null(config('app.user_domain_url')) ? config('app.domain_url') : config('app.user_domain_url'));
        }

        $return['success'] = false;
        $response = [];
        $member = DB::table('members')->select('code','user_name','balance')->where('session_id', request()->session()->getId())->first();
//        dd($member);

        if ($member) {

            $param = [
                'agent_id' => $this->agent,
                'game_id' => $data['gameCode'],
                'player_id' => $data['username'],
                'return_url' => 'https://'.$baseurl,
                'language' => 'th',
                'currency' => 'THB',
                'session_id' => request()->session()->getId()
            ];

            $sign = $this->getSign($param);

            $this->sign = $sign['sign'];

//            dd($param);
//            GameListProxy::where('code',$data['gameCode'])->increment('click',1);
            $response = $this->GameCurl($param, 'singlelogin');

            $param['param'] = $sign['param'];
            $param['sign'] = $sign['sign'];
            $path = storage_path('logs/seamless/login_hotdog_' . now()->format('Y_m_d') . '.log');
            file_put_contents($path, print_r($data, true), FILE_APPEND);
            file_put_contents($path, print_r($param, true), FILE_APPEND);
            file_put_contents($path, print_r($response, true), FILE_APPEND);
//           dd($response);
            if ($response['success'] === true) {
                $g_name = GameListProxy::where('code', $data['gameCode'])->where('product', Str::upper($data['productId']))->first();
                GameListProxy::where('code', $data['gameCode'])->increment('click', 1);
//                MemberProxy::where('code', $member->code)->update(['session_page' => $response['data']['sessionToken']]);

                $param = [
                    'id' => time(),
                    'roundId' => time(),
                    'playInfo' => $g_name['name'],
                    'status' => 'LOGIN'
                ];
                LogSeamless::log(Str::upper($data['productId']),$member->user_name,$param,$member->balance,$member->balance);
                $return['success'] = true;
                $return['url'] = $response['url'];


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
