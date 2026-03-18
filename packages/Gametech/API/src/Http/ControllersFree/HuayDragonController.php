<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use MongoDB\BSON\UTCDateTime;

class HuayDragonController extends AppBaseController
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


    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'balance' => (float)$member->balance
                ]
            ];


        } else {
            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'balance' => 0
                ]
            ];
        }
//
        $path = storage_path('logs/seamless/HuayDragon' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            MemberProxy::where('user_name', $session['username'])->decrement('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();


            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString()
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance
                    ],
                    'refId' => $session['refId']
                ]
            ];

        }

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return Response::json($param);
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();


            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString()
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance
                    ],
                    'refId' => $session['refId']
                ]
            ];

        }

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- PAY --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return Response::json($param);
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();


            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString()
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance
                    ],
                    'refId' => $session['refId']
                ]
            ];

        }

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return Response::json($param);
    }

    public function cancelNumber(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();


            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString()
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance
                    ],
                    'refId' => $session['refId']
                ]
            ];

        }

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return Response::json($param);
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            MemberProxy::where('user_name', $session['username'])->decrement('balance', abs($session['amount']));
            $member = MemberProxy::where('user_name', $session['username'])->first();


            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success'
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString()
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance
                    ],
                    'refId' => $session['refId']
                ]
            ];

        }

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- UNSETTLE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return Response::json($param);
    }

    public function adjustBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'ACE333')
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
                    'balance' => (float)$member->balance,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'ACE333';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'adjust';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'ACE333')
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

                        $newbalance = $member->balance - $adjust;

//                        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//                        file_put_contents($path, print_r('-- CAL ADJUST --', true), FILE_APPEND);
//                        file_put_contents($path, print_r($item['betAmount'] . ' - ' . $datasub['amount'], true), FILE_APPEND);

//                        if ($adjust >= 0) {

                        if ($newbalance >= 0) {

                            MemberProxy::where('user_name', $session['username'])->decrement('balance', $adjust);
                            $member = MemberProxy::where('user_name', $session['username'])->first();


//                            $member->balance = $member->balance - $adjust;
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            GameLogProxy::where('company', 'ACE333')
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
                            $session_in['company'] = 'ACE333';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'ajsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'balance' => (float)$member->balance,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId']
                            ];
                            break;

                        }


                    } else {

                        $datasubs = GameLogProxy::where('company', 'ACE333')
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

                                $newbalance = $member->balance - $adjust;

                                if ($newbalance >= 0) {

                                    MemberProxy::where('user_name', $session['username'])->decrement('balance', $adjust);
                                    $member = MemberProxy::where('user_name', $session['username'])->first();


                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 0,
                                        'currency' => "THB",
                                        'productId' => $session['productId'],
                                        'username' => $member->user_name,
                                        'balanceBefore' => (float)$oldbalance,
                                        'balanceAfter' => (float)$member->balance,
                                        'timestampMillis' => now()->getTimestampMs()
                                    ];

                                    $session_in['input'] = $item;
                                    $session_in['output'] = $param;
                                    $session_in['company'] = 'ACE333';
                                    $session_in['game_user'] = $member->user_name;
                                    $session_in['method'] = 'ajsub';
                                    $session_in['response'] = 'in';
                                    $session_in['amount'] = $item['betAmount'];
                                    $session_in['con_1'] = $item['id'];
                                    $session_in['con_2'] = $item['roundId'];
                                    $session_in['con_3'] = $item['txnId'];
                                    $session_in['con_4'] = null;
                                    $session_in['before_balance'] = $oldbalance;
                                    $session_in['after_balance'] = $member->balance;
                                    $session_in['date_create'] = now()->toDateTimeString();
                                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                    GameLogProxy::create($session_in);

                                } else {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 10002,
                                        'balance' => (float)$member->balance,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'productId' => $session['productId']
                                    ];
                                    break;

                                }

                            } else {

                                MemberProxy::where('user_name', $session['username'])->increment('balance', abs($adjust));
                                $member = MemberProxy::where('user_name', $session['username'])->first();


                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'ACE333';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'ajsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['betAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);

                            }


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'balance' => (float)$member->balance,
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
            $session_in['company'] = 'ACE333';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'adjust';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
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

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
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

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'ACE333')
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
                    'balanceAfter' => (float)$member->balance,
                    'timestampMillis' => now()->getTimestampMs()
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'ACE333';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'win';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];

                    $datasub = GameLogProxy::where('company', 'ACE333')
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
                            'balanceAfter' => (float)$member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                    } else {

                        MemberProxy::where('user_name', $session['username'])->increment('balance', $item['payoutAmount']);
                        $member = MemberProxy::where('user_name', $session['username'])->first();


//                        $member->balance += $item['payoutAmount'];
//                        $member->save();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance,
                            'timestampMillis' => now()->getTimestampMs()
                        ];


                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'ACE333';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'winsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['payoutAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                } // loop

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ACE333';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'win';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
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

//        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- WIN --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

}
