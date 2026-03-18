<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class FachaiGaminngNewController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $request;

    protected $member;

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
            $this->member = MemberProxy::without('bank')->where('user_name',$this->request['username'])->where('session_id',$this->request['sessionToken'])->where('enable','Y')->first();

        } else {
//            $this->member = $this->memberRepository->findOneWhere(['user_name' => $this->request['username'], 'enable' => 'Y']);
            $this->member = MemberProxy::without('bank')->where('user_name',$this->request['username'])->where('enable','Y')->first();
        }
    }


    public function transaction(Request $request)
    {
        $param = [];
        $session = $request->all();
        if ($session['productId'] != 'FACHAI') {
            $param = [
                'id' => $session['id'],
                'statusCode' => 40003,
                'productId' => $session['productId'],
                'timestampMillis' => now()->getTimestampMs()
            ];
            return $param;
        }


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);
        if ($member) {

            $oldbalance = $this->member->balance_free_free;
            $amount = 0;
            foreach ($session['txns'] as $item) {

                $data = GameLogProxy::where('company', 'FACHAI')
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->where('method', 'transub')
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['gameCode'])
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 20002,
                        'productId' => $session['productId'],
                        'timestampMillis' => now()->getTimestampMs()
                    ];
                    break;

                } else {

                    if ($item['playInfo'] === 'Fishing-withdraw') {
                        $amount = $item['betAmount'];
                        $check = $this->member->balance_free_free - $amount;
                    } else {
                        $amount = $item['payoutAmount'] - $item['betAmount'];
                        $check = $this->member->balance_free_free + $amount;
                    }

                    if ($check >= 0) {
                        $this->member->balance_free_free = $check;
                        $this->member->save();
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'productId' => $session['productId'],
                            'timestampMillis' => now()->getTimestampMs(),
                            'currency' => 'THB',
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'username' => $session['username']
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'transub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $amount;
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['gameCode'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    } else {
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'productId' => $session['productId'],
                            'timestampMillis' => now()->getTimestampMs()
                        ];
                        break;
                    }

                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'transub';
                $session_in['response'] = 'out';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['gameCode'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }


        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'productId' => $session['productId'],
                'timestampMillis' => now()->getTimestampMs()
            ];

        }


        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- TRANSACTION --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

//                $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

//        if(isset($session['sessionToken'])){
//            $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'],'session_id' => $session['sessionToken'], 'enable' => 'Y']);
//        }else{
//            $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);
//
//        }


        if ($this->member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => "THB",
                'productId' => $session['productId'],
                'username' => $this->member->user_name,
                'balance' => (float)$this->member->balance_free_free,
                'timestampMillis' => now()->getTimestampMs()
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'FACHAI';
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $this->member->balance_free_free;
            $session_in['after_balance'] = $this->member->balance_free_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
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

//        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {


                    $checkDup = GameLogProxy::where('company', 'FACHAI')
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
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if ($item['status'] === 'OPEN') {

                        $checkData = GameLogProxy::where('company', 'FACHAI')
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

                            $balance = ($this->member->balance_free_free - $item['betAmount']);
                            if ($balance < 0) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;

                            }


                            $this->member->decrement('balance_free', $item['betAmount']);
                            //$this->member->refresh();
//                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                            $member = MemberProxy::where('user_name', $session['username'])->first();
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'betsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
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
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'betsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'bet_' . $id;
                            $checkData->save();

                        }


                    } else {

                        $balance = ($this->member->balance_free_free - $item['betAmount']);
                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

//                        MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $this->member->decrement('balance_free', $item['betAmount']);
                        //$this->member->refresh();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {


                    if ($item['isSingleState'] === true) {

                        $checkDup = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'paysub')
//                        ->whereNotNull('con_1')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                        ->whereNotNull('con_3')
                            ->where('con_3', $item['gameCode'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if ($checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $balance = ($this->member->balance_free_free - $item['betAmount']);
                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                        if ($item['skipBalanceUpdate'] === false) {
                            $this->member->decrement('balance_free', $item['betAmount']);
                            //$this->member->refresh();
//                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);

                        }


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = 'OPEN';
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        $id = GameLogProxy::create($session_in)->id;


                        $checkBet = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
//                        ->whereNotNull('con_1')
                            ->where('_id', $id)
//                            ->where('con_2', $item['roundId'])
//                        ->whereNotNull('con_3')
//                            ->where('con_3', 'OPEN')
//                            ->whereNull('con_4')
//                            ->latest('created_at')
                            ->first();

                        if (!$checkBet) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }
                        if ($item['skipBalanceUpdate'] === false) {
                            $this->member->increment('balance_free', $item['payoutAmount']);
                            //$this->member->refresh();
//                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['payoutAmount']);
                        }
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'paysub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['payoutAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['gameCode'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        $id = GameLogProxy::create($session_in)->id;

                        $checkBet->con_4 = 'settle_' . $id;
                        $checkBet->save();


                    } else {

                        $checkDup = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'paysub')
//                        ->whereNotNull('con_1')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                        ->whereNotNull('con_3')
                            ->where('con_3', $item['gameCode'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if ($checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $checkBet = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'betsub')
//                        ->whereNotNull('con_1')
//                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                        ->whereNotNull('con_3')
                            ->where('con_3', 'OPEN')
//                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();


                        if (!$checkBet) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        if ($item['skipBalanceUpdate'] === false) {

                            $this->member->increment('balance_free', $item['payoutAmount']);
                            //$this->member->refresh();
                        }
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'paysub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['payoutAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['gameCode'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        $id = GameLogProxy::create($session_in)->id;

                        $checkBet->con_4 = 'settle_' . $id;
                        $checkBet->save();


                    }


                }

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'payout';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', 'FACHAI')
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
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    if ($item['status'] == 'REJECT') {
                        if ($item['transactionType'] === 'BY_TRANSACTION') {

                            $checkData = GameLogProxy::where('company', 'FACHAI')
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'betsub')
//                                ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                            ->where('amount', $item['betAmount'])
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'WAITING')
//                        ->where('con_3', 'OPEN')
                                ->whereNull('con_4')
//                                    ->orderByDesc('id')
                                ->latest('created_at')
                                ->first();


                            if (!$checkData) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;


                            }


                            $this->member->increment('balance_free', $checkData['amount']);
                            //$this->member->refresh();


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        } else {

                            $checkData = GameLogProxy::where('company', 'FACHAI')
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'betsub')
                                ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                            ->where('amount', $item['betAmount'])
//                            ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'WAITING')
//                        ->where('con_3', 'OPEN')
//                                ->whereNull('con_4')
//                                    ->orderByDesc('id')
                                ->latest('created_at')
                                ->first();

                            if (!$checkData) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;


                            }



                            $this->member->increment('balance_free', $checkData['amount']);
                            //$this->member->refresh();


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        }


                    } else {

                        if ($item['transactionType'] === 'BY_TRANSACTION') {
                            $checkData = GameLogProxy::where('company', 'FACHAI')
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->whereIn('method', ['betsub', 'ajsub'])
//                                ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                            ->where('amount', $item['betAmount'])
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', 'OPEN')
//                        ->where('con_3', 'OPEN')
                                ->whereNull('con_4')
//                                    ->orderByDesc('id')
                                ->latest('created_at')
                                ->first();

                            if (!$checkData) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;


                            }

                            $this->member->increment('balance_free', $checkData['amount']);
                            //$this->member->refresh();


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $checkData['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            $id = GameLogProxy::create($session_in)->id;

                            $checkData->con_4 = 'cancel_' . $id;
                            $checkData->save();

                        } else {

                            $amountsub = 0;

                            $checkDatas = GameLogProxy::where('company', 'FACHAI')
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->whereIn('method', ['betsub', 'ajsub'])
                                ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                            ->where('amount', $item['betAmount'])
//                            ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
//                                ->where('con_3', 'OPEN')
//                        ->where('con_3', 'OPEN')
//                                ->whereNull('con_4')
//                                    ->orderByDesc('id')
                                ->latest('created_at')
                                ->get();

                            if (count($checkDatas) < 1) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20001,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;


                            }

//                            foreach ($checkDatas as $checkData) {
//                                $amountsub += $checkData['amount'];
//                                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $checkData['amount']);
//                            }

                            $this->member->increment('balance_free', $item['betAmount']);
                            //$this->member->refresh();

//                            $member = MemberProxy::where('user_name', $session['username'])->first();


                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['status'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            $id = GameLogProxy::create($session_in)->id;

                            foreach ($checkDatas as $checkData) {
                                $checkData->con_4 = 'cancel_' . $id;
                                $checkData->save();
                            }

                        }


                    }


//
//                    if (!is_null($checkData['con_4'])) {
//                        if (Str::contains($checkData['con_4'], 'bet')) {
//                            $param = [
//                                'id' => $session['id'],
//                                'statusCode' => 20001,
//                                'timestampMillis' => now()->getTimestampMs(),
//                                'balance' => (float)$this->member->balance_free_free,
//                                'productId' => $session['productId']
//                            ];
//                            break;
////                        } else if (Str::contains($checkData['con_4'], 'cancel')) {
//
//                        } else {
//                            $param = [
//                                'id' => $session['id'],
//                                'statusCode' => 20004,
//                                'timestampMillis' => now()->getTimestampMs(),
//                                'balance' => (float)$this->member->balance_free_free,
//                                'productId' => $session['productId']
//                            ];
//                            break;
//
//                        }
//
//
//                    }


//                    $amountsub = 0;


                }
            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'cancel';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(7));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'unsettle';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'unsettlesub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['gameCode'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;
                    }


                    if ($item['betAmount'] > 0) {


                        $this->member->decrement('balance_free', $item['betAmount']);
                        //$this->member->refresh();
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['gameCode'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                        continue;

                    }

                    $checkData = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'paysub')
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['gameCode'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $balance = ($this->member->balance_free_free - $item['payoutAmount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $this->member->decrement('balance_free', $item['payoutAmount']);
                    //$this->member->refresh();

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FACHAI';
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'unsettlesub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['gameCode'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'unsettle_' . $id;
                    $checkData->save();

                    GameLogProxy::where('con_4', 'settle_' . $checkData['_id'])->update(['con_4' => null]);


                }

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'unsettle';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'adjust';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', 'FACHAI')
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

//                            $amount = $item['betAmount'] - $this->member->balance_free_free;
//
//                            MemberProxy::where('user_name', $session['username'])->update(['balance' => $amount]);
//                            $member = MemberProxy::where('user_name', $session['username'])->first();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }

                        if ($item['betAmount'] < $checkDup['amount']) {

                            $amount = $checkDup['amount'] - $item['betAmount'];


                            $this->member->increment('balance_free', $amount);
                            //$this->member->refresh();


                        } else if ($item['betAmount'] > $checkDup['amount']) {

                            $amount = $item['betAmount'] - $checkDup['amount'];

                            $balance = $this->member->balance_free_free - $amount;

                            if ($balance < 0) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;
                            }

                            $this->member->decrement('balance_free', $amount);
                            //$this->member->refresh();
//                            $member = MemberProxy::where('user_name', $session['username'])->first();

                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    } else {


                        $checkData = GameLogProxy::where('company', 'FACHAI')
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
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }


                        if ($item['betAmount'] < $checkData['amount']) {

                            $amount = $checkData['amount'] - $item['betAmount'];

                            $this->member->increment('balance_free', $amount);
                            //$this->member->refresh();

                        } else if ($item['betAmount'] > $checkData['amount']) {

//                            $amount = $item['betAmount'] - $checkData['amount'];

//                            $balance = $this->member->balance_free_free - $amount;

                            $amount = $item['betAmount'] - $checkData['amount'];

                            if ($amount > $this->member->balance_free) {
                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$this->member->balance_free_free,
                                    'productId' => $session['productId']
                                ];
                                break;
                            }



                            $this->member->decrement('balance_free', $amount);
                            //$this->member->refresh();


//                            if ($balance < 0) {
//                                $param = [
//                                    'id' => $session['id'],
//                                    'statusCode' => 10003,
//                                    'timestampMillis' => now()->getTimestampMs(),
//                                    'balance' => (float)$this->member->balance_free_free,
//                                    'productId' => $session['productId']
//                                ];
//                                break;
//                            }
//
//                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $amount);
//                            $member = MemberProxy::where('user_name', $session['username'])->first();

                        }

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    }
                }
            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'adjust';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- ADJUST --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function adjustBalance(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

//        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- ADJUST BALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['amount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'ajb';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

//                    $checkDup = GameLogProxy::where('company', 'FACHAI')
//                        ->where('response', 'in')
//                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'ajbsub')
//                        ->where('con_1', $item['refId'])
//                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
//                        ->whereNull('con_4')
//                        ->latest('created_at')
//                        ->first();


                    if ($item['status'] == 'DEBIT') {

                        $balance = $this->member->balance_free_free - $item['amount'];

                        if ($balance < 0) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];
                            break;
                        }

                        $this->member->decrement('balance_free', $item['amount']);
                        //$this->member->refresh();
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajbsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['amount'];
                        $session_in['con_1'] = $item['refId'];
                        $session_in['con_2'] = $item['status'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    } else if ($item['status'] == 'CREDIT') {


                        $this->member->increment('balance_free', $item['amount']);
                        //$this->member->refresh();
//                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'ajbsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['amount'];
                        $session_in['con_1'] = $item['refId'];
                        $session_in['con_2'] = $item['status'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }
                }
            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'ajb';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- ADJUST --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function winRewards(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balanceAfter' => (float)$this->member->balance_free_free,
                    'timestampMillis' => now()->getTimestampMs()
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'win';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];


                    $datasub = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'winsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['gameCode'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];
                        break;


                    } else {


                        $datasubs = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'unsettlesub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['gameCode'])
                            ->where('con_4', 'complete')
                            ->latest('created_at')
                            ->first();

                        if ($datasubs) {

                            $datasubss = GameLogProxy::where('company', 'FACHAI')
                                ->where('response', 'in')
                                ->where('game_user', $this->member->user_name)
                                ->where('method', 'paysub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['gameCode'])
                                ->whereNull('con_4')
                                ->latest('created_at')
                                ->first();

                            if ($datasubss) {

                                $this->member->increment('balance_free', $item['payoutAmount']);
                                //$this->member->refresh();
//                                $member = MemberProxy::where('user_name', $session['username'])->first();


                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $this->member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$this->member->balance_free_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'FACHAI';
                                $session_in['game_user'] = $this->member->user_name;
                                $session_in['method'] = 'winsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['payoutAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['gameCode'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $this->member->balance_free_free;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);
                            }

                        } else {

                            $this->member->increment('balance_free', $item['payoutAmount']);
                            //$this->member->refresh();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $this->member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$this->member->balance_free_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];


                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'FACHAI';
                            $session_in['game_user'] = $this->member->user_name;
                            $session_in['method'] = 'winsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['payoutAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['gameCode'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $this->member->balance_free_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        }


                    }

                } // loop

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'win';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/FACHAI' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- WIN --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function rollback(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'rollback';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

//                    $checkData = GameLogProxy::where('company', 'FACHAI')
//                        ->where('response', 'in')
//                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'refundsub')
////                        ->whereNotNull('con_1')
////                        ->whereNotNull('con_3')
////                        ->where('amount', $item['payoutAmount'])
////                        ->where('con_1', $item['id'])
//                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
//                        ->whereNull('con_4')
//                        ->latest('created_at')
//                        ->first();
//
//                    if($checkData)

//                    $checkDup = GameLogProxy::where('company', 'FACHAI')
//                        ->where('response', 'in')
//                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'rollsub')
//                        ->where('con_1', $item['id'])
//                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
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
//                            'balance' => (float)$this->member->balance_free_free,
//                            'productId' => $session['productId']
//                        ];
//
//                        break;
//                    }


//                    if ($item['betAmount'] > 0) {
//
//                        MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                        $member = MemberProxy::where('user_name', $session['username'])->first();
//
//                        $param = [
//                            'id' => $session['id'],
//                            'statusCode' => 0,
//                            'currency' => "THB",
//                            'productId' => $session['productId'],
//                            'username' => $this->member->user_name,
//                            'balanceBefore' => (float)$oldbalance,
//                            'balanceAfter' => (float)$this->member->balance_free_free,
//                            'timestampMillis' => now()->getTimestampMs()
//                        ];
//
//                        $session_in['input'] = $item;
//                        $session_in['output'] = $param;
//                        $session_in['company'] = 'FACHAI';
//                        $session_in['game_user'] = $this->member->user_name;
//                        $session_in['method'] = 'betsub';
//                        $session_in['response'] = 'in';
//                        $session_in['amount'] = $item['betAmount'];
//                        $session_in['con_1'] = $item['id'];
//                        $session_in['con_2'] = $item['roundId'];
//                        $session_in['con_3'] = $item['gameCode'];
//                        $session_in['con_4'] = null;
//                        $session_in['before_balance'] = $oldbalance;
//                        $session_in['after_balance'] = $this->member->balance_free_free;
//                        $session_in['date_create'] = now()->toDateTimeString();
//                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                        GameLogProxy::create($session_in);
//
//                        break;
//
//                    }

//                    $checkDup = GameLogProxy::where('company', 'FACHAI')
//                        ->where('response', 'in')
//                        ->where('game_user', $this->member->user_name)
//                        ->where('method', 'rollsub')
////                        ->whereNotNull('con_1')
////                        ->whereNotNull('con_3')
////                        ->where('amount', $item['payoutAmount'])
//                        ->where('con_1', $item['id'])
//                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
//                        ->whereNull('con_4')
//                        ->latest('created_at')
//                        ->first();
//
//                    if ($checkDup) {
//                        $param = [
//                            'id' => $session['id'],
//                            'statusCode' => 20002,
//                            'timestampMillis' => now()->getTimestampMs(),
//                            'balance' => (float)$this->member->balance_free_free,
//                            'productId' => $session['productId']
//                        ];
//
//                        break;
//                    }

                    if ($item['transactionType'] === 'BY_TRANSACTION') {

                        $checkData = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->whereIn('method', ['paysub', 'refundsub'])
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                    } else {

                        $checkDup = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->where('method', 'rollsub')
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['gameCode'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if ($checkDup) {
                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                        $checkData = GameLogProxy::where('company', 'FACHAI')
                            ->where('response', 'in')
                            ->where('game_user', $this->member->user_name)
                            ->whereIn('method', ['paysub', 'refundsub'])
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
//                        ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['gameCode'])
                            ->whereNull('con_4')
                            ->latest('created_at')
                            ->first();

                        if (!$checkData) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                    }


                    $balance = ($this->member->balance_free_free - ($item['payoutAmount'] + $item['betAmount']));

//                    if ($balance < 0) {
//
//                        $param = [
//                            'id' => $session['id'],
//                            'statusCode' => 10002,
//                            'timestampMillis' => now()->getTimestampMs(),
//                            'balance' => (float)$this->member->balance_free_free,
//                            'productId' => $session['productId']
//                        ];
//
//                        break;
//
//                    }

                    $this->member->decrement('balance_free', $item['payoutAmount']);
                    $this->member->decrement('balance_free', $item['betAmount']);
                    //$this->member->refresh();

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FACHAI';
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'rollsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['payoutAmount'] + $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['gameCode'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
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

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'rollback';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'void';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'voidsub')
//                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['gameCode'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if ($checkDup) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $checkData = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->whereIn('method', ['paysub', 'winsub'])
                        ->whereNotNull('con_1')
//                        ->whereNotNull('con_3')
//                        ->where('amount', $item['payoutAmount'])
//                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['gameCode'])
                        ->whereNull('con_4')
                        ->latest('created_at')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $amountsub = $item['betAmount'] - $item['payoutAmount'];
                    $balance = $this->member->balance_free_free + ($item['betAmount'] - $item['payoutAmount']);

                    if ($balance < 0) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 10002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];

                        break;

                    }

                    $this->member->increment('balance_free', $item['betAmount']);
                    $this->member->decrement('balance_free', $item['payoutAmount']);
//                    MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                    $member = MemberProxy::where('user_name', $session['username'])->first();
                    //$this->member->refresh();
                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FACHAI';
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'voidsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amountsub;
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['gameCode'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

//                    $checkData->con_4 = 'rollback_' . $id;
//                    $checkData->save();
//
//                    if ($checkData['method'] == 'paysub') {
//
//                        GameLogProxy::where('con_4', 'settle_' . $checkData['_id'])->update(['con_4' => null]);
//
//                    } else {
//
//                        GameLogProxy::where('con_4', 'cancel_' . $checkData['_id'])->update(['con_4' => null]);
//
//                    }


                }

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'void';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'tips';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'FACHAI')
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
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {

                        $balance = ($this->member->balance_free_free - $item['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$this->member->balance_free_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                        $this->member->decrement('balance_free', $item['betAmount']);
//                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['betAmount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                        //$this->member->refresh();


//                            $this->member->balance_free_free -= $item['betAmount'];
//                            $this->member->save();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $this->member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$this->member->balance_free_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FACHAI';
                        $session_in['game_user'] = $this->member->user_name;
                        $session_in['method'] = 'tipsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['status'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $this->member->balance_free_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                }


            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'tips';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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

//        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($this->member) {

            $oldbalance = $this->member->balance_free_free;

            $data = GameLogProxy::where('company', 'FACHAI')
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
                    'balance' => (float)$this->member->balance_free_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FACHAI';
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'canceltip';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance_free_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $checkDup = GameLogProxy::where('company', 'FACHAI')
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
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                    $checkData = GameLogProxy::where('company', 'FACHAI')
                        ->where('response', 'in')
                        ->where('game_user', $this->member->user_name)
                        ->where('method', 'tipsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
//                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if (!$checkData) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$this->member->balance_free_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

//                    if ($datasubs) {

//                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
                    $this->member->increment('balance_free', $item['betAmount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                    //$this->member->refresh();

//                            $this->member->balance_free_free += $datasubs['amount'];
//                            $this->member->save();

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'currency' => "THB",
                        'productId' => $session['productId'],
                        'username' => $this->member->user_name,
                        'balanceBefore' => (float)$oldbalance,
                        'balanceAfter' => (float)$this->member->balance_free_free,
                        'timestampMillis' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $item;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FACHAI';
                    $session_in['game_user'] = $this->member->user_name;
                    $session_in['method'] = 'ctsub';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $item['betAmount'];
                    $session_in['con_1'] = $item['id'];
                    $session_in['con_2'] = $item['roundId'];
                    $session_in['con_3'] = $item['status'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $this->member->balance_free_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    $id = GameLogProxy::create($session_in)->id;

                    $checkData->con_4 = 'canceltip_' . $id;
                    $checkData->save();

//                    } else {
//
//                        $param = [
//                            'id' => $session['id'],
//                            'statusCode' => 20001,
//                            'timestampMillis' => now()->getTimestampMs(),
//                            'balance' => (float)$this->member->balance_free_free,
//                            'productId' => $session['productId']
//                        ];
//                        break;
//
//                    }

                }
            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FACHAI';
//            $session_in['game_user'] = $this->member->user_name;
//            $session_in['method'] = 'canceltip';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $amount;
//            $session_in['con_1'] = $session['id'];
//            $session_in['con_2'] = $session['productId'];
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $this->member->balance_free_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

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
