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

    protected $memberLookupStatus = 10001;

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

        if (app()->runningInConsole()) {
            return;
        }

        // 🔎 ตรวจสอบค่า session['productId']
        $productId = session('productId'); // หรือจะใช้ $request->session()->get('productId')
        if (in_array($productId, ['UMBET', 'LALIKA', 'AFB1188', 'VIRTUAL_SPORT', 'COCKFIGHT', 'AMBSPORTBOOK', 'SABASPORTS', 'SBO', 'AOG', 'FB_SPORT', 'DB SPORTS'])) {
            $this->days = 7;
        }

        $this->expireAt = new UTCDateTime($this->now->copy()->addDays($this->days));

        $username = $request->input('username');
        $token = $request->input('token', $request->input('sessionToken'));
        $token = is_scalar($token) ? trim((string) $token) : '';
        $hasToken = $token !== '';

        $member = MemberProxy::without('bank')
            ->where('user_name', $username)
            ->where('enable', 'Y')
            ->first();

        if (! $member) {
            $this->memberLookupStatus = 10001;
            $this->member = null;

            return;
        }

        if ($hasToken) {
            $sessionId = is_scalar($member->session_id) ? trim((string) $member->session_id) : '';
            if ($sessionId !== $token) {
                $this->memberLookupStatus = 30001;
                $this->member = null;

                return;
            }
        }

        $this->memberLookupStatus = 10001;
        $this->member = $member;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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

    protected function memberLookupStatusCode(): int
    {
        return (int) $this->memberLookupStatus;
    }

    protected function createGameLog(array $data)
    {
        if (! array_key_exists('wallet_transaction', $data)) {
            $walletTxn = $this->resolveWalletTransactionForLog($data);
            if ($walletTxn !== null) {
                $data['wallet_transaction'] = $walletTxn;
            }
        }

        return GameLogProxy::create($data);
    }

    protected function resolveWalletTransactionForLog(array $data): ?string
    {
        $input = $data['input'] ?? null;
        if (is_array($input)) {
            if (isset($input['id']) && $input['id'] !== '') {
                return (string) $input['id'];
            }

            if (isset($input['txn']) && is_array($input['txn']) && isset($input['txn']['id']) && $input['txn']['id'] !== '') {
                return (string) $input['txn']['id'];
            }

            if (isset($input['txns']) && is_array($input['txns']) && isset($input['txns'][0]) && is_array($input['txns'][0]) && isset($input['txns'][0]['id']) && $input['txns'][0]['id'] !== '') {
                return (string) $input['txns'][0]['id'];
            }
        }

        return null;
    }

    protected function safeDecrementBalance($amount, bool $allowNegative = false, array $walletTxn = [])
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            return true;
        }

        return DB::transaction(function () use ($amount, $allowNegative, $walletTxn) {
            $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
            $balanceBefore = (float) $member->{$this->balances};
            if (! $allowNegative && $member->balance < $amount) {
                return false;
            }

            $member->{$this->balances} = $balanceBefore - $amount;
            $member->save();

            $this->recordWalletTransaction(
                walletTxn: $walletTxn,
                direction: 'DEBIT',
                amount: $amount,
                balanceBefore: $balanceBefore,
                balanceAfter: (float) $member->{$this->balances}
            );

            $this->member->refresh();

            return true;
        });
    }

    protected function safeIncrementBalance($amount, array $walletTxn = []): bool
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            return true;
        }

        return DB::transaction(function () use ($amount, $walletTxn) {
            $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
            $balanceBefore = (float) $member->{$this->balances};
            $member->{$this->balances} = $balanceBefore + $amount;
            $member->save();

            $this->recordWalletTransaction(
                walletTxn: $walletTxn,
                direction: 'CREDIT',
                amount: $amount,
                balanceBefore: $balanceBefore,
                balanceAfter: (float) $member->{$this->balances}
            );

            $this->member->refresh();

            return true;
        });
    }

    protected function recordWalletTransaction(array $walletTxn, string $direction, float $amount, float $balanceBefore, float $balanceAfter): void
    {
        if ($amount <= 0) {
            return;
        }

        $txnIdFromRequest = $this->resolveTxnIdFromRequest();
        $roundIdFromRequest = $this->resolveRoundIdFromRequest();
        $requestId = (string) $this->request->input('id', '');

        $refType = (string) ($walletTxn['ref_type'] ?? 'GAME_PROVIDER');
        $refCode = $requestId !== '' ? $requestId : trim((string) ($walletTxn['ref_code'] ?? ''));
        if ($refCode === '') {
            $refCode = $txnIdFromRequest !== '' ? $txnIdFromRequest : (string) ($this->request->input('id') ?? now()->format('YmdHisv'));
        }

        $exists = DB::table('wallet_transactions')
            ->where('member_id', (int) $this->member->code)
            ->where('direction', $direction)
            ->where('ref_type', $refType)
            ->where('ref_code', $refCode)
            ->exists();

        if ($exists) {
            return;
        }

        $description = (string) ($walletTxn['description'] ?? 'Game provider wallet transaction');
        $meta = (array) ($walletTxn['meta'] ?? []);
        $meta['txn_id'] = (string) ($meta['txn_id'] ?? $txnIdFromRequest);
        $meta['round_id'] = (string) ($meta['round_id'] ?? $roundIdFromRequest);
        $meta['request_id'] = (string) ($meta['request_id'] ?? $this->request->input('id', ''));
        $refId = isset($walletTxn['ref_id']) && is_numeric($walletTxn['ref_id']) ? (int) $walletTxn['ref_id'] : null;
        $groupCode = (string) ($walletTxn['group_code'] ?? ($refType.'_'.$refCode));

        $insert = [
            'member_id' => (int) $this->member->code,
            'scope' => 'MEMBER',
            'game_user_id' => null,
            'direction' => $direction,
            'amount' => number_format($amount, 2, '.', ''),
            'balance_before' => number_format($balanceBefore, 2, '.', ''),
            'balance_after' => number_format($balanceAfter, 2, '.', ''),
            'ref_type' => $refType,
            'ref_id' => $refId,
            'ref_code' => $refCode,
            'group_code' => $groupCode,
            'related_txn_id' => null,
            'status' => 'SUCCESS',
            'description' => $description,
            'meta' => empty($meta) ? null : json_encode($meta, JSON_UNESCAPED_UNICODE),
            'created_by_type' => 'system',
            'created_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $insert['provider_txn_id'] = $txnIdFromRequest !== '' ? $txnIdFromRequest : null;
        $insert['provider_round_id'] = $roundIdFromRequest !== '' ? $roundIdFromRequest : null;

        DB::table('wallet_transactions')->insert($insert);
    }

    protected function resolveTxnIdFromRequest(): string
    {
        $txns = $this->request->input('txns');
        if (is_array($txns) && isset($txns[0]) && is_array($txns[0]) && ! empty($txns[0]['id'])) {
            return (string) $txns[0]['id'];
        }

        return '';
    }

    protected function resolveRoundIdFromRequest(): string
    {
        $txns = $this->request->input('txns');
        if (is_array($txns) && isset($txns[0]) && is_array($txns[0]) && ! empty($txns[0]['roundId'])) {
            return (string) $txns[0]['roundId'];
        }

        return '';
    }

    public function placeBets(Request $request)
    {
        $session = $request->all();
        $param = [];

        if (! $this->member) {
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                if (! $this->safeDecrementBalance($betAmount, false, [
                    'ref_type' => 'GAME_BET',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_BET_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider bet debit',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ])) {
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                        if ($session['productId'] === 'PGSOFT') {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                                    'balanceBefore' => (float) $oldBalance,
                                    'balanceAfter' => (float) $this->member->balance,
                                ];
                        } else {
                            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                        }

                        break;
                    }

                    if (! $this->safeDecrementBalance($txn['betAmount'], false, [
                        'ref_type' => 'GAME_OPEN',
                        'ref_code' => (string) ($txn['id'] ?? $session['id']),
                        'group_code' => 'GAME_OPEN_'.(string) ($txn['roundId'] ?? $session['id']),
                        'description' => 'Provider open debit',
                        'meta' => [
                            'product_id' => $session['productId'] ?? null,
                            'round_id' => $txn['roundId'] ?? null,
                            'status' => $txn['status'] ?? null,
                        ],
                    ])) {
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
                $this->safeIncrementBalance($txn['payoutAmount'], [
                    'ref_type' => 'GAME_SETTLE',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_SETTLE_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider settle credit',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ]);
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                $this->safeDecrementBalance($txn['betAmount'], true, [
                    'ref_type' => 'GAME_UNSETTLE',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_UNSETTLE_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider unsettled debit (bet)',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ]);
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

                $this->safeDecrementBalance($txn['payoutAmount'], true, [
                    'ref_type' => 'GAME_UNSETTLE',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_UNSETTLE_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider unsettled debit (payout)',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ]);
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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

            $adjustResult = DB::transaction(function () use ($log, $txn) {
                $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
                $balanceBefore = (float) $member->{$this->balances};
                $balanceAfterRestore = $member->{$this->balances} + $log->amount;
                if ($balanceAfterRestore < $txn['betAmount']) {
                    return [
                        'success' => false,
                        'before' => $balanceBefore,
                        'after' => $balanceBefore,
                    ];
                }

                $member->{$this->balances} = $balanceAfterRestore - $txn['betAmount'];
                $member->save();
                $this->member->refresh();

                return [
                    'success' => true,
                    'before' => $balanceBefore,
                    'after' => (float) $member->{$this->balances},
                ];
            });

            if (! ($adjustResult['success'] ?? false)) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                break;
            }

            $adjustAmount = abs(((float) ($adjustResult['after'] ?? 0)) - ((float) ($adjustResult['before'] ?? 0)));
            if ($adjustAmount > 0) {
                $this->recordWalletTransaction(
                    walletTxn: [
                        'ref_type' => 'GAME_ADJUST_BET',
                        'ref_code' => (string) ($txn['id'] ?? $session['id']),
                        'group_code' => 'GAME_ADJUST_BET_'.(string) ($txn['roundId'] ?? $session['id']),
                        'description' => 'Provider adjust bet',
                        'meta' => [
                            'product_id' => $session['productId'] ?? null,
                            'round_id' => $txn['roundId'] ?? null,
                            'status' => $txn['status'] ?? null,
                        ],
                    ],
                    direction: ((float) ($adjustResult['after'] ?? 0)) >= ((float) ($adjustResult['before'] ?? 0)) ? 'CREDIT' : 'DEBIT',
                    amount: $adjustAmount,
                    balanceBefore: (float) ($adjustResult['before'] ?? 0),
                    balanceAfter: (float) ($adjustResult['after'] ?? 0),
                );
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                $this->safeDecrementBalance($betAmount, true, [
                    'ref_type' => 'GAME_CANCEL',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_CANCEL_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider cancel debit correction',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ]);
            }

            $this->safeIncrementBalance($txn['betAmount'], [
                'ref_type' => 'GAME_CANCEL',
                'ref_code' => (string) ($txn['id'] ?? $session['id']),
                'group_code' => 'GAME_CANCEL_'.(string) ($txn['roundId'] ?? $session['id']),
                'description' => 'Provider cancel credit',
                'meta' => [
                    'product_id' => $session['productId'] ?? null,
                    'round_id' => $txn['roundId'] ?? null,
                    'status' => $txn['status'] ?? null,
                ],
            ]);

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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
            $status = strtoupper((string) ($txn['status'] ?? 'ROLLBACK'));

            if ($txn['transactionType'] === 'BY_ROUND') {
                $isDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $status)
                    ->where('con_1', $txn['id'])
                    ->where('con_2', $txn['roundId'])
                    ->where('con_3', $status)
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

            $this->safeDecrementBalance($rollbackAmount, true, [
                'ref_type' => 'GAME_ROLLBACK',
                'ref_code' => (string) ($txn['id'] ?? $session['id']),
                'group_code' => 'GAME_ROLLBACK_'.(string) ($txn['roundId'] ?? $session['id']),
                'description' => 'Provider rollback debit',
                'meta' => [
                    'product_id' => $session['productId'] ?? null,
                    'round_id' => $txn['roundId'] ?? null,
                    'status' => $status,
                ],
            ]);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                    'balanceBefore' => (float) $oldBalance,
                    'balanceAfter' => (float) $this->member->balance,
                ];

            $logId = $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $status,
                'response' => 'in',
                'amount' => $rollbackAmount,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $status,
                'con_4' => null,
                'before_balance' => $oldBalance,
                'after_balance' => $this->member->balance,
                'date_create' => $this->now->toDateTimeString(),
                'expireAt' => $this->expireAt,
            ])->id;

            $log->con_4 = $status.'_'.$logId;
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
            $status = strtoupper((string) ($txn['status'] ?? 'SETTLED'));

            $logDup = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $status)
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $status)
                ->whereNull('con_4')
                ->exists();

            if ($logDup) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 20002, $this->member->balance);
                break;
            }

            $payout = $txn['payoutAmount'] ?? 0;

            $this->safeIncrementBalance($payout, [
                'ref_type' => 'GAME_WIN_REWARD',
                'ref_code' => (string) ($txn['id'] ?? $session['id']),
                'group_code' => 'GAME_WIN_REWARD_'.(string) ($txn['roundId'] ?? $session['id']),
                'description' => 'Provider win reward credit',
                'meta' => [
                    'product_id' => $session['productId'] ?? null,
                    'round_id' => $txn['roundId'] ?? null,
                    'status' => $status,
                ],
            ]);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                    'balanceBefore' => (float) $oldBalance,
                    'balanceAfter' => (float) $this->member->balance,
                ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $status,
                'response' => 'in',
                'amount' => $payout,
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $status,
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
            $voidResult = DB::transaction(function () use ($txn, $payout) {
                $member = MemberProxy::where('code', $this->member->code)->lockForUpdate()->first();
                $balanceBefore = (float) $member->{$this->balances};
                $balanceAfterCredit = $member->{$this->balances} + $txn['betAmount'];
                if ($balanceAfterCredit < $payout) {
                    return [
                        'success' => false,
                        'before' => $balanceBefore,
                        'after' => $balanceBefore,
                    ];
                }

                $member->{$this->balances} = $balanceAfterCredit - $payout;
                $member->save();
                $this->member->refresh();

                return [
                    'success' => true,
                    'before' => $balanceBefore,
                    'after' => (float) $member->{$this->balances},
                ];
            });

            if (! ($voidResult['success'] ?? false)) {
                $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                break;
            }

            $voidAmount = abs(((float) ($voidResult['after'] ?? 0)) - ((float) ($voidResult['before'] ?? 0)));
            if ($voidAmount > 0) {
                $this->recordWalletTransaction(
                    walletTxn: [
                        'ref_type' => 'GAME_VOID_SETTLED',
                        'ref_code' => (string) ($txn['id'] ?? $session['id']),
                        'group_code' => 'GAME_VOID_SETTLED_'.(string) ($txn['roundId'] ?? $session['id']),
                        'description' => 'Provider void settled',
                        'meta' => [
                            'product_id' => $session['productId'] ?? null,
                            'round_id' => $txn['roundId'] ?? null,
                            'status' => $txn['status'] ?? null,
                        ],
                    ],
                    direction: ((float) ($voidResult['after'] ?? 0)) >= ((float) ($voidResult['before'] ?? 0)) ? 'CREDIT' : 'DEBIT',
                    amount: $voidAmount,
                    balanceBefore: (float) ($voidResult['before'] ?? 0),
                    balanceAfter: (float) ($voidResult['after'] ?? 0),
                );
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                if (! $this->safeDecrementBalance($amount, false, [
                    'ref_type' => 'GAME_TIP',
                    'ref_code' => (string) ($txn['id'] ?? $session['id']),
                    'group_code' => 'GAME_TIP_'.(string) ($txn['roundId'] ?? $session['id']),
                    'description' => 'Provider tip debit',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'round_id' => $txn['roundId'] ?? null,
                        'status' => $txn['status'] ?? null,
                    ],
                ])) {
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
            $status = strtoupper((string) ($txn['status'] ?? 'REFUND'));

            $exists = GameLogProxy::where('company', $session['productId'])
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', $status)
                ->where('con_1', $txn['id'])
                ->where('con_2', $txn['roundId'])
                ->where('con_3', $status)
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

            $this->safeIncrementBalance($txn['betAmount'], [
                'ref_type' => 'GAME_CANCEL_TIP',
                'ref_code' => (string) ($txn['id'] ?? $session['id']),
                'group_code' => 'GAME_CANCEL_TIP_'.(string) ($txn['roundId'] ?? $session['id']),
                'description' => 'Provider cancel tip credit',
                'meta' => [
                    'product_id' => $session['productId'] ?? null,
                    'round_id' => $txn['roundId'] ?? null,
                    'status' => $status,
                ],
            ]);

            $param = $this->responseData($session['id'], $session['username'], $session['productId'], 0, $this->member->balance) + [
                    'balanceBefore' => (float) $oldBalance,
                    'balanceAfter' => (float) $this->member->balance,
                ];

            $this->createGameLog([
                'input' => $txn,
                'output' => $param,
                'company' => $session['productId'],
                'game_user' => $this->member->user_name,
                'method' => $status,
                'response' => 'in',
                'amount' => $txn['betAmount'],
                'con_1' => $txn['id'],
                'con_2' => $txn['roundId'],
                'con_3' => $status,
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
            return $this->responseData($session['id'], $session['username'], $session['productId'], $this->memberLookupStatusCode());
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
                if (! $this->safeDecrementBalance($item['amount'], false, [
                    'ref_type' => 'GAME_ADJUST_BALANCE',
                    'ref_code' => (string) ($item['refId'] ?? $session['id']),
                    'group_code' => 'GAME_ADJUST_BALANCE_'.(string) ($item['refId'] ?? $session['id']),
                    'description' => 'Provider adjust balance debit',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'status' => $item['status'] ?? null,
                    ],
                ])) {
                    $param = $this->responseData($session['id'], $session['username'], $session['productId'], 10002, $this->member->balance);
                    break;
                }
            } else {
                $this->safeIncrementBalance($item['amount'], [
                    'ref_type' => 'GAME_ADJUST_BALANCE',
                    'ref_code' => (string) ($item['refId'] ?? $session['id']),
                    'group_code' => 'GAME_ADJUST_BALANCE_'.(string) ($item['refId'] ?? $session['id']),
                    'description' => 'Provider adjust balance credit',
                    'meta' => [
                        'product_id' => $session['productId'] ?? null,
                        'status' => $item['status'] ?? null,
                    ],
                ]);
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
