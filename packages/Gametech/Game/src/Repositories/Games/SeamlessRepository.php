<?php

namespace Gametech\Game\Repositories\Games;

use Gametech\API\Models\GameListProxy;
use Gametech\API\Traits\LogSeamless;
use Gametech\Core\Eloquent\Repository;
use Gametech\Game\Models\GameSeamless;
use Gametech\Game\Models\GameSeamlessProxy;
use Gametech\Member\Models\MemberCreditLogProxy;
use Gametech\Member\Models\MemberProxy;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use MongoDB\BSON\UTCDateTime;

class SeamlessRepository extends Repository
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

    public function __construct($method, $debug, App $app)
    {
        $game = 'seamless';

        $this->method = $method;

        $this->debug = $debug;

        $this->url = config($this->method.'.'.$game.'.apiurl');

        $this->agent = config($this->method.'.'.$game.'.agent');

        $this->agentPass = config($this->method.'.'.$game.'.agent_pass');

        $this->login = config($this->method.'.'.$game.'.login');

        $this->auth = config($this->method.'.'.$game.'.auth');

        $this->passkey = config($this->method.'.'.$game.'.passkey');

        $this->secretkey = config($this->method.'.'.$game.'.secretkey');

        $this->responses = [];

        parent::__construct($app);
    }

    public function Debug($response, $custom = false)
    {

        if (! $custom) {
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
        $return = [
            'success' => false,
        ];

        $param = [
            'username' => $data['username'],
            'productId' => 'JOKER',
        ];

        $response = $this->GameCurl($param, 'seamless/member');

        //        $path = storage_path('logs/seamless/register' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r('-- TRANSACTION --', true), FILE_APPEND);
        //        file_put_contents($path, print_r($response, true), FILE_APPEND);
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);

        if ($response['success'] === true) {

            $return['msg'] = 'Complete';
            $return['success'] = true;
            $return['user_name'] = $response['data']['username'];
            $return['user_pass'] = $username;

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

        $response = rescue(function () use ($param, $action) {

            $url = $this->url.$action;

            return Http::timeout(10)->withHeaders([
                'Authorization' => 'Basic '.base64_encode($this->agent.':'.$this->secretkey),
            ])->withOptions(['debug' => false])->asJson()->post($url, $param);

        }, function ($e) {

            return false;

        }, true);

        //        if ($this->debug) {
        if ($response !== false) {
            $this->Debug($response);
        } else {
            // debug แบบ custom หรือ log error
            Log::error('Failed to connect or error in request', ['response' => true]);
        }

        //        }

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

    public function changePass($data): array
    {
        $return = [
            'success' => false,
        ];

        $param = [
            'Method' => 'SP',
            'Password' => $data['user_pass'],
            'Timestamp' => time(),
            'Username' => $data['user_name'],
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
            'username' => $username,
            'productId' => $product_id,
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

        $response = rescue(function () use ($param, $action) {

            $url = $this->url.$action;

            return Http::timeout(15)->withHeaders([
                'Authorization' => 'Basic '.base64_encode($this->agent.':'.$this->secretkey),
            ])->asJson()->get($url, $param);

        }, function ($e) {

            return false;

        }, true);

        //        if ($this->debug) {
        if ($response !== false) {
            $this->Debug($response);
        } else {
            Log::error('Failed to connect or error in request', ['response' => true]);
        }
        //        }

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

    public function deposit($username, $amount, $product_id): array
    {
        $return['success'] = false;

        $score = $amount;

        if ($score < 0) {
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username) || ! $username || is_null($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {
            $transID = 'DP'.date('YmdHis').rand(100, 999);
            $param = [
                'username' => $username,
                'amount' => $score,
                'transactionRef' => $transID,
                'productId' => $product_id,
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
            $return['msg'] = 'เกิดข้อผิดพลาด จำนวนยอดเงินไม่ถูกต้อง';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } elseif (empty($username)) {
            $return['msg'] = 'เกิดข้อผิดพลาด ไม่พบข้อมูลรหัสสมาชิก';
            if ($this->debug) {
                $this->Debug($return, true);
            }
        } else {

            $transID = 'WD'.date('YmdHis').rand(100, 999);
            $param = [
                'username' => $username,
                'amount' => $score,
                'transactionRef' => $transID,
                'productId' => $product_id,
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

    public function gameList(string $productId): array
    {
        $productId = strtoupper(trim($productId));
        $cacheKey = "game_list_{$productId}";
        $ttl = now()->addMinutes(10);

        if ($cached = Cache::get($cacheKey)) {
            // Log::channel('api')->info('getgamelist load game get cache',['response' => $cached]);
            return $cached;
        }

        $lockKey = "lock:{$cacheKey}";
        $lock = method_exists(Cache::store(), 'lock') ? Cache::lock($lockKey, 10) : null;

        $release = static function ($l) {
            try {
                $l && $l->release();
            } catch (\Throwable $e) { /* ignore */
            }
        };

        try {
            $lock && $lock->block(5);

            if ($cached = Cache::get($cacheKey)) {
                $this->syncGameListToMongo($productId, (array) ($cached['games'] ?? []));

                return $cached;
            }

            // ===== API =====
            $param = ['productId' => $productId];
            $response = $this->GameCurlGet($param, 'seamless/games');
            // Log::channel('api')->info('getgamelist loadgame',['response' => $response]);
            $ok = is_array($response)
                && ($response['success'] ?? false) === true
                && isset($response['data']['games'])
                && is_array($response['data']['games']);

            if (! $ok) {
                return [
                    'success' => false,
                    'msg' => $response['msg'] ?? 'Unknown error',
                    'games' => [],
                ];
            }

            $games = $response['data']['games'];
            // Log::channel('api')->info('getgamelist game',['response' => $games]);
            // ===== normalize =====
            $catMap = [
                'COCKFIGHT' => 'COCK',
                'AOG' => 'COCK',
                'AMBPOKER' => 'POKER',
                'KINGMAKER' => 'CARD',
            ];

            foreach ($games as &$item) {
                $code = (string) ($item['code'] ?? '');
                $item['code'] = $code;
                $item['name'] = (string) ($item['name'] ?? $code);
                $item['img'] = $item['img'] ?? null;
                $item['type'] = (string) ($item['type'] ?? 'SLOT');
                $item['rank'] = is_numeric($item['rank'] ?? null) ? (int) $item['rank'] : 0;
                $item['category'] = $catMap[$productId] ?? ($item['category'] ?? 'SLOT');
            }
            unset($item);

            if ($this->shouldSyncGameListToMongo($productId)) {
                $this->syncGameListToMongo($productId, $games);
            }

            $data = [
                'success' => true,
                'msg' => $response['msg'] ?? 'OK',
                'games' => $games,
            ];
            // Log::channel('api')->info('getgamelist game complete',['response' => $data]);

            Cache::put($cacheKey, $data, $ttl);

            return $data;

        } finally {
            $release($lock);
        }
    }

    private function shouldSyncGameListToMongo(string $productId): bool
    {
        $cooldownKey = "game_list_sync_mongo:{$productId}";

        return Cache::add($cooldownKey, 1, now()->addMinutes(60));
    }

    /**
     * @param  array<int, array<string, mixed>>  $games
     */
    private function syncGameListToMongo(string $productId, array $games): void
    {
        if (empty($games)) {
            return;
        }

        try {
            $nowMs = (int) round(microtime(true) * 1000);
            $rows = array_map(static function (array $it) use ($productId): array {
                return [
                    'product' => $productId,
                    'code' => (string) ($it['code'] ?? ''),
                    'category' => (string) ($it['category'] ?? 'SLOT'),
                    'type' => (string) ($it['type'] ?? 'SLOT'),
                    'img' => $it['img'] ?? null,
                    'name' => (string) ($it['name'] ?? ''),
                    'rank' => is_numeric($it['rank'] ?? null) ? (int) $it['rank'] : 0,
                    'game' => (string) ($it['code'] ?? ''),
                ];
            }, $games);

            $rows = array_values(array_filter($rows, static fn (array $row): bool => $row['code'] !== ''));
            if (empty($rows)) {
                return;
            }

            $model = GameListProxy::query()->getModel();
            $connName = $model->getConnectionName() ?: config('database.default');
            $table = $model->getTable();

            DB::connection($connName)
                ->collection($table)
                ->raw(function ($collection) use ($rows, $nowMs): void {
                    $ops = [];

                    foreach ($rows as $r) {
                        $ops[] = [
                            'updateOne' => [
                                ['product' => $r['product'], 'code' => $r['code']],
                                [
                                    '$set' => [
                                        'category' => $r['category'],
                                        'type' => $r['type'],
                                        'img' => $r['img'],
                                        'name' => $r['name'],
                                        'rank' => $r['rank'],
                                        'game' => $r['game'],
                                        'updated_at' => new UTCDateTime($nowMs),
                                    ],
                                    '$setOnInsert' => [
                                        'product' => $r['product'],
                                        'code' => $r['code'],
                                        'enable' => true,
                                        'click' => 0,
                                        'created_at' => new UTCDateTime($nowMs),
                                    ],
                                ],
                                ['upsert' => true],
                            ],
                        ];
                    }

                    if (! empty($ops)) {
                        $collection->bulkWrite($ops, ['ordered' => false]);
                    }
                });

            Log::channel('api')->info('getgamelist mongo sync success', [
                'product_id' => $productId,
                'count' => count($rows),
            ]);
        } catch (\Throwable $e) {
            Log::channel('api')->error('getgamelist mongo sync failed', [
                'product_id' => $productId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function login($data)
    {
        $pid = Str::upper($data['productId']);
        $traceId = trim((string) ($data['trace_id'] ?? (request()->attributes->get('frontend_game_login_trace_id') ?? '')));
        if ($traceId === '') {
            $traceId = (string) Str::uuid();
        }

        Log::channel('api')->info('seamless.login.enter', [
            'trace_id' => $traceId,
            'product_id' => $pid,
            'user_name' => (string) ($data['username'] ?? ''),
            'game_code' => (string) ($data['gameCode'] ?? ''),
        ]);

        $return['game'] = $pid;
        $Agent = new Agent;

        $lang = app()->getLocale();
        if ($lang !== 'th') {
            $lang = 'en';
        }

        $setting = GameSeamlessProxy::where('id', $pid)->first();

        //        $gameid = [ 'LALIKA' , 'AFB1188' , 'VIRTUAL_SPORT' , 'COCKFIGHT' , 'AMBSPORTBOOK', 'SABASPORTS' , 'UMBET' , 'SBO'];

        if ($Agent->isMobile()) {
            if ($setting && $setting->mobile == 'Y') {
                $mobile = true;
            } else {
                $mobile = false;
            }

        } else {
            $mobile = false;
        }

        //        if (in_array($pid, $gameid)) {
        //            $mobile = false;
        //        } else {
        //            if ($Agent->isMobile()) {
        //                $mobile = true;
        //            } else {
        //                $mobile = false;
        //            }
        //        }
        //        if($pid = 'PGSOFT2'){
        //            $html = true;
        //        }else{
        //            $html = false;
        //        }

        //        $this->betLimit($pid,$data['username']);

        $return['success'] = false;
        $response = [];
        $member = DB::table('members')
            ->select('user_name', 'code', 'balance')
            ->where('user_name', (string) ($data['username'] ?? ''))
            ->first();

        // Backward-compatible fallback for old web-session flow.
        if (! $member) {
            $sessionId = $this->resolveRequestSessionId();
            if (is_string($sessionId) && $sessionId !== '') {
                $member = DB::table('members')
                    ->select('user_name', 'code', 'balance')
                    ->where('session_id', $sessionId)
                    ->first();
            }
        }

        if ($member) {
            $requestSession = $this->resolveRequestSessionId();
            $headerSession = (string) request()->header('X-Game-Session-Token', '');
            $session = trim($headerSession) !== '' ? trim($headerSession) : $requestSession;
            if ($session === '') {
                $session = (string) Str::uuid();
            }

            if ($pid == 'RELAX') {
                $session = Str::limit($session, 20, '');
            }

            // Keep provider callback identity tied to the latest game login.
            MemberProxy::where('code', $member->code)->update([
                'session_id' => $session,
                'session_page' => $session,
            ]);

            Log::channel('api')->info('seamless.login.member_resolved', [
                'trace_id' => $traceId,
                'member_code' => (int) $member->code,
                'user_name' => (string) ($member->user_name ?? ''),
                'session_token' => $session,
            ]);

            $param = [
                'username' => $data['username'],
                'productId' => Str::upper($data['productId']),
                'gameCode' => $data['gameCode'],
                'isMobileLogin' => $mobile,
                'currency' => 'THB',
                'language' => $lang,
                'sessionToken' => $session,
            ];

            if ($setting !== null && $setting->limit !== null) {
                $param['limit'] = (int) $setting->limit;
            }
            //                dd($param);

            //                if($pid = 'PGSOFT2'){
            //                    $response = $this->GameCurlPg($param, 'seamless/logIn');
            //
            //                }else{
            //                    $response = $this->GameCurl($param, 'seamless/logIn');
            //                }

            $response = $this->GameCurl($param, 'seamless/logIn');
            $response['param'] = $param;
            $response['datetime'] = now()->toDateTimeString();
            $response['api'] = $this->responses;
            Log::channel('api')->info('seamless.login.response', [
                'trace_id' => $traceId,
                'product_id' => $pid,
                'user_name' => $data['username'] ?? null,
                'game_code' => $data['gameCode'] ?? null,
                'response' => $response,
            ]);

            if ($response['success'] === true && isset($response['data']['url'])) {

                if (isset($response['data']['url']['errors'])) {
                    $return['success'] = false;
                    $return['url'] = '';
                } else {
                    $userId = $member->user_name;
                    $gameId = $data['gameCode'];
                    $productId = Str::upper($data['productId']);
                    if ($productId == 'PGSOFT2') {
                        $productId = 'PGSOFT';
                    }

                    // LOG: extracted values
                    //                        Log::info('[LOGIN] GAME', [
                    //                            'userId' => $userId,
                    //                            'gameId' => $gameId,
                    //                            'productId' => $productId,
                    //                        ]);

                    // บันทึกสถานะใหม่ (หรืออัปเดตเวลา)
                    Redis::connection('game')->setex("user_game_status:{$userId}", 600, json_encode([
                        'gameCode' => strtolower((string) $gameId),
                        'productId' => strtoupper((string) $productId),
                        'sessionToken' => $session,
                        'last_active_at' => now()->toDateTimeString(),
                    ]));

                    $g_name = GameListProxy::where('code', $data['gameCode'])->where('product', Str::upper($data['productId']))->first();
                    GameListProxy::where('code', $data['gameCode'])->where('product', Str::upper($data['productId']))->increment('click', 1);
                    MemberProxy::where('code', $member->code)->update(['session_page' => $session]);
                    $param = [
                        'id' => time(),
                        'roundId' => time(),
                        'playInfo' => $g_name['name'],
                        'status' => 'LOGIN',
                    ];
                    LogSeamless::log(Str::upper($data['productId']), $member->user_name, $param, $member->balance, $member->balance);
                    $return['success'] = true;
                    //                        $return['game'] = $pid;
                    $return['url'] = $response['data']['url'];
                }

            } else {
                $return['success'] = false;
                $return['msg'] = ($response['msg'] ?? 'ไม่สามารถเข้าสู่เกมได้ในขณะนี้');
            }
        } else {
            $return['success'] = false;
            $return['msg'] = 'ไม่พบข้อมูลสมาชิกสำหรับเข้าเกม';
            Log::channel('api')->warning('seamless.login.member_not_found', [
                'trace_id' => $traceId,
                'product_id' => $pid,
                'user_name' => (string) ($data['username'] ?? ''),
                'session_id' => $this->resolveRequestSessionId(),
            ]);
        }

        $return['api'] = $response;

        return $return;
    }

    private function resolveRequestSessionId(): string
    {
        try {
            $request = request();
            if (method_exists($request, 'hasSession') && $request->hasSession()) {
                return (string) $request->session()->getId();
            }
        } catch (\Throwable $e) {
            // Session may not be available in stateless API routes.
        }

        return '';
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
            'nextId' => $data['nextId'],
        ];

        //			        dd($param);

        $response = $this->GameCurlGet($param, 'seamless/betTransactionsV2');
        //			dd($response);

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

    public function betLimit($product_id)
    {
        $pid = Str::upper($product_id);
        $betLimit = [];
        $param = [
            'productId' => $product_id,
        ];

        $response = $this->GameCurlGet($param, 'seamless/betLimitsV2');
        //        dd($response);
        if ($response['code'] == 0) {
            $betLimit = (array) data_get($response, 'data.0.BetLimit', []);
            //            foreach($betLimits as $item){
            //                $betLimit[] = $item;
            //            }
        }

        //        dd($betLimit);

        //        $path = storage_path('logs/seamless/betlimit' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);
        //        file_put_contents($path, print_r($response, true), FILE_APPEND);

        return $betLimit;
    }

    public function freeGame($data)
    {
        //        $pid = Str::upper($product_id);

        $param = [
            'productId' => strtoupper($data['product_id']),
            'player_name' => $data['member_user'],
            'free_game_name' => $data['free_game_name'],
            'expired_date' => $data['expired_date'],
            'bet_amount' => $data['bet_amount'],
            'game_count' => $data['game_count'],
            'game_ids' => $data['game_ids'],
        ];

        $response = $this->GameCurl($param, 'seamless/free-game');
        Log::channel('api')->info('seamless.free_game.response', [
            'param' => $param,
            'response' => $response,
        ]);

        if ($response['success'] === true) {

            if (isset($response['data']['freeGameId'])) {
                $return['success'] = true;
                $return['msg'] = $response['msg'];
                $return['freeGameId'] = $response['data']['freeGameId'];

                $newparam = [
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'total' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'pro_code' => 0,
                    'bank_code' => 0,
                    'auto' => 'Y',
                    'enable' => 'Y',
                    'user_create' => 'System Auto',
                    'user_update' => 'System Auto',
                    'refer_code' => 0,
                    'refer_table' => 'freegame',
                    'remark' => 'ได้รับ Free Game จำนวน '.$data['game_count'].' ที่ Bet '.$data['bet_amount'].' ค่าย '.$data['product_id'].' เกม '.$data['game_name'],
                    'kind' => 'FREEGAME',
                    'amount' => 0,
                    'amount_balance' => 0,
                    'withdraw_limit' => 0,
                    'withdraw_limit_amount' => 0,
                    'method' => 'D',
                    'gameuser_code' => 0,
                    'member_code' => $data['member_code'],
                ];

                MemberCreditLogProxy::create($newparam);
            } else {
                $return['msg'] = 'ไม่สามารถเพิ่มฟรีเกมได้';
                $return['success'] = false;
            }

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
    public function model(): string
    {
        return GameSeamless::class;
    }
}
