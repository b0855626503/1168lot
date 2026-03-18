<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class YggdrasilController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'code' => 0,
                'msg' => 'Success',
                'data' => [
                    'currency' => 'THB',
                    'balance' => (float)$member->balance_free,
                    'country' => 'TH'
                ]
            ];

        } else {
            $param = [
                'code' => 1,
                'Description' => 'Any other error'
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
                'code' => 0,
                'msg' => 'Success',
                'data' => [
                    'currency' => 'THB',
                    'balance' => (float)$member->balance_free,
                    'country' => 'TH'
                ]
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
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
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'YGGDRASIL')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'code' => 5043,
                    'msg' => 'Bet data existed'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'YGGDRASIL')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['gameId'])
                    ->where('con_3', $session['roundId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'code' => 0,
                        'msg' => 'Success',
                        'data' => [
                            'currency' => 'THB',
                            'balance' => (float)$member->balance_free,
                            'country' => 'TH'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'YGGDRASIL';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['gameId'];
                    $session_in['con_3'] = $session['roundId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {


                    if ($session['amount'] < 0) {

                        $param = [
                            'code' => 5001,
                            'msg' => 'Request parameter error'
                        ];

                    } else {
                        $balance = ($oldbalance - $session['amount']);
                        if ($balance >= 0) {

                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $session['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();


//                            $member->balance_free -= $session['amount'];
//                            $member->save();

                            $param = [
                                'code' => 0,
                                'msg' => 'Success',
                                'data' => [
                                    'currency' => 'THB',
                                    'balance' => (float)$member->balance_free,
                                    'country' => 'TH'
                                ]
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'YGGDRASIL';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'bet';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['betId'];
                            $session_in['con_2'] = $session['gameId'];
                            $session_in['con_3'] = $session['roundId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'code' => 1006,
                                'msg' => 'Overdraft'
                            ];
                        }
                    }
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['gameId'];
            $session_in['con_3'] = $session['roundId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'YGGDRASIL')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'code' => 5043,
                    'msg' => 'Bet data existed'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'YGGDRASIL')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['gameId'])
//                    ->where('con_3', $session['roundId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'code' => 5043,
                        'msg' => 'Bet data existed'
                    ];

                } else {

                    $datasubs = GameLogProxy::where('company', 'YGGDRASIL')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'bet')
                        ->where('con_1', $session['betId'])
                        ->where('con_2', $session['gameId'])
//                    ->where('con_3', $session['roundId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasubs) {

                        if ($session['amount'] < 0) {

                            $param = [
                                'code' => 5001,
                                'msg' => 'Request parameter error'
                            ];

                        } else {


                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

//                            $member->balance_free += $session['amount'];
//                            $member->save();

                            $param = [
                                'code' => 0,
                                'msg' => 'Success',
                                'data' => [
                                    'currency' => 'THB',
                                    'balance' => (float)$member->balance_free,
                                    'country' => 'TH'
                                ]
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'YGGDRASIL';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'payout';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['betId'];
                            $session_in['con_2'] = $session['gameId'];
                            $session_in['con_3'] = $session['roundId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);
                        }
                    } else {
                        $param = [
                            'code' => 5042,
                            'msg' => 'Bet data is not existed'
                        ];
                    }
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['gameId'];
            $session_in['con_3'] = $session['roundId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }


        return $param;
    }

    public function transferInAppend(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'YGGDRASIL')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'append')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'code' => 5043,
                    'msg' => 'Bet data existed'
                ];

            } else {
                if ($session['amount'] < 0) {
                    $param = [
                        'code' => 5001,
                        'msg' => 'Request parameter error'
                    ];
                } else {

                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

//                    $member->balance_free += $session['amount'];
//                    $member->save();


                    $param = [
                        'code' => 0,
                        'msg' => 'Success',
                        'data' => [
                            'currency' => 'THB',
                            'balance' => (float)$member->balance_free,
                            'country' => 'TH'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'YGGDRASIL';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'append';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['gameId'];
                    $session_in['con_3'] = $session['roundId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'append';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['gameId'];
            $session_in['con_3'] = $session['roundId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }


        return $param;
    }

    public function transferInCampaign(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'YGGDRASIL')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'campaign')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['gameId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'code' => 5043,
                    'msg' => 'Bet data existed'
                ];

            } else {
                if ($session['amount'] < 0) {
                    $param = [
                        'code' => 5001,
                        'msg' => 'Request parameter error'
                    ];
                } else {
                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

//                    $member->balance_free += $session['amount'];
//                    $member->save();

                    $param = [
                        'code' => 0,
                        'msg' => 'Success',
                        'data' => [
                            'currency' => 'THB',
                            'balance' => (float)$member->balance_free,
                            'country' => 'TH'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'YGGDRASIL';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'campaign';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['gameId'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'campaign';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['gameId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'YGGDRASIL')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'code' => 5043,
                    'msg' => 'Bet data existed'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'YGGDRASIL')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['betId'])
                    ->where('con_2', $session['gameId'])
                    ->where('con_3', $session['roundId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $datasubs = GameLogProxy::where('company', 'YGGDRASIL')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
                        ->where('con_1', $session['betId'])
                        ->where('con_2', $session['gameId'])
//                        ->where('con_3', $session['roundId'])
                        ->whereNull('con_4')
                        ->first();

                    if (!$datasubs) {

                        if ($session['amount'] < 0) {
                            $param = [
                                'code' => 5001,
                                'msg' => 'Request parameter error'
                            ];
                        } else {

                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasub['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

//                            $member->balance_free += $datasub['amount'];
//                            $member->save();

                            $param = [
                                'code' => 0,
                                'msg' => 'Success',
                                'data' => [
                                    'currency' => 'THB',
                                    'balance' => (float)$member->balance_free,
                                    'country' => 'TH'
                                ]
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'YGGDRASIL';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'cancel';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['betId'];
                            $session_in['con_2'] = $session['gameId'];
                            $session_in['con_3'] = $session['roundId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);
                        }
                    } else {

                        $param = [
                            'code' => 5043,
                            'msg' => 'Bet data existed'
                        ];

                    }

                } else {

                    $param = [
                        'code' => 0,
                        'msg' => 'Success',
                        'data' => [
                            'currency' => 'THB',
                            'balance' => (float)$member->balance_free,
                            'country' => 'TH'
                        ]
                    ];
                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'YGGDRASIL';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['gameId'];
                    $session_in['con_3'] = $session['roundId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);
                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'YGGDRASIL';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['gameId'];
            $session_in['con_3'] = $session['roundId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'code' => 1,
                'msg' => 'Any other error'
            ];
        }

        return $param;
    }


}
