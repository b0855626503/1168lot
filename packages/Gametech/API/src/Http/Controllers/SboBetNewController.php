<?php

namespace Gametech\API\Http\Controllers;


use Gametech\API\Models\GameLogProxy;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;

class SboBetNewController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $request;

    protected $member;

    protected $balances;

    protected $game;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository      $memberRepo,
        GameUserRepository    $gameUserRepo,
        Request               $request
    )
    {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->request = $request;

        if (isset($this->request['sessionToken'])) {
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('enable', 'Y')->first();
        } else {

            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('enable', 'Y')->first();
        }

//        $this->member->balance = $this->member->balance;

        $this->balances = 'balance';

        $this->game = 'SBO';
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();

        if ($this->member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => "THB",
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'balance' => (float)$this->member->balance,
                'timestampMillis' => now()->getTimestampMs()
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $this->game;
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $this->member->balance;
            $session_in['after_balance'] = $this->member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];
        }

//        $path = storage_path('logs/seamless/ADVANT' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {


                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'betsub')
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
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $checkCancel = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'refundsub')
                        ->whereNotNull('con_1')

//                        ->where('amount', $item['betAmount'])
//                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->whereNotNull('con_3')
//                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkCancel) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        break;

                    }

                    $balance = ($this->member->balance - $item['betAmount']);
                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $this->member->decrement($this->balances, abs($item['betAmount']));

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance,
                        'timestampMillis' => now()->getTimestampMs()
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
                    $session_in['after_balance'] = $this->member->balance;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    GameLogProxy::create($session_in);

                }

            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'payout')
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
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'paysub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $checkBet = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'betsub')
                        ->whereNotNull('con_1')
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', 'OPEN')
                        ->latest('created_at')
                        ->first();

                    if (!$checkBet) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if (!is_null($checkBet['con_4'])) {

                        if (Str::contains($checkBet['con_4'], 'cancel')) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20003,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

                    }


                    $amount = ($this->member->balance + $item['payoutAmount']);

                    $this->member->increment($this->balances, abs($item['payoutAmount']));
//                    MemberProxy::where('user_name', $session['username'])->increment('balance', abs($item['payoutAmount']));
//                    $member = MemberProxy::where('user_name', $session['username'])->first();

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance,
                        'timestampMillis' => now()->getTimestampMs()
                    ];


                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'paysub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['txnId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkBet->con_4 = 'settle_' . $id;
                    $checkBet->save();


                }

            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'cancel')
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
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'refundsub')
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
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }


                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'betsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', 'OPEN')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if (!is_null($checkData->con_4)) {
                        $check_settle = stripos($checkData->con_4, "settle");
                        if ($check_settle !== false) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20004,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];
                            break;
                        }

                        $check_cancel = stripos($checkData->con_4, "cancel");
                        if ($check_cancel !== false) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];
                            break;
                        }

                    }


                    $this->member->increment($this->balances, $checkData['amount']);

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'refundsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = 'complete';
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'cancel_' . $id;
                    $checkData->save();


                }
            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

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
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
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
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

//                    $checkDup = GameLogProxy::where('company', $this->game)
//                        ->where('response', 'in')
//                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'unsettlesub')
//                        ->where('con_1', $item['id'])
//                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['txnId'])
//                        ->whereNull('con_4')
//                        ->latest('created_at')
//                        ->first();
//
//                    if ($checkDup) {
//
//                        $param = [
//                            'id' => $session['id'],
//                            'statusCode' => 20002,
//                            'timestampMillis' => now()->getTimestampMs(),
//                            'balance' => (float)$this->member->balance,
//                            'productId' => $session['productId']
//                        ];
//
//                        break;
//                    }


                    if ($item['betAmount'] > 0) {


                        $checkDup = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', 'OPEN')
                            ->latest('created_at')
                            ->first();

                        if (!$checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $this->member->decrement($this->balances, $item['betAmount']);

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $checkDup['amount'];
                        $session_in['con_1'] = $checkDup['con_1'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = 'OPEN';
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        GameLogProxy::create($session_in);

                        continue;

                    }


                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'betsub')
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', 'OPEN')
//                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];

                        break;
                    }

                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'paysub')
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $balance = ($this->member->balance - $checkData['amount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $this->member->decrement($this->balances, $checkData['amount']);


                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'unsettlesub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $checkData['amount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['txnId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'unsettle_' . $id;
                    $checkData->save();
//
                    GameLogProxy::where('con_4', 'settle_' . $checkData['_id'])->update(['con_4' => null]);


                }

            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function adjustBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'adjust')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->latest('created_at')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20001,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'adjust';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'ajsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();


                    if ($checkDup) {

                        if ($item['betAmount'] > $this->member->balance) {


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

                        if ($item['betAmount'] < $checkDup['amount']) {

                            $amount = $checkDup['amount'] - $item['betAmount'];


                            $this->member->increment($this->balances, $amount);


                        } else if ($item['betAmount'] > $checkDup['amount']) {

                            $amount = $item['betAmount'] - $checkDup['amount'];

                            $balance = $this->member->balance - $amount;

                            if ($balance < 0) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance,
                                    'productId' => $session['productId']
                                ];
                                break;
                            }

                            $this->member->decrement($this->balances, $amount);
                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        GameLogProxy::create($session_in);

                    } else {

                        $checkData = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', 'OPEN')
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

                        if ($item['betAmount'] < $checkData['amount']) {

                            $amount = $checkData['amount'] - $item['betAmount'];

                            $this->member->increment($this->balances, $amount);


                        } else if ($item['betAmount'] > $checkData['amount']) {

                            $amount = $item['betAmount'] - $checkData['amount'];

                            if ($amount > $this->member->balance) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance,
                                    'productId' => $session['productId']
                                ];
                                break;
                            }

                            $this->member->decrement($this->balances, $amount);

                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        GameLogProxy::create($session_in);

                    }
                }
            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function winRewards(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'win')
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
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'win';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];


                    $datasub = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'winsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;


                    } else {


                        $datasubs = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'unsettlesub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->where('con_4', 'complete')
                            ->latest('created_at')
                            ->first();

                        if ($datasubs) {

                            $datasubss = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'paysub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();

                            if ($datasubss) {

                                $this->member->increment($this->balances, $item['payoutAmount']);

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $this->member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$this->member->balance,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = $this->game;
                                $session_in['game_user'] = $this->member->user_name;
                                $session_in['method'] = 'winsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['payoutAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $this->member->balance;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                                GameLogProxy::create($session_in);
                            }

                        } else {

                            $this->member->increment($this->balances, $item['payoutAmount']);

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance,
                                'timestampMillis' => now()->getTimestampMs()
                            ];


                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = $this->game;
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'winsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['payoutAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            GameLogProxy::create($session_in);

                        }


                    }

                } // loop

            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }

        return $param;
    }

    public function placeTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'tips')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'tips';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'tipsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {

                        $balance = ($this->member->balance - $item['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                        $this->member->decrement($this->balances, $item['betAmount']);


                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'tipsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        GameLogProxy::create($session_in);

                    }

                }


            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function cancelTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'canceltip')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$this->member->balance,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'canceltip';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'ctsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'tipsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->whereNull('con_4')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $this->member->increment($this->balances, $checkData['amount']);


                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'ctsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $checkData['amount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'canceltip_' . $id;
                    $checkData->save();

                }
            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

}
