<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class KingMakerController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository      $memberRepo,
        GameUserRepository    $gameUserRepo
    )
    {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;
    }


    public function transaction(Request $request)
    {
        $param = [];
        $session = $request->all();
        if ($session['productId'] != 'KINGMAKER2') {
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

            $oldbalance = $member->balance_free;
            $amount = 0;
            foreach ($session['txns'] as $item) {

                $data = GameLogProxy::where('company', 'KINGMAKER2')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'transub')
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['txnId'])
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
                        $check = $member->balance_free - $amount;
                    } else {
                        $amount = $item['payoutAmount'] - $item['betAmount'];
                        $check = $member->balance_free + $amount;
                    }

                    if ($check >= 0) {

                        $member->balance_free = $check;

                        $member->save();
                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'productId' => $session['productId'],
                            'timestampMillis' => now()->getTimestampMs(),
                            'currency' => 'THB',
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'username' => $session['username']
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'KINGMAKER2';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'transub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $amount;
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
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
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'transub';
                $session_in['response'] = 'out';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $item['id'];
                $session_in['con_2'] = $item['roundId'];
                $session_in['con_3'] = $item['txnId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
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


        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- TRANSACTION --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => "THB",
                'productId' => $session['productId'],
                'username' => $member->user_name,
                'balance' => (float)$member->balance_free,
                'timestampMillis' => now()->getTimestampMs()
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $member->balance_free;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];
        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
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

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
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
                    'balance' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {
//                    $amount += $item['betAmount'];

                    $data_sub = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'refundsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($data_sub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {


                        $datasub = GameLogProxy::where('company', 'KINGMAKER2')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'betsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasub) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];

                        } else {

                            if ($item['betAmount'] >= 0) {
                                $balance = ($member->balance_free - $item['betAmount']);
                            } else {
                                $balance = ($member->balance_free - abs($item['betAmount']));
                            }

                            if ($balance >= 0) {
                                MemberProxy::where('user_name', $session['username'])->decrement('balance_free', abs($item['betAmount']));
                                $member = MemberProxy::where('user_name', $session['username'])->first();

//                                $member->balance_free = $balance;
//                                $member->save();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                            } else {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'balance' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'productId' => $session['productId']
                                ];
                                break;

                            }

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'KINGMAKER2';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'betsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        }

                    }

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {


                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'balance' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];

                    $datasub = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'betsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $datasub_s = GameLogProxy::where('company', 'KINGMAKER2')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'refundsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasub_s) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20003,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];
                            break;

                        } else {


                            $datasubs = GameLogProxy::where('company', 'KINGMAKER2')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'paysub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->first();

                            if ($datasubs) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];


                            } else {

                                $datasubss = GameLogProxy::where('company', 'KINGMAKER2')
                                    ->where('response', 'in')
                                    ->where('game_user', $member->user_name)
                                    ->where('method', 'unsettlesub')
                                    ->where('con_1', $item['id'])
                                    ->where('con_2', $item['roundId'])
                                    ->where('con_3', $item['txnId'])
                                    ->whereNull('con_4')
                                    ->first();

                                if ($datasubss) {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 20003,
                                        'balance' => (float)$member->balance_free,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'productId' => $session['productId']
                                    ];
                                    break;

                                } else {

                                    if ($item['payoutAmount'] >= 0) {
                                        $amount = ($member->balance_free + $item['payoutAmount']);
                                    } else {
                                        $amount = ($member->balance_free + abs($item['payoutAmount']));
                                    }

                                    if ($amount >= 0) {
                                        MemberProxy::where('user_name', $session['username'])->increment('balance_free', abs($item['payoutAmount']));
                                        $member = MemberProxy::where('user_name', $session['username'])->first();

//                                        $member->balance_free = $amount;
//                                        $member->save();

                                        $param = [
                                            'id' => $session['id'],
                                            'statusCode' => 0,
                                            'currency' => "THB",
                                            'productId' => $session['productId'],
                                            'username' => $member->user_name,
                                            'balanceBefore' => (float)$oldbalance,
                                            'balanceAfter' => (float)$member->balance_free,
                                            'timestampMillis' => now()->getTimestampMs()
                                        ];


                                        $session_in['input'] = $item;
                                        $session_in['output'] = $param;
                                        $session_in['company'] = 'KINGMAKER2';
                                        $session_in['game_user'] = $member->user_name;
                                        $session_in['method'] = 'paysub';
                                        $session_in['response'] = 'in';
                                        $session_in['amount'] = $item['payoutAmount'];
                                        $session_in['con_1'] = $item['id'];
                                        $session_in['con_2'] = $item['roundId'];
                                        $session_in['con_3'] = $item['txnId'];
                                        $session_in['con_4'] = null;
                                        $session_in['before_balance'] = $oldbalance;
                                        $session_in['after_balance'] = $member->balance_free;
                                        $session_in['date_create'] = now()->toDateTimeString();
                                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                        GameLogProxy::create($session_in);

                                    } else {

                                        $param = [
                                            'id' => $session['id'],
                                            'statusCode' => 20001,
                                            'balance' => (float)$member->balance_free,
                                            'timestampMillis' => now()->getTimestampMs(),
                                            'productId' => $session['productId']
                                        ];
                                        break;

                                    }
                                }

                            }
                        }

                    } else {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'balance' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs(),
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- PAY --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'balance' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'refundsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                    } else {

                        $datasubs = GameLogProxy::where('company', 'KINGMAKER2')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'paysub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20004,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];
                            break;

                        } else {


                            $amountsub = 0;

                            $datasubss = GameLogProxy::where('company', 'KINGMAKER2')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'betsub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->first();


                            if ($datasubss) {

                                $amountsub = $datasubss['amount'];
                                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasubss['amount']);
                                $member = MemberProxy::where('user_name', $session['username'])->first();


//                                $member->balance_free += $datasubss['amount'];
//                                $member->save();


                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'KINGMAKER2';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'refundsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $amountsub;
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance_free;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);


                            } else {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'KINGMAKER2';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'refundsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['betAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance_free;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);

                            }
                        }


                    }


                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'unsettle')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20001,
                    'balance' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'unsettle';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub2 = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'paysub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub2) {

                        $balance = ($member->balance_free - $datasub2['amount']);

                        if ($balance >= 0) {
                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $datasub2['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();


//                            $member->balance_free -= $datasub2['amount'];
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'KINGMAKER2';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'unsettlesub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $datasub2['amount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

//                            $datasub2->con_4 = 'complete';
//                            $datasub2->save();

                            GameLogProxy::where('company', 'KINGMAKER2')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'paysub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->update(['con_4' => 'complete']);


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];

                            break;
                        }

                    } else {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'balance' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs(),
                            'productId' => $session['productId']
                        ];

                        break;

                    }
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'unsettle';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- UNSETTLE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function adjustBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'adjust')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20001,
                    'balance' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'adjust';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'ajsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $adjust = $item['betAmount'] - $datasub['amount'];

                        $newbalance = $member->balance_free - $adjust;

//                        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//                        file_put_contents($path, print_r('-- CAL ADJUST --', true), FILE_APPEND);
//                        file_put_contents($path, print_r($item['betAmount'] . ' - ' . $datasub['amount'], true), FILE_APPEND);

//                        if ($adjust >= 0) {

                        if ($newbalance >= 0) {
                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $adjust);
                            $member = MemberProxy::where('user_name', $session['username'])->first();


//                            $member->balance_free = $member->balance_free - $adjust;
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            GameLogProxy::where('company', 'KINGMAKER2')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'ajsub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->update(['con_4' => 'complete']);

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'KINGMAKER2';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'ajsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];
                            break;

                        }


                    } else {

                        $datasubs = GameLogProxy::where('company', 'KINGMAKER2')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'betsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

                            $adjust = $item['betAmount'] - $datasubs['amount'];

                            if ($adjust >= 0) {

                                $newbalance = $member->balance_free - $adjust;

                                if ($newbalance >= 0) {
                                    MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $adjust);
                                    $member = MemberProxy::where('user_name', $session['username'])->first();


//                                    $member->balance_free -= $adjust;
//                                    $member->save();

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 0,
                                        'currency' => "THB",
                                        'productId' => $session['productId'],
                                        'username' => $member->user_name,
                                        'balanceBefore' => (float)$oldbalance,
                                        'balanceAfter' => (float)$member->balance_free,
                                        'timestampMillis' => now()->getTimestampMs()
                                    ];

                                    $session_in['input'] = $item;
                                    $session_in['output'] = $param;
                                    $session_in['company'] = 'KINGMAKER2';
                                    $session_in['game_user'] = $member->user_name;
                                    $session_in['method'] = 'ajsub';
                                    $session_in['response'] = 'in';
                                    $session_in['amount'] = $item['betAmount'];
                                    $session_in['con_1'] = $item['id'];
                                    $session_in['con_2'] = $item['roundId'];
                                    $session_in['con_3'] = $item['txnId'];
                                    $session_in['con_4'] = null;
                                    $session_in['before_balance'] = $oldbalance;
                                    $session_in['after_balance'] = $member->balance_free;
                                    $session_in['date_create'] = now()->toDateTimeString();
                                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                    GameLogProxy::create($session_in);

                                } else {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 10002,
                                        'balance' => (float)$member->balance_free,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'productId' => $session['productId']
                                    ];
                                    break;

                                }

                            } else {

                                MemberProxy::where('user_name', $session['username'])->increment('balance_free', abs($adjust));
                                $member = MemberProxy::where('user_name', $session['username'])->first();

//                                $member->balance_free += abs($adjust);
//                                $member->save();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'KINGMAKER2';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'ajsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['betAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance_free;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);

                            }


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'balance' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];
                            break;

                        }
                    }
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'adjust';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
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

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'KINGMAKER2')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'win')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {


                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => "THB",
                    'productId' => $session['productId'],
                    'username' => $member->user_name,
                    'balanceBefore' => (float)$oldbalance,
                    'balanceAfter' => (float)$member->balance_free,
                    'timestampMillis' => now()->getTimestampMs()
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'KINGMAKER2';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'win';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];

                    $datasub = GameLogProxy::where('company', 'KINGMAKER2')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'winsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                    } else {

                        MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['payoutAmount']);
                        $member = MemberProxy::where('user_name', $session['username'])->first();


//                        $member->balance_free += $item['payoutAmount'];
//                        $member->save();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'KINGMAKER2';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'winsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['payoutAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                } // loop

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'KINGMAKER2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'win';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId']
            ];

        }

//        $path = storage_path('logs/seamless/KINGMAKER2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- WIN --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

}
