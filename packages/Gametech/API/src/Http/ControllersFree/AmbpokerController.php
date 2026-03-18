<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class AmbpokerController extends AppBaseController
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
                    'message' => "Success"
                ],
                'data' => [
                    'balance' => (float)$member->balance_free
                ]
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'AMBPOKER';
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
                'status' => [
                    'code' => 999,
                    'message' => "Service not available"
                ]
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBPOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['refId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => "Duplicate Round Id"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

            } else {

                $balance = ($oldbalance - abs($session['amount']));
                if ($balance >= 0) {

                    MemberProxy::where('user_name', $session['username'])->decrement('balance_free', abs($session['amount']));
                    $member = MemberProxy::where('user_name', $session['username'])->first();

//                    $member->balance_free -= abs($session['amount']);
//                    $member->save();

                    $param = [
                        'status' => [
                            'code' => 0,
                            'message' => "Success"
                        ],
                        'data' => [
                            'username' => $member->user_name,
                            'wallet' => [
                                'balance' => (float)$member->balance_free,
                                'lastUpdate' => now()->toISOString()
                            ],
                            'balance' => [
                                'before' => (float)$oldbalance,
                                'after' => (float)$member->balance_free
                            ],
                            'refId' => $session['refId']
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'AMBPOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = abs($session['amount']);
                    $session_in['con_1'] = $session['refId'];
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
                        'status' => [
                            'code' => 800,
                            'message' => "Balance insufficient"
                        ]
                    ];
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'AMBPOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = abs($session['amount']);
            $session_in['con_1'] = $session['refId'];
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
                'status' => [
                    'code' => 999,
                    'message' => "Service not available"
                ]
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBPOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['refId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => "Duplicate Round Id"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'AMBPOKER')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['refId'])
                    ->where('con_2', $session['gameId'])
                    ->where('con_3', $session['roundId'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {
                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', abs($session['amount']));
                    $member = MemberProxy::where('user_name', $session['username'])->first();

                }

                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => "Success"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'AMBPOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = abs($session['amount']);
                $session_in['con_1'] = $session['refId'];
                $session_in['con_2'] = $session['gameId'];
                $session_in['con_3'] = $session['roundId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'AMBPOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = abs($session['amount']);
            $session_in['con_1'] = $session['refId'];
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
                'status' => [
                    'code' => 999,
                    'message' => "Service not available"
                ]
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBPOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['refId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => "Duplicate Round Id"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

            } else {
                $sumamount = 0;
                $datasub = GameLogProxy::where('company', 'AMBPOKER')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['refId'])
                    ->where('con_2', $session['gameId'])
                    ->where('con_3', $session['roundId'])
                    ->whereNull('con_4')
                    ->get();


                if (count($datasub) > 0) {

                    foreach ($datasub as $item) {
                        $sumamount += $item['amount'];
                    }

                    $datasubs = GameLogProxy::where('company', 'AMBPOKER')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
//                    ->where('con_1', $session['refId'])
                        ->where('con_2', $session['gameId'])
                        ->where('con_3', $session['roundId'])
                        ->whereNull('con_4')
                        ->first();

                    if (!$datasubs) {
                        MemberProxy::where('user_name', $session['username'])->increment('balance_free', $sumamount);
                        $member = MemberProxy::where('user_name', $session['username'])->first();

                    }

                    $param = [
                        'status' => [
                            'code' => 0,
                            'message' => "Success"
                        ],
                        'data' => [
                            'username' => $member->user_name,
                            'wallet' => [
                                'balance' => (float)$member->balance_free,
                                'lastUpdate' => now()->toISOString()
                            ],
                            'balance' => [
                                'before' => (float)$oldbalance,
                                'after' => (float)$member->balance_free
                            ],
                            'refId' => $session['refId']
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'AMBPOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $sumamount;
                    $session_in['con_1'] = $session['refId'];
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
                        'status' => [
                            'code' => 807,
                            'message' => "Bet not found"
                        ],
                        'data' => [
                            'username' => $member->user_name,
                            'wallet' => [
                                'balance' => (float)$member->balance_free,
                                'lastUpdate' => now()->toISOString()
                            ],
                            'balance' => [
                                'before' => (float)$oldbalance,
                                'after' => (float)$member->balance_free
                            ],
                            'refId' => $session['refId']
                        ]
                    ];
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'AMBPOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = abs($session['amount']);
            $session_in['con_1'] = $session['refId'];
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
                'status' => [
                    'code' => 999,
                    'message' => "Service not available"
                ]
            ];
        }


        return $param;
    }

    public function voidBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBPOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'void')
                ->where('con_1', $session['refId'])
                ->where('con_2', $session['gameId'])
                ->where('con_3', $session['roundId'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => "Success"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

            } else {
                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();


                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => "Success"
                    ],
                    'data' => [
                        'username' => $member->user_name,
                        'wallet' => [
                            'balance' => (float)$member->balance_free,
                            'lastUpdate' => now()->toISOString()
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance_free
                        ],
                        'refId' => $session['refId']
                    ]
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'AMBPOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'void';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['refId'];
                $session_in['con_2'] = $session['gameId'];
                $session_in['con_3'] = $session['roundId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'AMBPOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'void';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['refId'];
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
                'status' => [
                    'code' => 999,
                    'message' => "Service not available"
                ]
            ];
        }


        return $param;
    }


}
