<?php

namespace Gametech\API\Http\Controllers;

use Gametech\API\Models\GameLogProxy;
use Gametech\API\Traits\LogSeamless;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\UTCDateTime;

class NewCommonFlowController extends AppBaseController
{
    use LogSeamless;

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
        Request $request
    ) {
        $this->_config = $request->input('_config');
        $this->middleware('api');
        $this->repository = $repository;
        $this->memberRepository = $memberRepo;
        $this->gameUserRepository = $gameUserRepo;
        $this->request = $request;

        $this->now = now();

        // 🔎 ตรวจสอบค่า session['productId']
        $productId = session('productId'); // หรือจะใช้ $request->session()->get('productId')
        if (in_array($productId, ['UMBET', 'LALIKA', 'AFB1188', 'VIRTUAL_SPORT', 'COCKFIGHT', 'AMBSPORTBOOK', 'SABASPORTS', 'SBO', 'AOG', 'FB_SPORT', 'DB SPORTS'])) {
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
            'wallet_transaction' => $this->resolveWalletTransactionId((string) $id),
            'timestampMillis' => $this->now->getTimestampMs(),
        ];
    }

    protected function resolveWalletTransactionId(string $fallback): string
    {
        $txns = $this->request->input('txns');
        if (is_array($txns) && isset($txns[0]) && is_array($txns[0])) {
            $txnId = (string) ($txns[0]['id'] ?? '');
            if ($txnId !== '') {
                return $txnId;
            }
        }

        $singleTxnId = (string) $this->request->input('txn.id', $this->request->input('txnId', ''));
        if ($singleTxnId !== '') {
            return $singleTxnId;
        }

        return $fallback;
    }

    protected function createGameLog(array $data)
    {
        return GameLogProxy::create($data);
    }

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

    public function placeBets(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $txnDup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->exists();

            if ($txnDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($txn['status'] === 'OPEN') {
                $waitingExists = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'WAITING')
                    ->where('con_1', $txn['id'])
                    ->where('con_2', $txn['roundId'])
                    ->exists();

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

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function settleBets(Request $request)
    {
        $session = $request->all();
        $param = [];

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

        $mainLog = $this->createGameLog($log);

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
                    $existingBet = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'OPEN')
                        ->where('con_1', $txn['id'])
                        ->where('con_2', $txn['roundId'])
                        ->first();

                    if ($existingBet) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                        break;
                    }

                    if (! $this->safeDecrementBalance($txn['betAmount'])) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                        break;
                    }
                }

                $this->createGameLog([
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
            }

            // 2. เช็ค log ว่าเคย placeBets หรือยัง
            if ($transactionType === 'BY_ROUND') {
                $logs = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('con_2', $txn['roundId'])
                    ->whereNull('con_4')
                    ->get();

                if ($logs->isEmpty()) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                if (! $ismulti && ! $skipBalanceUpdate) {
                    $dupLog = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $txn['status'])
                        ->where('con_2', $txn['roundId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($dupLog && $dupLog['con_3'] === false) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                        break;
                    }
                }
            } else {
                $log = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'OPEN')
                    ->where('con_1', $txn['id'])
                    ->latest('created_at')
                    ->first();

                if (! $log) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                if (! $skipBalanceUpdate) {
                    $dupSettle = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $txn['status'])
                        ->where('con_1', $txn['id'])
                        ->whereNull('con_4')
                        ->exists();

                    if ($dupSettle) {
                        $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                        break;
                    }
                }
            }

            // 3. เติมเงิน
            if (! $skipBalanceUpdate) {
                $this->member->increment($this->balances, $txn['payoutAmount']);
            }

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
            ];

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

            // 4. อัปเดต con_4 ของ log ที่เกี่ยวข้อง
            if ($transactionType === 'BY_ROUND') {
                foreach ($logs as $log) {
                    $log->con_4 = $txn['status'].'_'.$settleId;
                    $log->save();
                }
            } elseif (isset($log)) {
                $log->con_4 = $txn['status'].'_'.$settleId;
                $log->save();
            }

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function unsettleBets(Request $request)
    {
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

        $existing = GameLogProxy::where('company', $this->game)
            ->where('response', 'in')
            ->where('game_user', $this->member->user_name)
            ->where('method', 'unsettle')
            ->where('con_1', $session['id'])
            ->where('con_2', $session['productId'])
            ->whereNull('con_3')
            ->whereNull('con_4')
            ->latest('created_at')->first();

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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $logDup = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'unsettlesub')
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->whereNull('con_4')
                ->latest('created_at')->first();

            if ($logDup) {
                return $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
            }

            if ($txn['betAmount'] > 0) {
                $this->safeDecrementBalance($txn['betAmount'], true);
                $method = 'betsub';
                $amount = $txn['betAmount'];
            } else {
                $settledLog = GameLogProxy::where('company', $this->game)
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'paysub')
                    ->where('con_1', $txn['id'])
                    ->where('con_2', $txn['roundId'])
                    ->where('con_3', $txn['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')->first();

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
                $settledLog->con_4 = 'unsettle_'.$logId;
                $settledLog->save();

                GameLogProxy::where('con_4', 'settle_'.$settledLog->_id)
                    ->update(['con_4' => null]);
            }

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        return $param;
    }

    public function adjustBets(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $log = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->latest('created_at')
                ->first();

            if (! $log) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            $isAdjusted = DB::transaction(function () use ($log, $txn) {
                $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
                $balanceAfterRestore = $member->{$this->balances} + $log->amount;
                if ($balanceAfterRestore < $txn['betAmount']) {
                    return false;
                }

                $member->{$this->balances} = $balanceAfterRestore - $txn['betAmount'];
                $member->save();
                $this->member->refresh();

                return true;
            });

            if (! $isAdjusted) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                break;
            }

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

            $log->con_4 = 'ADJUSTBET_'.$logId;
            $log->save();

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function cancelBets(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $exists = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->whereNull('con_4')
                ->exists();

            if ($exists) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $logMethod = ($txn['status'] === 'REJECT') ? 'WAITING' : 'OPEN';

            if ($txn['transactionType'] === 'BY_ROUND') {
                $logs = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $logMethod)
                    ->where('con_2', $txn['roundId'])
                    ->get();

                if ($logs->isEmpty()) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                $betAmount = $logs->sum('amount');
                $isArray = true;

            } else {
                $logs = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $logMethod)
                    ->where('con_1', $txn['id'])
                    ->latest('created_at')->limit(1)->get();

                if ($logs->isEmpty()) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }

                $betAmount = $logs[0]->amount;
            }

            if ($txn['betAmount'] > $betAmount) {
                $this->safeDecrementBalance($betAmount, true);
            }

            $this->member->increment($this->balances, $txn['betAmount']);

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

            if ($isArray) {
                foreach ($logs as $log) {
                    $log->con_4 = $txn['status'].'_'.$logId;
                    $log->save();
                }
            } else {
                $logs[0]->con_4 = $txn['status'].'_'.$logId;
                $logs[0]->save();
            }

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function rollback(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            if ($txn['transactionType'] === 'BY_ROUND') {
                $isDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $txn['status'])
                    ->where('con_1', $txn['id'])
                    ->where('con_2', $txn['roundId'])
                    ->where('con_3', $txn['status'])
                    ->whereNull('con_4')
                    ->exists();

                if ($isDup) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                    break;
                }

                $log = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->whereIn('method', ['REFUND', 'SETTLED'])
                    ->where('con_2', $txn['roundId'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if (! $log) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                    break;
                }
            } else {
                $log = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->whereIn('method', ['REFUND', 'SETTLED'])
                    ->where('con_1', $txn['id'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if (! $log) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                    break;
                }
            }

            $rollbackAmount = $log->method === 'SETTLED' ? $txn['payoutAmount'] : $txn['betAmount'];

            $this->safeDecrementBalance($rollbackAmount, true);

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
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            $log->con_4 = $txn['status'].'_'.$logId;
            $log->save();

            GameLogProxy::where('con_4', $log->method.'_'.$log->id)
                ->whereIn('method', ['WAITING', 'OPEN'])
                ->where('company', $session['productId'])
                ->where('game_user', $this->member->user_name)
                ->update(['con_4' => null]);

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function winRewards(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $logDup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->whereNull('con_4')
                ->exists();

            if ($logDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $payout = $txn['payoutAmount'] ?? 0;

            $this->member->increment($this->balances, $payout);

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
                'con_3' => $txn['status'],
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ]);

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function voidSettled(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $duplicate = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->whereNull('con_4')
                ->exists();

            if ($duplicate) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($txn['transactionType'] === 'BY_ROUND') {
                $settledLog = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'SETTLED')
                    ->where('con_2', $txn['roundId'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();
            } else {

                $settledLog = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'SETTLED')
                    ->where('con_1', $txn['id'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

            }

            if (! $settledLog) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            $payout = $txn['payoutAmount'];
            $isVoided = DB::transaction(function () use ($txn, $payout) {
                $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
                $balanceAfterCredit = $member->{$this->balances} + $txn['betAmount'];
                if ($balanceAfterCredit < $payout) {
                    return false;
                }

                $member->{$this->balances} = $balanceAfterCredit - $payout;
                $member->save();
                $this->member->refresh();

                return true;
            });

            if (! $isVoided) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                break;
            }

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

            $settledLog->con_4 = $txn['status'].'_'.$logId;
            $settledLog->save();

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function placeTips(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $tipDup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->exists();

            if ($tipDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $amount = $txn['betAmount'] ?? 0;
            $skipUpdate = $txn['skipBalanceUpdate'] ?? false;

            if (! $skipUpdate) {
                if (! $this->safeDecrementBalance($amount)) {
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

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function cancelTips(Request $request)
    {
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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $txn) {
            $exists = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $txn['status'])
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $txn['status'])
                ->whereNull('con_4')
                ->exists();

            if ($exists) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $dup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'TIPS')
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->whereNull('con_4')
                ->doesntExist();

            if ($dup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20001, $this->member->balance);
                break;
            }

            $newBalance = $this->member->balance - $txn['betAmount'];

            if ($newBalance < 0) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                break;
            }

            $this->member->increment($this->balances, $txn['betAmount']);

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

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $txn,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }

    public function adjustBalance(Request $request)
    {

        $param = [];
        $amount = 0;
        $session = $request->all();

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], 10001);
        }

        $oldBalance = $this->member->balance;

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

        $mainLog = $this->createGameLog($log);

        foreach ($session['txns'] as $item) {
            $checkDup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'ADJUSTBALANCE')
                ->where('con_1', $item['refId'])
                ->where('con_2', $item['refId'])
                ->where('con_3', $item['status'])
                ->whereNull('con_4')
                ->exists();

            if ($checkDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            if ($item['status'] === 'DEBIT') {
                if (! $this->safeDecrementBalance($item['amount'])) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                    break;
                }
            } else {
                $this->member->increment($this->balances, $item['amount']);
            }

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => 'THB',
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'wallet_transaction' => $this->resolveWalletTransactionId((string) $session['id']),
                'balanceBefore' => (float) $oldBalance,
                'balanceAfter' => (float) $this->member->balance,
                'timestampMillis' => $this->now->getTimestampMs(),
            ];

            foreach (['ADJUSTBALANCE', 'OPEN'] as $method) {
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
                GameLogProxy::create($session_in);
            }

            LogSeamless::log(
                $session['productId'],
                $this->member->user_name,
                $item,
                $oldBalance,
                $this->member->balance
            );
        }

        $mainLog->output = $param;
        $mainLog->save();

        return $param;
    }
}
