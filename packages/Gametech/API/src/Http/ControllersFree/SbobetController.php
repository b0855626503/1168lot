<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class   SbobetController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $request;

    protected $member;

//    protected $balance;
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
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('session_id', $this->request['sessionToken'])->where('enable', 'Y')->first();

        } else {
//            $this->member = $this->memberRepository->findOneWhere(['user_name' => $this->request['username'], 'enable' => 'Y']);
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['username'])->where('enable', 'Y')->first();
        }

//        $this->member->balance_free = $this->member->balance_free;

        $this->balances = 'balance_free';

        $this->game = 'SBO';
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

//                $path = storage_path('logs/seamless/nextspin' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
//        file_put_contents($path, print_r($this->member, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        if ($this->member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => "THB",
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'balance' => (float)$this->member->balance_free,
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
            $session_in['before_balance'] = $this->member->balance_free;
            $session_in['after_balance'] = $this->member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'id' => $session['id'],
                'statusCode' => 30001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
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
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if ($item['status'] === 'OPEN') {

                        $checkData = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
                            ->where('amount', $item['betAmount'])
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', 'WAITING')
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $balance = ($this->member->balance_free - $item['betAmount']);
                            if ($balance < 0) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
                                    'productId' => $session['productId']
                                ];
                                break;

                            }

                            if (isset($item['skipBalanceUpdate'])) {

                                if ($item['skipBalanceUpdate'] === false) {
                                    $this->member->decrement($this->balances, $item['betAmount']);
                                }

                            }else{
                                $this->member->decrement($this->balances, $item['betAmount']);
                            }
                            //$this->member->refresh();
//                            MemberProxy::where('user_name', $session['username'])->decrement($this->balances, $item['betAmount']);
//                            $member = MemberProxy::where('user_name', $session['username'])->first();
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free,
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
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            GameLogProxy::create($session_in);


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free,
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
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'bet_' . $id;
                            $checkData->save();

                        }


                    } else {


                        $balance = ($this->member->balance_free - $item['betAmount']);
                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

//                        MemberProxy::where('user_name', $session['username'])->decrement($this->balances, $item['betAmount']);
//                        $member = MemberProxy::where('user_name', $session['username'])->first();
                        if (isset($item['skipBalanceUpdate'])) {
                            if ($item['skipBalanceUpdate'] === false) {
                                $this->member->decrement($this->balances, $item['betAmount']);
                            }
                        }else{
                            $this->member->decrement($this->balances, $item['betAmount']);
                        }
                        //$this->member->refresh();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['after_balance'] = $this->member->balance_free;
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

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {


                    if ($item['isSingleState'] === true) {

                        $checkDup = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'paysub')
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
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $balance = ($this->member->balance_free - $item['betAmount']);
                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                        if (isset($item['skipBalanceUpdate'])) {
                            if ($item['skipBalanceUpdate'] === false) {
                                $this->member->decrement($this->balances, $item['betAmount']);
                            }
                        }else{
                            $this->member->decrement($this->balances, $item['betAmount']);
                        }


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = 'OPEN';
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        $id = GameLogProxy::create($session_in)->id;


                        $checkBet = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
                            ->where('_id', $id)
                            ->first();

                        if (!$checkBet) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }
                        if (isset($item['skipBalanceUpdate'])) {
                            if ($item['skipBalanceUpdate'] === false) {
                                $this->member->increment($this->balances, $item['payoutAmount']);
                            }
                        }else{
                            $this->member->increment($this->balances, $item['payoutAmount']);
                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        $id = GameLogProxy::create($session_in)->id;

                        $checkBet->con_4 = 'settle_' . $id;
                        $checkBet->save();


                    } else {

                        $checkDup = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'paysub')
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
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            return $param;
                        }

                        if ($item['transactionType'] === 'BY_ROUND') {

                            if ($item['isFeature'] === false) {

                                $checkDup = GameLogProxy::where('company', $this->game)
                                    ->where('response', 'in')
                                    ->where('game_user', $this->member->user_name)
                                    ->where('method', 'paysub')
//                                ->where('con_1', $item['id'])
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
                                        'balance' => (float)$this->member->balance_free,
                                        'productId' => $session['productId']
                                    ];

                                    break;
                                }
                            }

                            $checkBet = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('con_2', $item['roundId'])
                                ->first();
//
//                            if($checkBet['method'] === 'paysub'){
//                                $param = [
//                                    'id' => $session['id'],
//                                    'statusCode' => 20002,
//                                    'timestampMillis' => now()->getTimestampMs(),
//                                    'balance' => (float)$this->member->balance_free,
//                                    'productId' => $session['productId']
//                                ];
//
//                                return $param;
//                            }

                        } else {
                            $checkBet = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'betsub')
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'OPEN')
                                ->latest('created_at')
                                ->first();
                        }


                        if (!$checkBet) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        if (isset($item['skipBalanceUpdate'])) {
                            if ($item['skipBalanceUpdate'] === false) {

                                $this->member->increment($this->balances, $item['payoutAmount']);

                            }
                        }else{
                            $this->member->increment($this->balances, $item['payoutAmount']);
                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        $id = GameLogProxy::create($session_in)->id;

                        $checkBet->con_4 = 'settle_' . $id;
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

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
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
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if ($item['status'] == 'REJECT') {
                        if ($item['transactionType'] === 'BY_TRANSACTION') {

                            $checkData = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'betsub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'WAITING')
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();


                            if (!$checkData) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
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
                                'balanceAfter' => (float)$this->member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = $this->game;
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        } else {

                            $checkData = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'betsub')
                                ->whereNotNull('con_1')
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'WAITING')
                                ->latest('created_at')
                                ->first();

                            if (!$checkData) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
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
                                'balanceAfter' => (float)$this->member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = $this->game;
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        }


                    } else {

                        if ($item['transactionType'] === 'BY_TRANSACTION') {
                            $checkData = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->whereIn('method', ['betsub', 'ajsub'])
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
                                    'balance' => (float)$this->member->balance_free,
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
                                'balanceAfter' => (float)$this->member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = $this->game;
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        } else {

                            $amountsub = 0;

                            $checkDatas = GameLogProxy::where('company', $this->game)
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->whereIn('method', ['betsub', 'ajsub'])
                                ->whereNotNull('con_1')
                                ->where('con_2', $item['roundId'])
                                ->latest('created_at')
                                ->get();

                            if (count($checkDatas) < 1) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
                                    'productId' => $session['productId']
                                ];
                                break;


                            }


                            $this->member->increment($this->balances, $item['betAmount']);


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free,
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
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            foreach ($checkDatas as $checkData) {
                                $checkData->con_4 = 'cancel_' . $id;
                                $checkData->save();
                            }

                        }

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
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
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;
                    }


                    if ($item['betAmount'] > 0) {


                        $this->member->decrement($this->balances, $item['betAmount']);

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
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

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $balance = ($this->member->balance_free - $item['payoutAmount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $this->member->decrement($this->balances, $item['payoutAmount']);

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs()
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
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'unsettle_' . $id;
                    $checkData->save();

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

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
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
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        if ($item['betAmount'] > $this->member->balance_free) {


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

                        if ($item['betAmount'] < $checkDup['amount']) {

                            $amount = $checkDup['amount'] - $item['betAmount'];


                            $this->member->increment($this->balances, $amount);


                        } else if ($item['betAmount'] > $checkDup['amount']) {

                            $amount = $item['betAmount'] - $checkDup['amount'];

                            $balance = $this->member->balance_free - $amount;

                            if ($balance < 0) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
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
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
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
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }


                        if ($item['betAmount'] < $checkData['amount']) {

                            $amount = $checkData['amount'] - $item['betAmount'];

                            $this->member->increment($this->balances, $amount);


                        } else if ($item['betAmount'] > $checkData['amount']) {

                            $amount = $item['betAmount'] - $checkData['amount'];

                            if ($amount > $this->member->balance_free) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free,
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
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
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

    public function adjustBalance(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'ajb')
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
                    'balance' => (float)$this->member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['amount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'ajb';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'ajbsub')
                        ->where('con_1', $item['refId'])
                        ->where('con_2', $item['refId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
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
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;
                        }

                        $this->member->decrement($this->balances, $item['amount']);

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajbsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['amount'];
                        $session_in['con_1'] = $item['refId'];
                        $session_in['con_2'] = $item['refId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                        GameLogProxy::create($session_in);

                    } else if ($item['status'] == 'CREDIT') {


                        $this->member->increment($this->balances, $item['amount']);

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = $this->game;
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajbsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['amount'];
                        $session_in['con_1'] = $item['refId'];
                        $session_in['con_2'] = $item['refId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free;
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

            $oldbalance = $this->member->balance_free;

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
                    'statusCode' => 0,
                    'currency' => "THB",
                    'productId' => $session['productId'],
                    'username' => $this->member->user_name,
                    'balanceBefore' => (float)$oldbalance,
                    'balanceAfter' => (float)$this->member->balance_free,
                    'timestampMillis' => now()->getTimestampMs()
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
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'winsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['status'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
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
                            ->where('con_3', $item['status'])
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
                                ->where('con_3', $item['status'])
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
                                    'balanceAfter' => (float)$this->member->balance_free,
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
                                $session_in['con_3'] = $item['status'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $this->member->balance_free;
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
                                'balanceAfter' => (float)$this->member->balance_free,
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
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free;
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

    public function rollback(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();


        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'rollback')
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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['method'] = 'rollback';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {


                    if ($item['transactionType'] === 'BY_TRANSACTION') {

                        $checkData = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->whereIn('method', ['paysub', 'refundsub'])
//                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                    } else {

                        $checkDup = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'rollsub')
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
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $checkData = GameLogProxy::where('company', $this->game)
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->whereIn('method', ['paysub', 'refundsub'])
                            ->where('con_2', $item['roundId'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                    }

                    $balance = ($this->member->balance_free - ($item['payoutAmount'] + $item['betAmount']));


                    $this->member->decrement($this->balances, $item['payoutAmount']);
                    $this->member->decrement($this->balances, $item['betAmount']);


                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'rollsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'] + $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'rollback_' . $id;
                    $checkData->save();

                    if ($checkData['method'] == 'paysub') {

                        GameLogProxy::where('con_4', 'settle_' . $checkData['_id'])->update(['con_4' => null]);

                    } else {

                        GameLogProxy::where('con_4', 'cancel_' . $checkData['_id'])->update(['con_4' => null]);

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

    public function voidSettled(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'void')
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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['method'] = 'void';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'voidsub')
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
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $checkData = GameLogProxy::where('company', $this->game)
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->whereIn('method', ['paysub', 'winsub'])
                        ->whereNotNull('con_1')
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', 'SETTLED')
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $amountsub = $item['betAmount'] - $item['payoutAmount'];
                    $balance = $this->member->balance_free + ($item['betAmount'] - $item['payoutAmount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $this->member->increment($this->balances, $item['betAmount']);
                    $this->member->decrement($this->balances, $item['payoutAmount']);

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'voidsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amountsub;
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
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

    public function placeTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
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
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    } else {

                        $balance = ($this->member->balance_free - $item['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free,
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
                            'balanceAfter' => (float)$this->member->balance_free,
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
                        $session_in['after_balance'] = $this->member->balance_free;
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

            $oldbalance = $this->member->balance_free;

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
                    'balance' => (float)$this->member->balance_free,
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
                $session_in['after_balance'] = $this->member->balance_free;
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
                            'balance' => (float)$this->member->balance_free,
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
                            'balance' => (float)$this->member->balance_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $this->member->increment($this->balances, $item['betAmount']);


                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = $this->game;
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'ctsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free;
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
