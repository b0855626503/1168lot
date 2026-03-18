<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class FunkyGameController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'errorCode' => 0,
                'errorMessage' => 'No Error',
                'data' => [
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ]
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'FUNKY';
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
                'errorCode' => 401,
                'errorMessage' => 'Player Is Not Login',
                'data' => [
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];

        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'FUNKY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['bet']['gameCode'])
                ->where('con_2', $session['bet']['refNo'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'errorCode' => 403,
                    'errorMessage' => 'Bet already exists',
                    'data' => [
                        'balance' => (float)$member->balance_free,
                        'currency' => 'THB'
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'FUNKY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['bet']['refNo'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'errorCode' => 0,
                        'errorMessage' => 'No Error',
                        'data' => [
                            'balance' => (float)$member->balance_free,
                            'currency' => 'THB'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FUNKY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['bet']['stake'];
                    $session_in['con_1'] = $session['bet']['gameCode'];
                    $session_in['con_2'] = $session['bet']['refNo'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $balance = ($oldbalance - $session['bet']['stake']);
                    if ($balance >= 0) {

                        MemberProxy::where('user_name', $member->user_name)->decrement('balance_free', $session['bet']['stake']);
                        $member = MemberProxy::where('user_name', $member->user_name)->first();

//                        $member->balance_free -= $session['bet']['stake'];
//                        $member->save();

                        $param = [
                            'errorCode' => 0,
                            'errorMessage' => 'No Error',
                            'data' => [
                                'balance' => (float)$member->balance_free,
                                'currency' => 'THB'
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FUNKY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['bet']['stake'];
                        $session_in['con_1'] = $session['bet']['gameCode'];
                        $session_in['con_2'] = $session['bet']['refNo'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    } else {

                        $param = [
                            'errorCode' => 402,
                            'errorMessage' => 'Insufficient Balance',
                            'data' => [
                                'balance' => (float)$member->balance_free,
                                'currency' => 'THB'
                            ]
                        ];
                    }

                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'FUNKY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['bet']['stake'];
            $session_in['con_1'] = $session['bet']['gameCode'];
            $session_in['con_2'] = $session['bet']['refNo'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'errorCode' => 401,
                'errorMessage' => 'Player Is Not Login',
                'data' => [
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['betResultReq']['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'FUNKY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['betResultReq']['gameCode'])
                ->where('con_2', $session['refNo'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'errorMessage' => 'No Error',
                    'data' => [
                        'refNo' => $session['refNo'],
                        'balance' => (float)$member->balance_free,
                        'playerId' => $session['betResultReq']['playerId'],
                        'statementDate' => now()->subDay()->toDateString()
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'FUNKY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['betResultReq']['gameCode'])
                    ->where('con_2', $session['refNo'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $data_sub = GameLogProxy::where('company', 'FUNKY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'cancel')
                        ->where('con_1', $session['refNo'])
                        ->whereNull('con_2')
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($data_sub) {

                        $param = [
                            'errorCode' => 410,
                            'errorMessage' => 'Bet was already cancelled',
                            'data' => [
                                'balance' => (float)$member->balance_free,
                                'currency' => 'THB'
                            ]
                        ];

                    } else {

                        $balance = ($oldbalance + $session['betResultReq']['winAmount']);

                        MemberProxy::where('user_name', $member->user_name)->increment('balance_free', $session['betResultReq']['winAmount']);
                        $member = MemberProxy::where('user_name', $member->user_name)->first();

//                        $member->balance_free += $session['betResultReq']['winAmount'];
//                        $member->save();

                        $param = [
                            'errorCode' => 0,
                            'errorMessage' => 'No Error',
                            'data' => [
                                'refNo' => $session['refNo'],
                                'balance' => (float)$member->balance_free,
                                'playerId' => $session['betResultReq']['playerId'],
                                'statementDate' => now()->subDay()->toDateString()
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FUNKY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'payout';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['betResultReq']['winAmount'];
                        $session_in['con_1'] = $session['betResultReq']['gameCode'];
                        $session_in['con_2'] = $session['refNo'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                } else {

                    $param = [
                        'errorCode' => 404,
                        'errorMessage' => 'Bet was not found',
                        'data' => [
                            'balance' => (float)$member->balance_free,
                            'currency' => 'THB'
                        ]
                    ];

                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'FUNKY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['betResultReq']['winAmount'];
            $session_in['con_1'] = $session['betResultReq']['gameCode'];
            $session_in['con_2'] = $session['refNo'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'errorCode' => 401,
                'errorMessage' => 'Player Is Not Login',
                'data' => [
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];
        }


        return $param;
    }

    public function cancelBets(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'FUNKY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['refNo'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'errorMessage' => 'No Error',
                    'data' => [
                        'refNo' => $session['refNo']
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'FUNKY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_2', $session['refNo'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {


                    $data_sub = GameLogProxy::where('company', 'FUNKY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
                        ->where('con_2', $session['refNo'])
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($data_sub) {

                        $param = [
                            'errorCode' => 409,
                            'errorMessage' => 'Bet was already settled',
                            'data' => [
                                'balance' => 0,
                                'currency' => 'THB'
                            ]
                        ];

                    } else {

                        $balance = ($oldbalance + $datasub['amount']);
//                        $member->balance_free += $datasub['amount'];
//                        $member->save();

                        MemberProxy::where('user_name', $member->user_name)->increment('balance_free', $datasub['amount']);
                        $member = MemberProxy::where('user_name', $member->user_name)->first();


                        $param = [
                            'errorCode' => 0,
                            'errorMessage' => 'No Error',
                            'data' => [
                                'refNo' => $session['refNo']
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'FUNKY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'cancel';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $datasub['amount'];
                        $session_in['con_1'] = $session['refNo'];
                        $session_in['con_2'] = null;
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }


                } else {

                    $param = [
                        'errorCode' => 0,
                        'errorMessage' => 'No Error',
                        'data' => [
                            'refNo' => $session['refNo']
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'FUNKY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = 0;
                    $session_in['con_1'] = $session['refNo'];
                    $session_in['con_2'] = null;
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                }

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FUNKY';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'cancel';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['refNo'];
//            $session_in['con_2'] = null;
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

        } else {

            $param = [
                'errorCode' => 401,
                'errorMessage' => 'Player Is Not Login',
                'data' => [
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];


        }


        return $param;
    }

    public function checkBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

//            $data = GameLogProxy::where('company', 'FUNKY')
//                ->where('response', 'in')
//                ->where('game_user', $member->user_name)
//                ->where('method', 'checkbet')
//                ->where('con_1', $session['id'])
//                ->whereNull('con_2')
//                ->whereNull('con_3')
//                ->whereNull('con_4')
//                ->first();

            $oldbalance = $member->balance_free;

//            if ($data) {
//
//                $param = [
//                    'errorCode' => 0,
//                    'errorMessage' => 'No Error',
//                    'data' => [
//                        'refNo' => $session['refNo'],
//                        'balance' => (float)$member->balance_free,
//                        'playerId' => $session['betResultReq']['playerId'],
//                        'statementDate' => now()->toDateString()
//                    ]
//                ];
//
//            } else {

            $winamount = 0;
            $data = GameLogProxy::where('company', 'FUNKY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_2', $session['id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $datasubs = GameLogProxy::where('company', 'FUNKY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_2', $session['id'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasubs) {

                    $status = 'C';

                } else {

                    $datasub = GameLogProxy::where('company', 'FUNKY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
                        ->where('con_2', $session['id'])
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $winamount = $datasub['amount'];

                        if ($datasub['amount'] > $data['amount']) {
                            $status = 'W';
                        } else if ($data['amount'] > $datasub['amount']) {
                            $status = 'L';
                        } else if ($data['amount'] == $datasub['amount']) {
                            $status = 'D';
                        }


                    } else {
                        $status = 'R';
                    }

                }

                $param = [
                    'errorCode' => 0,
                    'errorMessage' => 'No Error',
                    'data' => [
                        'refNo' => $session['id'],
                        'stake' => (float)$data['amount'],
                        'winAmount' => (float)$winamount,
                        'status' => $status,
                        'statementDate' => now()->subDay()->toDateString()
                    ]
                ];

//                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'FUNKY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'checkbet';
                $session_in['response'] = 'in';
                $session_in['amount'] = 0;
                $session_in['con_1'] = $session['id'];
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
                    'errorCode' => 404,
                    'errorMessage' => 'Bet was not found',
                    'data' => [
                        'balance' => 0,
                        'currency' => 'THB'
                    ]
                ];

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'FUNKY';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'transferlost';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = 0;
//            $session_in['con_1'] = $session['txnid'];
//            $session_in['con_2'] = null;
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $member->balance_free;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);

        } else {
            $param = [
                'errorCode' => 401,
                'errorMessage' => 'Player Is Not Login',
                'data' => [
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];
        }


        return $param;
    }


}
