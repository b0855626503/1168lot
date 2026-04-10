<?php

namespace Gametech\API\Http\Controllers;

use App\Services\GameLogRedisService;
use Gametech\API\Models\GameLogProxy;
use Gametech\API\Traits\LogSeamless;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class NewCommonFlowRedisController extends AppBaseController
{
    use LogSeamless;

    protected $gameLogRedis;
    protected $redis;
    protected $_config;
    protected $repository;
    protected $memberRepository;
    protected $gameUserRepository;
    protected $request;
    protected $member;
    protected $balances = 'balance';
    protected $game = 'ACE333';
    protected $days = 3;
    protected $now;
    protected $expireAt;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo,
        GameLogRedisService $gameLogRedis,
        Request $request
    ) {
        $this->redis = Redis::connection('gamelog');
        $this->_config = $request->input('_config');
        $this->middleware('api');
        $this->repository = $repository;
        $this->memberRepository = $memberRepo;
        $this->gameUserRepository = $gameUserRepo;
        $this->request = $request;
        $this->gameLogRedis = $gameLogRedis;

        $this->now = now();

        $productId = session('productId');
        if (in_array($productId, [
            'UMBET', 'LALIKA', 'AFB1188', 'VIRTUAL_SPORT', 'COCKFIGHT', 'AMBSPORTBOOK', 'SABASPORTS',
            'SBO', 'AOG', 'FB_SPORT', 'DB SPORTS',
        ])) {
            $this->days = 7;
        }

        $this->expireAt = new UTCDateTime($this->now->copy()->addDays($this->days));

        $username = $request->input('username');
        $token = $request->input('token', $request->input('sessionToken'));

        $query = MemberProxy::without('bank')->where('user_name', $username)->where('enable', 'Y');
        if ($token) {
            $query->where('session_id', $token);
        }

        $this->member = $query->first();
    }

    // =================== LOG CREATE & UPDATE ===================

    public function getBalance(Request $request)
    {
        $session = $request->all();

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 30001);
        }

        $param = $this->responseData(
            $session['id'],
            $this->member->user_name,
            $session['productId'],
            0,
            $this->member->balance
        );

        $this->createGameLog([
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'getbalance',
            'response' => 'in',
            'amount' => 0,
            'con_1' => null,
            'con_2' => null,
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $this->member->balance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ]);

        return $param;
    }

    protected function responseData($id, $username, $productId, $statusCode, $balance = 0)
    {
        return [
            'id' => $id,
            'statusCode' => $statusCode,
            'balance' => (float) $balance,
            'productId' => $productId,
            'currency' => 'THB',
            'username' => $username,
            'timestampMillis' => $this->now->getTimestampMs(),
        ];
    }

    protected function createGameLog(array $data)
    {
        // บันทึก log (sub) ลง Redis
        $logId = $this->gameLogRedis->saveLog($data);
        $log = $this->gameLogRedis->getLog($logId);

        // เพิ่ม index (zset) สำหรับค้นหาเร็ว
        if (! empty($log['con_2']) && ! empty($log['con_1'])) {
            $roundId = $log['con_2'];
            $txnId = $log['con_1'];
            $productId = $log['company'] ?? null;
            $method = $log['method'] ?? null;
            $user = $log['game_user'] ?? null;
            $value = $log['con_3'];

            if (is_null($value)) {
                $con_3 = 'null';
            } elseif (is_bool($value)) {
                $con_3 = $value ? 'true' : 'false';
            } else {
                $con_3 = (string) $value;
            }

            $value = $log['con_4'];

            if (is_null($value)) {
                $con_4 = 'null';
            } else {
                $con_4 = (string) $value;
            }
            $score = strtotime($log['created_at'] ?? $log['date_create'] ?? now());
            $keys = [
                $this->buildRedisKey($user, $productId, $method, 'both', $txnId, $roundId),
                $this->buildRedisKey($user, $productId, $method, 'con_1', $txnId, $roundId),
                $this->buildRedisKey($user, $productId, $method, 'con_2', $txnId, $roundId),
            ];
            $this->redis->pipeline(function ($pipe) use ($keys, $score, $logId) {
                foreach ($keys as $key) {
                    Log::channel('gamelog')->debug("[createGameLog] Add log to key: $key log_id: $logId");
                    $pipe->zadd($key, $score, (string) $logId);
                    $pipe->expire($key, 86400);
                }
            });
            $addonData = [
                'amount' => $log['amount'] ?? 0,
                'method' => $log['method'],
                'con_1' => $txnId,
                'con_2' => $roundId,
                'con_3' => $con_3,
                'con_4' => $con_4,
                'status' => $log['status'] ?? '',
                'by_id' => $log['by_id'] ?? '',
                'created_at' => $log['created_at'] ?? $log['date_create'] ?? '',
            ];
            $addonKey = "game:log:$user:$productId:addon:$logId";
            Log::channel('gamelog')->debug("[createGameLog] Add Addon log to key: $addonKey log_id: $logId");
            $this->redis->hmset($addonKey, $addonData);
            $this->redis->expire($addonKey, 86400);
        }

        return $log;
    }

    // =================== LOG SEARCH ===================

    protected function buildRedisKey($user, $company, $method, $find, $txnid, $roundId)
    {
        $parts = ['game', 'log', $user, $company, $method, $find];
        if ($find === 'both') {
            $parts[] = $txnid;
            $parts[] = $roundId;
        } elseif ($find === 'con_1') {
            $parts[] = $txnid;
        } else {
            $parts[] = $roundId;
        }
        $buildkey = implode(':', $parts);
        Log::channel('gamelog')->debug("Redis BUILD KEY FOR CREATE: $buildkey");

        return $buildkey;
    }

    protected function buildRedisKeySearch($user, $company, $method, $find, $txnid, $roundId)
    {
        $parts = ['game', 'log', $user, $company, $method, $find];
        if ($find === 'both') {
            $parts[] = $txnid;
            $parts[] = $roundId;
        } elseif ($find === 'con_1') {
            $parts[] = $txnid;
        } else {
            $parts[] = $roundId;
        }
        $buildkey = implode(':', $parts);
        Log::channel('gamelog')->debug("Redis BUILD KEY FOR SEARCH: $buildkey");

        return $buildkey;
    }

    public function placeBets(Request $request)
    {
        Log::channel('gamelog')->debug('Start placebet-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;
        $amount = collect($session['txns'])->sum('betAmount');

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'betmain',
            'response' => 'in',
            'amount' => $amount,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {

            $find = $this->getLastLogId('bet', $this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], $txn['status']);

            if ($find) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($txn['status'] === 'OPEN') {

                $waitingExists = $this->getLastLogId('bet', $this->member->user_name, $session['productId'], 'WAITING', 'both', $txn['id'], $txn['roundId'], $txn['status']);

                if ($waitingExists) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                        'balanceBefore' => (float) $oldBalance,
                        'balanceAfter' => (float) $this->member->balance,
                    ];
                    $this->createGameLog([
                        'input' => $txn,
                        'output' => $param,
                        'company' => $session['productId'],
                        'game_user' => $this->member->user_name,
                        'method' => $txn['status'],
                        'response' => 'in',
                        'amount' => $txn['betAmount'],
                        'con_1' => $txn['id'],
                        'con_2' => $txn['roundId'],
                        'con_3' => $txn['status'],
                        'con_4' => null,
                        'before_balance' => $oldBalance,
                        'after_balance' => $this->member->balance,
                        'date_create' => $this->now->toDateTimeString(),
                        'expireAt' => $this->expireAt,
                    ]);
                    break;
                }
            }

            $betAmount = $txn['betAmount'];
            $skipUpdate = $txn['skipBalanceUpdate'] ?? false;

            if (! $skipUpdate) {
                $newBalance = $this->member->balance - $betAmount;

                //				if ($newBalance < 0) {
                //					$param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                //					break;
                //				}

                if (! $this->safeDecrementBalance($betAmount)) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                    break;
                }

            }

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $betAmount,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ]);

            //            LogSeamless::log(
            //                $session['productId'],
            //                $this->member->user_name,
            //                $txn,
            //                $oldBalance,
            //                $this->member->balance
            //            );

            Log::channel('gamelog')->debug('placeBets createGameLog', [
                'method' => $txn['status'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
            ]);
        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    // =================== LOG KEY & ADDON ===================

    protected function createGameLogMain(array $data)
    {
        // บันทึก log หลัก ลง Redis
        $logId = $this->gameLogRedis->saveLog($data);
        $log = $this->gameLogRedis->getLog($logId);
        // เพิ่ม index (zset) สำหรับค้นหาเร็ว
        if (! empty($log['con_2']) && ! empty($log['con_1'])) {
            $roundId = $log['con_2'];
            $txnId = $log['con_1'];
            $productId = $log['company'] ?? null;
            $method = $log['method'] ?? null;
            $user = $log['game_user'] ?? null;
            $value = $log['con_3'];

            if (is_null($value)) {
                $con_3 = 'null';
            } elseif (is_bool($value)) {
                $con_3 = $value ? 'true' : 'false';
            } else {
                $con_3 = (string) $value;
            }

            $value = $log['con_4'];

            if (is_null($value)) {
                $con_4 = 'null';
            } else {
                $con_4 = (string) $value;
            }

            $score = strtotime($log['created_at'] ?? $log['date_create'] ?? now());
            $keys = [
                $this->buildRedisKey($user, $productId, $method, 'both', $txnId, $roundId),
            ];
            $this->redis->pipeline(function ($pipe) use ($keys, $score, $logId) {
                foreach ($keys as $key) {
                    $pipe->zadd($key, $score, (string) $logId);
                    $pipe->expire($key, 86400);
                }
            });
            $addonData = [
                'amount' => $log['amount'] ?? 0,
                'method' => $log['method'],
                'con_1' => $txnId,
                'con_2' => $roundId,
                'con_3' => $con_3,
                'con_4' => $con_4,
                'status' => $log['status'] ?? '',
                'by_id' => $log['by_id'] ?? '',
                'created_at' => $log['created_at'] ?? $log['date_create'] ?? '',
            ];
            $addonKey = "game:log:$user:$productId:addon:$logId";
            $this->redis->hmset($addonKey, $addonData);
            $this->redis->expire($addonKey, 86400);
        }

        return $log;
    }

    protected function getLastLogId($action, $user, $company, $method, $find, $txnid, $roundId, $con_3 = null, $con_4 = false)
    {
        if ($action === 'bet') {
            $methods = strtoupper($method) === 'ALL' ? ['OPEN', 'WAITING'] : [strtoupper($method)];
        } elseif ($action === 'settled') {
            $methods = strtoupper($method) === 'ALL' ? ['OPEN', 'SETTLED', 'WAITING'] : [strtoupper($method)];
        }

        $redisKeysMiss = [];
        foreach ($methods as $m) {
            $redisKey = $this->buildRedisKeySearch($user, $company, $m, $find, $txnid, $roundId);

            $ids = $this->redis->zrevrange($redisKey, 0, 5);

            if (empty($ids)) {
                Log::channel('gamelog')->debug("Redis MISS for key: $redisKey");
                $redisKeysMiss[] = $redisKey;

                continue;
            }
            foreach ($ids as $id) {
                $addonKey = "game:log:$user:$company:addon:$id";
                Log::channel('gamelog')->debug("CHECKING addonKey: $addonKey con_3=".json_encode($con_3).' con_4='.json_encode($con_4));
                $addon = $this->redis->hgetall($addonKey);

                if (empty($addon)) {
                    Log::channel('gamelog')->debug("NOT FOUND addon for log_id=$id");

                    continue;
                }

                Log::channel('gamelog')->debug("FOUND addon for log_id=$id: ".json_encode($addon));

                $matchCon3 = false;
                if ($con_3 === null) {
                    $matchCon3 = ($addon['con_3'] ?? null) === 'null';
                } elseif ($con_3 === 'ALL') {
                    $matchCon3 = in_array($addon['con_3'] ?? '', ['OPEN', 'WAITING', 'true', 'false'], true);
                } elseif ($con_3 === 'bool') {
                    $matchCon3 = in_array($addon['con_3'] ?? '', ['true', 'false'], true);
                } elseif ($con_3 === 'none') {
                    $matchCon3 = true;
                } else {
                    $matchCon3 = ($addon['con_3'] ?? null) === $con_3;
                }
                $matchCon4 = true;
                if ($con_4 !== false) {
                    if ($con_4 === null) {
                        $matchCon4 = ($addon['con_4'] ?? null) === 'null';
                    } else {
                        $matchCon4 = ($addon['con_4'] ?? null) === $con_4;
                    }
                }
                Log::channel('gamelog')->debug("matchCon3 for $con_3 - ".$addon['con_3']." log_id=$id: ".$matchCon3);
                Log::channel('gamelog')->debug("matchCon4 for $con_4 - ".$addon['con_4']." log_id=$id: ".$matchCon4);

                if ($matchCon3 && $matchCon4) {
                    Log::channel('gamelog')->debug("FOUND Match Seach addon for log_id=$id: ".json_encode($addon));

                    return $id;
                }
            }
        }

        if (! empty($redisKeysMiss)) {
            Log::channel('gamelog')->debug('Redis MISS keys: '.implode(', ', $redisKeysMiss));
        }
        Log::channel('gamelog')->debug("Redis MISS (getLastLogId): user=$user company=$company method=$method con_3=".json_encode($con_3).' con_4='.json_encode($con_4));

        return null;
    }

    // =================== RESPONSE & BALANCE ===================

    protected function safeDecrementBalance($amount, bool $allowNegative = false)
    {
        return DB::transaction(function () use ($amount, $allowNegative) {
            $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
            if (! $allowNegative && $member->balance < $amount) {
                return false;
            }
            $member->decrement($this->balances, $amount);
            $this->member->refresh();

            return true;
        });
    }

    public function updateLogField($mainLog, $field, $value)
    {
        if (is_array($mainLog)) {
            $logId = $mainLog['id'] ?? $mainLog['_id'] ?? null;
            $user = $mainLog['game_user'] ?? null;
            $company = $mainLog['company'] ?? null;
        } elseif (is_object($mainLog)) {
            $logId = $mainLog->id ?? $mainLog->_id ?? null;
            $user = $mainLog->game_user ?? null;
            $company = $mainLog->company ?? null;
        } else {
            return false;
        }
        if (! $logId || ! $user || ! $company) {
            return false;
        }
        $addonKey = "game:log:$user:$company:addon:$logId";
        $storeValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
        $this->redis->hset($addonKey, $field, $storeValue);
        $this->redis->expire($addonKey, 86400);

        return true;
    }

    public function settleBets(Request $request)
    {
        Log::channel('gamelog')->debug('Start settlebet-----------');
        $session = $request->all();
        $param = [];
        $logOpenMap = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;
        $amount = collect($session['txns'])->sum('payoutAmount');

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'settlemain',
            'response' => 'in',
            'amount' => $amount,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $isSingleState = $txn['isSingleState'] ?? false;
            $skipBalanceUpdate = $txn['skipBalanceUpdate'] ?? false;
            $isFeature = $txn['isFeature'] ?? false;
            $isFeatureBuy = $txn['isFeatureBuy'] ?? false;
            $isEndRound = $txn['isEndRound'] ?? true;
            $ismulti = ($isFeature || $isFeatureBuy || ! $isEndRound);
            $transactionType = $txn['transactionType'] ?? 'BY_TRANSACTION';

            // 1. Handle isSingleState before settle
            if ($isSingleState) {
                if (! $skipBalanceUpdate) {

                    $existingBet = $this->getLastLogId('settled', $this->member->user_name, $session['productId'], 'OPEN', 'both', $txn['id'], $txn['roundId'], 'OPEN');

                    if ($existingBet) {
                        if ($session['productId'] === 'ASKMEBET') {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                                'balanceBefore' => (float) $oldBalance,
                                'balanceAfter' => (float) $this->member->balance,
                            ];
                        } else {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                        }

                        break;
                    }

                    //					$newBalance = $this->member->balance - $txn['betAmount'];
                    //					if ($newBalance < 0) {
                    //						$param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                    //						break;
                    //					}
                    //					$this->member->decrement($this->balances, $txn['betAmount']);

                    if (! $this->safeDecrementBalance($txn['betAmount'])) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                        break;
                    }

                }

                $openLog = $this->createGameLog([
                    'input' => $txn,
                    'output' => [],
                    'company' => $session['productId'],
                    'game_user' => $this->member->user_name,
                    'method' => 'OPEN',
                    'response' => 'in',
                    'amount' => $txn['betAmount'],
                    'con_1' => $txn['id'],
                    'con_2' => $txn['roundId'],
                    'con_3' => 'OPEN',
                    'con_4' => null,
                    'before_balance' => $oldBalance,
                    'after_balance' => $this->member->balance,
                    'date_create' => $this->now->toDateTimeString(),
                    'expireAt' => $this->expireAt,
                ]);

                $logOpenMap[$txn['roundId']][] = $openLog->id;

                //                Log::info('SETTLE SINGLE START  CREATE OPEN LOG  GET ARRAY ID in $logOpenMap', $logOpenMap);

            }

            if (! isset($logOpenMap[$txn['roundId']])) {
                Log::channel('gamelog')->debug('In ! isset $logOpenMap', [
                    'method' => $txn['status'],
                    'find' => 'con_2',
                    'con_1' => $txn['id'],
                    'con_2' => $txn['roundId'],
                ]);
                // 2. เช็ค log ว่าเคย placeBets หรือยัง

                //            Log::info('SETTLE CHECK BET BY TRANSACTION');
                if ($transactionType === 'BY_ROUND') {

                    $logs = $this->getLogIds($this->member->user_name, $session['productId'], 'ALL', 'con_2', $txn['id'], $txn['roundId'], 'ALL', null);
                    //               Log::info('SETTLE CHECK BET BY BY_ROUND THEN CHECK BET LOG', $logs);
                    if (! $logs) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                        break;
                    }

                    if (! $ismulti && ! $skipBalanceUpdate) {

                        $dupLog = $this->getLastLogId('settled', $this->member->user_name, $session['productId'], $txn['status'], 'con_2', $txn['id'], $txn['roundId'], 'false', null);

                        if ($dupLog) {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                            break;
                        }
                    }

                } else {

                    $log = $this->getLastLogId('settled', $this->member->user_name, $session['productId'], 'OPEN', 'con_1', $txn['id'], $txn['roundId'], 'OPEN', false);
                    //                Log::info('SETTLE CHECK BET BY TRANSACTION THEN CHECK BET LOG : NO CREATE THEN CHECK BY DB', $log);

                    if (! $log) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                        break;
                    }

                    if (! $skipBalanceUpdate) {

                        //                    Log::info('SETTLE CHECK BET BY TRANSACTION THEN CHECK BET LOG : NO UPDATE BALANCE');

                        $dupSettle = $this->getLastLogId('settled', $this->member->user_name, $session['productId'], $txn['status'], 'con_1', $txn['id'], $txn['roundId'], 'ALL', null);
                        //                    Log::info('SETTLE CHECK BET BY TRANSACTION THEN CHECK BET LOG : NO UPDATE BALANCE : DUP', $dupSettle);
                        if ($dupSettle) {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                            break;
                        }
                    }
                }

            }

            // 3. เติมเงิน
            if (! $skipBalanceUpdate) {
                $this->safeIncreaseBalance($txn['payoutAmount']);

            }

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            Log::channel('gamelog')->debug('settleBets getLogIds', [
                'method' => $txn['status'],
                'find' => 'con_2',
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
            ]);

            $logData = [
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $txn['payoutAmount'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $ismulti,
                'con_4' => null,
                'status' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ];

            $settleId = $this->createGameLog($logData)->id;

            $settleLogMap[$txn['roundId']] = [
                'id' => $settleId,
                'status' => $txn['status'],
            ];

            if (isset($logOpenMap[$txn['roundId']])) {

                foreach ($logOpenMap as $roundId => $openIds) {
                    if (
                        ! isset($settleLogMap[$roundId]['id']) ||
                        ! isset($settleLogMap[$roundId]['status']) ||
                        ! is_array($openIds) ||
                        empty($openIds)
                    ) {
                        continue;
                    }

                    //						$openIds = array_map(fn ($id) => new ObjectId($id), $openIds);
                    $con4Value = $settleLogMap[$roundId]['status'].'_'.$settleLogMap[$roundId]['id'];

                    $this->updateLogsAndRefreshCache($openIds, ['con_4' => $con4Value], $this->member->user_name, $session['productId']);

                }

            } else {

                $con4Value = $txn['status'].'_'.$settleId;

                if ($transactionType === 'BY_ROUND') {
                    //						$logObjectIds = array_map(fn ($id) => $id instanceof ObjectId ? $id : new ObjectId($id), $logs);

                    $this->updateLogsAndRefreshCache($logs, ['con_4' => $con4Value], $this->member->user_name, $session['productId']);

                } elseif (isset($log)) {
                    //						$logObjectId = $log instanceof ObjectId ? $log : new ObjectId($log);

                    $this->updateLogsAndRefreshCache($log, ['con_4' => $con4Value], $this->member->user_name, $session['productId']);

                }
            }

            //            LogSeamless::log(
            //                $session['productId'],
            //                $this->member->user_name,
            //                $txn,
            //                $oldBalance,
            //                $this->member->balance
            //            );
        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        //        Log::info('SETTLE  COMPLETE : END', ['log' => $mainLog]);

        return $param;
    }

    protected function getLogIds($user, $company, $method, $find, $txnid, $roundId, $con_3 = null, $con_4 = false)
    {
        $logIds = [];
        $methods = strtoupper($method) === 'ALL' ? ['OPEN', 'SETTLED', 'WAITING'] : [strtoupper($method)];
        foreach ($methods as $m) {
            $redisKey = $this->buildRedisKey($user, $company, $m, $find, $txnid, $roundId);
            $ids = $this->redis->zrevrange($redisKey, 0, -1);
            if (empty($ids)) {
                continue;
            }
            foreach ($ids as $id) {
                $addonKey = "game:log:$user:$company:addon:$id";
                $addon = $this->redis->hgetall($addonKey);
                if (empty($addon)) {
                    continue;
                }
                $matchCon3 = false;
                if ($con_3 === null) {
                    $matchCon3 = ($addon['con_3'] ?? null) === 'null';
                } elseif ($con_3 === 'ALL') {
                    $matchCon3 = in_array($addon['con_3'] ?? '', ['OPEN', 'WAITING', 'true', 'false'], true);
                } elseif ($con_3 === 'bool') {
                    $matchCon3 = in_array($addon['con_3'] ?? '', ['true', 'false'], true);
                } elseif ($con_3 === 'none') {
                    $matchCon3 = true;
                } else {
                    $matchCon3 = ($addon['con_3'] ?? null) === $con_3;
                }
                $matchCon4 = true;
                if ($con_4 !== false) {
                    if ($con_4 === null) {
                        $matchCon4 = ($addon['con_4'] ?? null) === 'null';
                    } else {
                        $matchCon4 = ($addon['con_4'] ?? null) === $con_4;
                    }
                }
                if ($matchCon3 && $matchCon4) {
                    $logIds[] = $id;
                }
            }
        }

        return array_unique($logIds);
    }

    protected function safeIncreaseBalance($amount)
    {
        return DB::transaction(function () use ($amount) {
            $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
            $member->increment($this->balances, $amount);
            $this->member->refresh();

            return true;
        });
    }

    public function unsettleBets(Request $request)
    {
        Log::channel('gamelog')->debug('Start unsettle-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $existing = $this->getLastLogId($this->member->user_name, $session['productId'], 'unsettle', 'both', $session['id'], $session['productId']);

        if ($existing) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
        }

        $totalAmount = 0;
        foreach ($session['txns'] as $txn) {
            $totalAmount += $txn['payoutAmount'];
        }

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'unsettle',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {

            $logDup = $this->getLastLogId($this->member->user_name, $session['productId'], 'unsettlesub', 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

            if ($logDup) {
                return $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
            }

            if ($txn['betAmount'] > 0) {
                $this->safeDecrementBalance($txn['betAmount'], true);
                $method = 'betsub';
                $amount = $txn['betAmount'];
            } else {

                $settledLog = $this->getLastLogId($this->member->user_name, $session['productId'], 'paysub', 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

                if (! $settledLog) {
                    return $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                }

                $this->safeDecrementBalance($txn['payoutAmount'], true);
                $method = 'unsettlesub';
                $amount = $txn['payoutAmount'];
            }

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $this->game,
                'game_user' => $this->member->user_name,
                'method' => $method,
                'response' => 'in',
                'amount' => $amount,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            if (isset($settledLog)) {

                GameLogProxy::where('_id', $settledLog)
                    ->update(['con_4' => 'unsettle_'.$logId]);

                GameLogProxy::where('con_4', 'settle_'.$settledLog->_id)
                    ->update(['con_4' => null]);
            }

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function adjustBets(Request $request)
    {
        Log::channel('gamelog')->debug('Start adjust-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'adjustbetmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {

            $log = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

            if (! $log) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            $addon = $this->getAddon($this->member->user_name, $session['productId'], $log);
            $oldBetAmount = $addon['amount'] ?? 0;
            $newBetAmount = $txn['betAmount'];

            // Adjust balance atomically
            DB::transaction(function () use ($oldBetAmount, $newBetAmount) {
                $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
                $member->increment($this->balances, $oldBetAmount);
                $member->decrement($this->balances, $newBetAmount);
                $this->member->refresh();
            });

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $txn['betAmount'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            //				$logObjectId = $log instanceof ObjectId ? $log : new ObjectId($log);
            $this->updateLogsAndRefreshCache($log, ['con_4' => 'ADJUSTBET_'.$logId], $this->member->user_name, $session['productId']);

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    protected function getAddon($user, $company, $find)
    {
        $key = "game:log:$user:$company:addon:$find";
        $addon = $this->redis->hgetall($key);
        if (! empty($addon)) {
            return [
                'amount' => (float) $addon['amount'] ?? 0,
                'method' => $addon['method'] ?? '',
                'con_1' => $addon['con_1'] ?? '',
                'con_2' => $addon['con_2'] ?? '',
                'con_3' => $addon['con_3'] ?? '',
                'con_4' => $addon['con_4'] ?? '',
                'created_at' => $addon['created_at'] ?? '',
            ];
        }

        return null;
    }

    public function cancelBets(Request $request)
    {
        Log::channel('gamelog')->debug('Start CancelBets-----------');
        $session = $request->all();
        $param = [];
        $isArray = false;

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'cancelmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $exists = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], 'none', null);

            if ($exists) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $logMethod = ($txn['status'] === 'REJECT') ? 'WAITING' : 'OPEN';

            if ($txn['transactionType'] === 'BY_ROUND') {
                $logs = $this->getLogIds($this->member->user_name, $session['productId'], $logMethod, 'con_2', $txn['id'], $txn['roundId'], $logMethod, null);

                if (! $logs) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                $sumAmount = 0;
                foreach ($logs as $log) {
                    $addon = $this->getAddon($this->member->user_name, $session['productId'], $log);
                    $sumAmount += $addon['amount'];
                }

                $betAmount = $sumAmount;
                $isArray = true;

            } else {
                $log = $this->getLastLogId($this->member->user_name, $session['productId'], $logMethod, 'con_1', $txn['id'], $txn['roundId'], 'none', null);

                if (! $log) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                $addon = $this->getAddon($this->member->user_name, $session['productId'], $log);

                $betAmount = $addon['amount'];
            }

            if ($txn['betAmount'] > $betAmount) {
                $this->safeDecrementBalance($betAmount, true);
                //                $this->member->decrement($this->balances, $betAmount);
            }

            $this->safeIncreaseBalance($txn['betAmount']);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $txn['betAmount'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => false,
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            if ($isArray) {
                //					$logObjectIds = array_map(fn ($id) => $id instanceof ObjectId ? $id : new ObjectId($id), $logs);

                $this->updateLogsAndRefreshCache($logs, ['con_4' => $txn['status'].'_'.$logId], $this->member->user_name, $session['productId']);
            } else {
                //					$logObjectId = $log instanceof ObjectId ? $log : new ObjectId($log);

                $this->updateLogsAndRefreshCache($log, ['con_4' => $txn['status'].'_'.$logId], $this->member->user_name, $session['productId']);

            }

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function rollback(Request $request)
    {
        Log::channel('gamelog')->debug('Start rollback-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'rollbackmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {

            // 1. หา log ที่ rollback นี้ซ้ำหรือยัง
            $isDup = $this->getLastLogId(
                $this->member->user_name,
                $session['productId'],
                $txn['status'],
                'both',
                $txn['id'],
                $txn['roundId'],
                'false',
                null
            );
            if ($isDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            // 2. หา log REFUND, SETTLED ที่เกี่ยวข้อง เพื่อใช้ rollback
            $methods = ['REFUND', 'SETTLED'];
            $logItems = [];
            foreach ($methods as $method) {
                $id = $this->getLastLogId(
                    $this->member->user_name,
                    $session['productId'],
                    $method,
                    $txn['transactionType'] === 'BY_ROUND' ? 'con_2' : 'con_1',
                    $txn['id'],
                    $txn['roundId'],
                    'false',
                    null
                );
                if ($id) {
                    $logItems[] = [
                        'method' => $method,
                        'id' => $id,
                    ];
                }
            }

            if (empty($logItems)) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            // 3. เลือก log ที่เวลาล่าสุด
            $latest = $this->getLatestAddonFromLogItems($logItems, $this->member->user_name, $session['productId']);
            if (! $latest) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            // 4. คำนวณยอด rollback
            $rollbackAmount = $latest['method'] === 'SETTLED' ? $txn['payoutAmount'] : $txn['betAmount'];
            $this->safeDecrementBalanceMinus($rollbackAmount);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $rollbackAmount,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => false,
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])['id'];

            // 5. อัปเดต con_4 ของ log REFUND/SETTLED ที่ถูก rollback
            $this->updateLogsAndRefreshCache(
                $latest['id'],
                ['con_4' => $txn['status'].'_'.$logId],
                $this->member->user_name,
                $session['productId']
            );

            // 6. หา log OPEN/WAITING ที่ผูก con_4 กับ REFUND/SETTLED นี้ และ clear con_4 = null
            $openLogIds = [];
            foreach (['OPEN', 'WAITING'] as $openMethod) {
                $candidateIds = $this->getLogIds(
                    $this->member->user_name,
                    $session['productId'],
                    $openMethod,
                    'both',
                    $txn['id'],
                    $txn['roundId'],
                    null,
                    $latest['method'].'_'.$latest['id']
                );
                foreach ($candidateIds as $oid) {
                    $openLogIds[] = $oid;
                }
            }
            foreach ($openLogIds as $openLogId) {
                $this->updateLogAddonField(
                    $this->member->user_name,
                    $session['productId'],
                    $openLogId,
                    'con_4',
                    'null'
                );
            }
        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function winRewards(Request $request)
    {
        Log::channel('gamelog')->debug('Start winreward-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'winrewardmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $logDup = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], 'false', null);

            if ($logDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $payout = $txn['payoutAmount'] ?? 0;

            $this->safeIncreaseBalance($payout);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $payout,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => false,
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ]);

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function voidSettled(Request $request)
    {
        Log::channel('gamelog')->debug('Start void settle-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'voidsettledmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $duplicate = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

            if ($duplicate) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($txn['transactionType'] === 'BY_ROUND') {
                $settledLog = $this->getLastLogId($this->member->user_name, $session['productId'], 'SETTLED', 'con_2', $txn['id'], $txn['roundId'], 'bool', null);

            } else {

                $settledLog = $this->getLastLogId($this->member->user_name, $session['productId'], 'SETTLED', 'con_1', $txn['id'], $txn['roundId'], 'bool', null);

            }

            if (! $settledLog) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            $this->safeIncreaseBalance($txn['betAmount']);

            $payout = $txn['payoutAmount'];

            $this->safeDecrementBalance($payout, true);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $txn['betAmount'] - $payout,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            //				$logObjectId = $settledLog instanceof ObjectId ? $settledLog : new ObjectId($settledLog);
            $this->updateLogsAndRefreshCache($settledLog, ['con_4' => $txn['status'].'_'.$logId], $this->member->user_name, $session['productId']);

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function placeTips(Request $request)
    {
        Log::channel('gamelog')->debug('Start tip-----------');
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'placetipmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $tipDup = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

            if ($tipDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $amount = $txn['betAmount'] ?? 0;
            $skipUpdate = $txn['skipBalanceUpdate'] ?? false;

            if (! $skipUpdate) {

                $this->safeDecrementBalance($amount, true);

            }

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $amount,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ]);

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function cancelTips(Request $request)
    {
        Log::channel('gamelog')->debug('Start canceltip-----------');
        $session = $request->all();
        $param = [];
        $isArray = false;

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'canceltipmain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $txn) {
            $exists = $this->getLastLogId($this->member->user_name, $session['productId'], $txn['status'], 'both', $txn['id'], $txn['roundId'], $txn['status'], null);

            if ($exists) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $checkTip = $this->getLastLogId($this->member->user_name, $session['productId'], 'TIPS', 'both', $txn['id'], $txn['roundId'], 'TIPS', null);

            if (! $checkTip) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            //            $newBalance = $this->member->balance - $txn['betAmount'];
            //
            //            if ($newBalance < 0) {
            //                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
            //                break;
            //            }

            $this->safeIncreaseBalance($txn['betAmount']);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $txn['status'],
                'response' => 'in',
                'amount' => $txn['betAmount'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ]);

        }

        $this->gameLogRedis->updateLogField($mainLog, 'output', $param);

        return $param;
    }

    public function adjustBalance(Request $request)
    {
        Log::channel('gamelog')->debug('Start aj balance-----------');
        $param = [];
        $session = $request->all();

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        // log หลัก
        $log = [
            'input' => $session,
            'output' => $param,
            'company' => $session['productId'],
            'game_user' => $this->member->user_name,
            'method' => 'adjustbalancemain',
            'response' => 'in',
            'amount' => 0,
            'con_1' => $session['id'],
            'con_2' => $session['productId'],
            'con_3' => null,
            'con_4' => null,
            'before_balance' => $oldBalance,
            'after_balance' => $this->member->balance,
            'date_create' => $this->now->toDateTimeString(),
            'expireAt' => $this->expireAt,
        ];

        $mainLog = $this->createGameLogMain($log);

        foreach ($session['txns'] as $item) {
            $checkDup = $this->getLastLogId(
                $this->member->user_name,
                $session['productId'],
                'ADJUSTBALANCE',
                'both',
                $item['refId'],
                $item['refId'],
                $item['status'],
                null
            );

            if ($checkDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($item['status'] === 'DEBIT') {
                $this->safeDecrementBalance($item['amount'], true);
            } else {
                $this->safeIncreaseBalance($item['amount']);
            }

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => 'THB',
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
                'timestampMillis' => $this->now->getTimestampMs(),
            ];

            // เก็บ log ทั้ง 2 method ลง Redis
            foreach (['ADJUSTBALANCE', 'OPEN'] as $method) {
                $session_in = [];
                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['response'] = 'in';
                $session_in['method'] = $method;
                $session_in['amount'] = $item['amount'];
                $session_in['con_1'] = $item['refId'];
                $session_in['con_2'] = $item['refId'];
                $session_in['con_3'] = $item['status'];
                $session_in['before_balance'] = $oldBalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = $this->now->toDateTimeString();
                $session_in['expireAt'] = $this->expireAt;
                $this->createGameLog($session_in);
            }
        }

        $this->gameLogRedis->updateLogField($mainLog,'output', $param);

        return $param;
    }
}
