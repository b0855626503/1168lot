<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class WmCasinoController extends AppBaseController
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


    public function verify(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['member']['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'data' => [
                    'player_name' => $member->user_name,
                    'nickname' => $member->user_name,
                    'currency' => 'THB',
                    'reminder_time' => now()->timestamp
                ],
                'error' => null
            ];
        } else {
            $param = [
                'data' => null,
                'error' => [
                    'code' => 3004,
                    'message' => "Player isn't exist"
                ]
            ];
        }

        return $param;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'errorCode' => 0,
                'result' => [
                    'balance' => (float)$member->balance_free
                ]
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
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
                'errorCode' => 10501,
                'errorMessage' => 'No such account was found, please check',
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'WM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['gameId'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance_free
                    ]
                ];

            } else {


                $datasub = GameLogProxy::where('company', 'WM')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'refund')
                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['roundId'])
                    ->where('con_3', $session['gameId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'errorCode' => 0,
                        'result' => [
                            'balance' => (float)$member->balance_free
                        ]
                    ];


                } else {

                    $balance = ($member->balance_free - $session['amount']);
                    if ($balance >= 0) {
                        MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $session['amount']);
                        $member = MemberProxy::where('user_name', $session['username'])->first();


//                        $member->balance_free -= $session['amount'];
//                        $member->save();

                        $param = [
                            'errorCode' => 0,
                            'result' => [
                                'balance' => (float)$member->balance_free
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'WM';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['betId'];
                        $session_in['con_2'] = $session['roundId'];
                        $session_in['con_3'] = $session['gameId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    } else {
                        $param = [
                            'errorCode' => 10805,
                            'errorMessage' => 'Insufficient balance',
                        ];
                    }
                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['roundId'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'errorCode' => 10501,
                'errorMessage' => 'No such account was found, please check',
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'WM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['gameId'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance_free
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'WM')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['roundId'])
                    ->where('con_3', $session['gameId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();


//                    $member->balance_free += $session['amount'];
//                    $member->save();

                }

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance_free
                    ]
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'WM';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['betId'];
                $session_in['con_2'] = $session['roundId'];
                $session_in['con_3'] = $session['gameId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['roundId'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'errorCode' => 10501,
                'errorMessage' => 'No such account was found, please check',
            ];

        }


        return $param;
    }

    public function refund(Request $request)
    {
        $param = [];
        $session = $request->all();
        $amount = 0;
        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

//            $balance = ($member->balance_free + $session['amount']);
//
//
//            $member->balance_free += $session['amount'];
//            $member->save();
//
//            $param = [
//                'errorCode' => 0,
//                'result' => [
//                    'balance' => (float)$member->balance_free
//                ]
//            ];
//
//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'WM';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'refund';
//            $session_in['response'] = 'in';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['betId'];
//            $session_in['con_2'] = $session['roundId'];
//            $session_in['con_3'] = $session['gameId'];
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

            $data = GameLogProxy::where('company', 'WM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'refund')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['gameId'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance_free
                    ]
                ];

            } else {


                $datasub = GameLogProxy::where('company', 'WM')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['roundId'])
                    ->where('con_3', $session['gameId'])
                    ->whereNull('con_4')
                    ->first();

                $amount = $session['amount'];

                if ($datasub) {

                    $amount = $datasub['amount'];

                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasub['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

//                    $member->balance_free += $session['amount'];
//                    $member->save();


                }

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance_free
                    ]
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'WM';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'refund';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['betId'];
                $session_in['con_2'] = $session['roundId'];
                $session_in['con_3'] = $session['gameId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'refund';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['roundId'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'errorCode' => 10501,
                'errorMessage' => 'No such account was found, please check',
            ];

        }


        return $param;
    }


}
