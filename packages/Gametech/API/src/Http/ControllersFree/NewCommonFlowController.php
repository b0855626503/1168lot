<?php

namespace Gametech\API\Http\ControllersFree;

use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class NewCommonFlowController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $request;

    protected $member;

    protected $balances;

    protected $game;

    protected $days;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo,
        Request $request
    ) {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->request = $request;

        if (isset($this->request['token'])) {
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('session_id', $this->request['token'])->where('enable', 'Y')->first();
        } elseif (isset($this->request['sessionToken'])) {
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('session_id', $this->request['sessionToken'])->where('enable', 'Y')->first();
        } else {
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('enable', 'Y')->first();
        }

        //        $this->member->balance_free = $this->member->balance_free;

        $this->balances = 'balance_free';

        $this->game = 'ACE333';

        $this->days = 3;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

        if ($this->member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => 'THB',
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'balance' => (float) $this->member->balance_free,
                'timestampMillis' => now()->getTimestampMs(),
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $this->member->balance_free;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'id' => $session['id'],
                'statusCode' => 30001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];
        }

        return $param;
    }

    public function placeBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'betmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                if ($item['status'] === 'OPEN') {
                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'WAITING')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['status'])
                        ->latest('created_at')
                        ->first();
                    if ($checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => 'THB',
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float) $oldbalance,
                            'balanceAfter' => (float) $this->member->balance_free,
                            'timestampMillis' => now()->getTimestampMs(),
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $session['productId'];
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = $item['status'];
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['status'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                        GameLogProxy::create($session_in);
                        break;
                    }
                }

                if (isset($item['skipBalanceUpdate'])) {
                    if ($item['skipBalanceUpdate'] === false) {

                        $balance = ($this->member->balance_free - $item['betAmount']);

                        if ($balance < 0) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];
                            break;
                        }
                        $this->member->decrement($this->balances, $item['betAmount']);
                    }
                } else {

                    $balance = ($this->member->balance_free - $item['betAmount']);

                    if ($balance < 0) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }
                    $this->member->decrement($this->balances, $item['betAmount']);
                }

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);
            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }


    public function placeBets_cockfight(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'betmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                if (isset($item['skipBalanceUpdate'])) {
                    if ($item['skipBalanceUpdate'] === false) {

                        $balance = ($this->member->balance_free - $item['betAmount']);

                        if ($balance < 0) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];
                            break;
                        }
                        $this->member->decrement($this->balances, $item['betAmount']);
                    }
                } else {

                    $balance = ($this->member->balance_free - $item['betAmount']);

                    if ($balance < 0) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }
                    $this->member->decrement($this->balances, $item['betAmount']);
                }

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);
            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function settleBets_first(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            foreach ($session['txns'] as $item) {
                $amount += $item['payoutAmount'];
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'settlemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                if ($item['transactionType'] === 'BY_TRANSACTION') {

                    $checkDup = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $item['status'])
                        ->where('con_1', $item['id'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;

                    }

                } else {

                    if (isset($item['isFeature'])) {

                        if ($item['isFeature'] === false) {

                            if (isset($item['skipBalanceUpdate'])) {

                                if ($item['skipBalanceUpdate'] === false) {

                                    $checkDup = GameLogProxy::where('company', $session['productId'])
                                        ->where('response', 'in')
                                        ->where('game_user', $this->member->user_name)
                                        ->where('method', $item['status'])
                                        ->where('con_2', $item['roundId'])
                                        ->where('con_3', $item['status'])
                                        ->whereNull('con_4')
                                        ->latest('created_at')
                                        ->first();

                                    if ($checkDup) {

                                        $param = [
                                            'id' => $session['id'],
                                            'statusCode' => 20002,
                                            'timestampMillis' => now()->getTimestampMs(),
                                            'balance' => (float) $this->member->balance_free,
                                            'productId' => $session['productId'],
                                        ];
                                        break;

                                    }

                                }
                            } else {
                                $checkDup = GameLogProxy::where('company', $session['productId'])
                                    ->where('response', 'in')
                                    ->where('game_user', $this->member->user_name)
                                    ->where('method', $item['status'])
                                    ->where('con_2', $item['roundId'])
                                    ->where('con_3', $item['status'])
                                    ->whereNull('con_4')
                                    ->latest('created_at')
                                    ->first();

                                if ($checkDup) {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 20002,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'balance' => (float) $this->member->balance_free,
                                        'productId' => $session['productId'],
                                    ];
                                    break;

                                }
                            }

                        } else {

                            $checkDup = GameLogProxy::where('company', $session['productId'])
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', $item['status'])
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['status'])
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();

                            if ($checkDup) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float) $this->member->balance_free,
                                    'productId' => $session['productId'],
                                ];
                                break;

                            }

                        }
                    } else {

                        if (isset($item['skipBalanceUpdate'])) {
                            if ($item['skipBalanceUpdate'] === 'false') {
                                $checkDup = GameLogProxy::where('company', $session['productId'])
                                    ->where('response', 'in')
                                    ->where('game_user', $this->member->user_name)
                                    ->where('method', $item['status'])
                                    ->where('con_2', $item['roundId'])
                                    ->where('con_3', $item['status'])
                                    ->whereNull('con_4')
                                    ->latest('created_at')
                                    ->first();

                                if ($checkDup) {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 20002,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'balance' => (float) $this->member->balance_free,
                                        'productId' => $session['productId'],
                                    ];
                                    break;

                                }
                            }
                        } else {

                            $checkDup = GameLogProxy::where('company', $session['productId'])
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', $item['status'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['status'])
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();

                            if ($checkDup) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float) $this->member->balance_free,
                                    'productId' => $session['productId'],
                                ];
                                break;

                            }

                        }

                    }

                }

                if ($item['isSingleState'] === true) {

                    if (isset($item['skipBalanceUpdate'])) {
                        if ($item['skipBalanceUpdate'] === false) {
                            $balance = ($this->member->balance_free - $item['betAmount']);

                            if ($balance < 0) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float) $this->member->balance_free,
                                    'productId' => $session['productId'],
                                ];

                                break;

                            }
                            $this->member->decrement($this->balances, $item['betAmount']);
                        }
                    } else {
                        $balance = ($this->member->balance_free - $item['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];

                            break;

                        }
                        $this->member->decrement($this->balances, $item['betAmount']);
                    }

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => 'THB',
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float) $oldbalance,
                        'balanceAfter' => (float) $this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $session['productId'];
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'OPEN';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = 'OPEN';
                    $session_in['con_4'] = null;
                    $session_in['status'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                    GameLogProxy::create($session_in);

                    if (isset($item['skipBalanceUpdate'])) {
                        if ($item['skipBalanceUpdate'] === false) {
                            $this->member->increment($this->balances, $item['payoutAmount']);
                        }
                    } else {

                        $this->member->increment($this->balances, $item['payoutAmount']);

                    }

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => 'THB',
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float) $oldbalance,
                        'balanceAfter' => (float) $this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $session['productId'];
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = $item['status'];
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['status'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                    GameLogProxy::create($session_in);

                } else {

                    if ($item['transactionType'] === 'BY_ROUND') {

                        $checkData = GameLogProxy::where('company', $session['productId'])
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'OPEN')
                            ->where('con_2', $item['roundId'])
                            ->latest('created_at')
                            ->get();

                    } else {

                        $checkData = collect([GameLogProxy::where('company', $session['productId'])
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'OPEN')
                            ->where('con_1', $item['id'])
                            ->latest('created_at')
                            ->first()]);

                    }

                    if ($checkData->isEmpty()) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }

                    if (isset($item['skipBalanceUpdate'])) {
                        if ($item['skipBalanceUpdate'] === false) {

                            $this->member->increment($this->balances, $item['payoutAmount']);

                        }
                    } else {
                        $this->member->increment($this->balances, $item['payoutAmount']);
                    }

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => 'THB',
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float) $oldbalance,
                        'balanceAfter' => (float) $this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $session['productId'];
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = $item['status'];
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['status'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                    $id = GameLogProxy::create($session_in)->id;

                    foreach ($checkData as $subitem) {
                        $subitem->con_4 = $item['status'].'_'.$id;
                        $subitem->save();
                    }

                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function settleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            foreach ($session['txns'] as $item) {
                $amount += $item['payoutAmount'];
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'settlemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {
                $oldsbalance = $this->member->balance_free;
                $isSingleState = false;
                if (isset($item['isSingleState'])) {
                    $isSingleState = $item['isSingleState'];
                }

                $ismulti = false;
                $isFeature = false;
                if (isset($item['isFeature'])) {
                    if ($item['isFeature'] === true) {
                        $isFeature = true;
                    }
                }
                if (isset($item['isFeatureBuy'])) {
                    if ($item['isFeatureBuy'] === true) {
                        $isFeature = true;
                    }
                }

                $isEndRound = true;
                if (isset($item['isEndRound'])) {
                    $isEndRound = $item['isEndRound'];
                }

                if ($isFeature === true && $isEndRound === true) {
                    $ismulti = true;
                } elseif ($isFeature === true && $isEndRound === false) {
                    $ismulti = true;
                } elseif ($isFeature === false && $isEndRound === false) {
                    $ismulti = true;
                } elseif ($isFeature === false && $isEndRound === true) {
                    $ismulti = false;
                }

                $skipBalanceUpdate = false;
                if (isset($item['skipBalanceUpdate'])) {
                    $skipBalanceUpdate = $item['skipBalanceUpdate'];
                }

                if ($isSingleState) {

                    if ($skipBalanceUpdate === false) {

                        $checkDup = GameLogProxy::where('company', $session['productId'])
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'OPEN')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if ($checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];

                            break;
                        }

                        $balance = ($this->member->balance_free - $item['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];

                            break;

                        }

                        $this->member->decrement($this->balances, $item['betAmount']);
                    }

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => 'THB',
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float) $oldsbalance,
                        'balanceAfter' => (float) $this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $session['productId'];
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'OPEN';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = 'OPEN';
                    $session_in['con_4'] = null;
                    $session_in['status'] = null;
                    $session_in['before_balance'] = $oldsbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                    GameLogProxy::create($session_in);
                }

                $oldsbalance = $this->member->balance_free;

                $transactionType = $item['transactionType'];

                if ($transactionType === 'BY_ROUND') {

                    $checkDatas = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'OPEN')
                        ->where('con_2', $item['roundId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->get();

                    if ($checkDatas->isEmpty()) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;

                    }

                    if (! $ismulti) {
                        if ($skipBalanceUpdate === false) {
                            $checkDup = GameLogProxy::where('company', $session['productId'])
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', $item['status'])
                                ->where('con_2', $item['roundId'])
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();

                            if ($checkDup) {
                                if ($checkDup['con_3'] === false) {
                                    //                            if($checkDup['con_1'] == $item['id']) {
                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 20002,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'balance' => (float) $this->member->balance_free,
                                        'productId' => $session['productId'],
                                    ];
                                    break;
                                    //                            }
                                }
                            }
                        }
                    }

                } else {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'OPEN')
                        ->where('con_1', $item['id'])
//                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (! $checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;

                    }

                    if ($skipBalanceUpdate === false) {
                        $checkDup = GameLogProxy::where('company', $session['productId'])
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', $item['status'])
                            ->where('con_1', $item['id'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if ($checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float) $this->member->balance_free,
                                'productId' => $session['productId'],
                            ];
                            break;
                        }
                    }

                    //                    if ($ismulti) {
                    //                        $checkDup = GameLogProxy::where('company', $session['productId'])
                    //                            ->where('response', 'in')
                    //                            ->where('game_user', $this->member->user_name)
                    //                            ->where('method', $item['status'])
                    //                            ->where('con_1', $item['id'])
                    //                            ->where('con_2', $item['roundId'])
                    //                            ->latest('created_at')
                    //                            ->first();
                    //                        if ($checkDup) {
                    //                            $param = [
                    //                                'id' => $session['id'],
                    //                                'statusCode' => 20002,
                    //                                'timestampMillis' => now()->getTimestampMs(),
                    //                                'balance' => (float)$this->member->balance_free,
                    //                                'productId' => $session['productId'],
                    //                            ];
                    //                            break;
                    //                        }
                    //                    } else {
                    //                        $checkDup = GameLogProxy::where('company', $session['productId'])
                    //                            ->where('response', 'in')
                    //                            ->where('game_user', $this->member->user_name)
                    //                            ->where('method', $item['status'])
                    //                            ->where('con_1', $item['id'])
                    //                            ->latest('created_at')
                    //                            ->first();
                    //                        if ($checkDup) {
                    //                            $param = [
                    //                                'id' => $session['id'],
                    //                                'statusCode' => 20002,
                    //                                'timestampMillis' => now()->getTimestampMs(),
                    //                                'balance' => (float)$this->member->balance_free,
                    //                                'productId' => $session['productId'],
                    //                            ];
                    //                            break;
                    //                        }

                    //                    }

                }

                if ($skipBalanceUpdate === false) {
                    $this->member->increment($this->balances, $item['payoutAmount']);
                }

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['payoutAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $ismulti;
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldsbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                $id = GameLogProxy::create($session_in)->id;

                //                if(!$ismulti) {
                if ($transactionType === 'BY_ROUND') {
                    foreach ($checkDatas as $checkData) {
                        $checkData->con_4 = $item['status'].'_'.$id;
                        $checkData->save();
                    }
                } else {
                    $checkData->con_4 = $item['status'].'_'.$id;
                    $checkData->save();
                }
                //                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $array = false;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'cancelmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                if ($item['status'] == 'REJECT') {
                    $method = 'WAITING';
                } else {
                    $method = 'OPEN';
                }

                if ($item['transactionType'] === 'BY_ROUND') {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $method)
                        ->where('con_2', $item['roundId'])
//                            ->whereNull('con_4')
//                        ->whereNull('status')
                        ->latest('created_at')
                        ->get();

                    if ($checkData->isEmpty()) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }

                    foreach ($checkData as $subData) {
                        $amount += $subData['amount'];
                    }

                    $array = true;

                } else {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $method)
                        ->where('con_1', $item['id'])
//                            ->whereNull('con_4')
//                        ->whereNull('status')
                        ->latest('created_at')
//                        ->withSum('amount as total_amount')
                        ->first();

                    if (! $checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }

                    $amount = $checkData['amount'];

                }

                if ($item['betAmount'] > $amount) {
                    $this->member->decrement($this->balances, $amount);
                }

                $this->member->increment($this->balances, $item['betAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                $id = GameLogProxy::create($session_in)->id;

                if ($array) {
                    foreach ($checkData as $subdata) {
                        $subdata->con_4 = $item['status'].'_'.$id;
                        $subdata->save();
                    }
                } else {
                    $checkData->con_4 = $item['status'].'_'.$id;
                    $checkData->save();
                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function adjustBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'adjustbetmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkData = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->latest('created_at')
                    ->first();

                if (! $checkData) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20001,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                $balance = ($this->member->balance_free + $checkData['amount']) - $item['betAmount'];
                if ($balance < 0) {
                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 10002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;
                }

                $this->member->increment($this->balances, $checkData['amount']);
                $this->member->decrement($this->balances, $item['betAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                $id = GameLogProxy::create($session_in)->id;

                $checkData->con_4 = 'ADJUSTBET'.'_'.$id;
                $checkData->save();

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function rollback(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'rollbackmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                if ($item['transactionType'] === 'BY_ROUND') {

                    $checkDup = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', $item['status'])
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;

                    }

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->whereIn('method', ['REFUND', 'SETTLED'])
                        ->where('con_2', $item['roundId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (! $checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }

                } else {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->whereIn('method', ['REFUND', 'SETTLED'])
                        ->where('con_1', $item['id'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (! $checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }

                }

                if ($checkData['method'] == 'SETTLED') {
                    //                    $balance = ($this->member->balance_free - $item['payoutAmount']);
                    //                    if ($balance < 0) {
                    //                        $param = [
                    //                            'id' => $session['id'],
                    //                            'statusCode' => 10002,
                    //                            'timestampMillis' => now()->getTimestampMs(),
                    //                            'balance' => (float) $this->member->balance_free,
                    //                            'productId' => $session['productId'],
                    //                        ];
                    //                        break;
                    //                    }

                    $this->member->decrement($this->balances, $item['payoutAmount']);

                } else {

                    //                    $balance = ($this->member->balance_free - $item['betAmount']);
                    //                    if ($balance < 0) {
                    //                        $param = [
                    //                            'id' => $session['id'],
                    //                            'statusCode' => 10002,
                    //                            'timestampMillis' => now()->getTimestampMs(),
                    //                            'balance' => (float) $this->member->balance_free,
                    //                            'productId' => $session['productId'],
                    //                        ];
                    //                        break;
                    //                    }

                    $this->member->decrement($this->balances, $item['betAmount']);

                }

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = ($checkData['method'] == 'SETTLED' ? $item['payoutAmount'] : $item['betAmount']);
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                $id = GameLogProxy::create($session_in)->id;

                $checkData->con_4 = $item['status'].'_'.$id;
                $checkData->save();

                $checkBets = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->whereIn('method', ['WAITING', 'OPEN'])
//                    ->where('con_1', $item['id'])
                    ->where('con_4', $checkData->method.'_'.$checkData->id)
                    ->latest('created_at')
                    ->get();
                if ($checkBets->isNotEmpty()) {
                    foreach ($checkBets as $checkBet) {
                        $checkBet->con_4 = null;
                        $checkBet->save();
                    }
                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function voidSettled(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'voidsettlemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                if ($item['transactionType'] === 'BY_ROUND') {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'SETTLED')
                        ->where('con_2', $item['roundId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                } else {

                    $checkData = GameLogProxy::where('company', $session['productId'])
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'SETTLED')
                        ->where('con_1', $item['id'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                }

                if (! $checkData) {
                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20001,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;
                }

                $this->member->increment($this->balances, $item['betAmount']);
                $this->member->decrement($this->balances, $item['payoutAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = ($item['betAmount'] - $item['payoutAmount']);
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                $id = GameLogProxy::create($session_in)->id;

                $checkData->con_4 = $item['status'].'_'.$id;
                $checkData->save();

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function winRewards(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'winrewardmain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                $this->member->increment($this->balances, $item['payoutAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['payoutAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'unsettle')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->latest('created_at')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float) $this->member->balance_free,
                    'productId' => $session['productId'],
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'unsettle';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'unsettlesub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];

                        break;
                    }

                    if ($item['betAmount'] > 0) {

                        $this->member->decrement($this->balances, $item['betAmount']);

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => 'THB',
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float) $oldbalance,
                            'balanceAfter' => (float) $this->member->balance_free,
                            'timestampMillis' => now()->getTimestampMs(),
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                        GameLogProxy::create($session_in);

                        continue;

                    }

                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'paysub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (! $checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];

                        break;

                    }

                    $balance = ($this->member->balance_free - $item['payoutAmount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];

                        break;

                    }

                    $this->member->decrement($this->balances, $item['payoutAmount']);

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => 'THB',
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float) $oldbalance,
                        'balanceAfter' => (float) $this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs(),
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'unsettlesub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'unsettle_'.$id;
                    $checkData->save();

                    GameLogProxy::where('con_4', 'settle_'.$checkData['_id'])->update(['con_4' => null]);

                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        return $param;
    }

    public function adjustBalance(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'adjustbalancemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'ADJUSTBALANCE')
                    ->where('con_1', $item['refId'])
                    ->where('con_2', $item['refId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->first();

                if ($checkDup) {
                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;
                }

                if ($item['status'] == 'DEBIT') {
                    $balance = $this->member->balance_free - $item['amount'];

                    if ($balance < 0) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float) $this->member->balance_free,
                            'productId' => $session['productId'],
                        ];
                        break;
                    }
                    $this->member->decrement($this->balances, $item['amount']);
                } else {
                    $this->member->increment($this->balances, $item['amount']);
                }

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'ADJUSTBALANCE';
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['amount'];
                $session_in['con_1'] = $item['refId'];
                $session_in['con_2'] = $item['refId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'OPEN';
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['amount'];
                $session_in['con_1'] = $item['refId'];
                $session_in['con_2'] = $item['refId'];
                $session_in['con_3'] = 'OPEN';
                $session_in['con_4'] = null;
                $session_in['status'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function placeTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'placetipemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                $balance = ($this->member->balance_free - $item['betAmount']);

                if ($balance < 0) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 10002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];

                    break;

                }

                $this->member->decrement($this->balances, $item['betAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }

    public function cancelTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $session['productId'];
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'canceltipemain';
            $session_in['response'] = 'in';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
            $main = GameLogProxy::create($session_in);

            foreach ($session['txns'] as $item) {

                $checkDup = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', $item['status'])
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if ($checkDup) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                $checkData = GameLogProxy::where('company', $session['productId'])
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'TIPS')
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
//                    ->where('con_3', $item['status'])
                    ->whereNull('con_4')
                    ->latest('created_at')
                    ->first();

                if (! $checkData) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20001,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];
                    break;

                }

                $balance = ($this->member->balance_free - $item['betAmount']);

                if ($balance < 0) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 10002,
                        'timestampMillis' => now()->getTimestampMs(),
                        'balance' => (float) $this->member->balance_free,
                        'productId' => $session['productId'],
                    ];

                    break;

                }

                $this->member->increment($this->balances, $item['betAmount']);

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

                $session_in['input'] = $item;
                $session_in['output'] = $param;
                $session_in['company'] = $session['productId'];
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = $item['status'];
                $session_in['response'] = 'in';
                $session_in['amount'] = $item['betAmount'];
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['status'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays($this->days));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

        $main->output = $param;
        $main->save();

        return $param;
    }
}
