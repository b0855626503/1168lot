<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class AmbslotController extends AppBaseController
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
            $session_in['company'] = 'AMBSLOT';
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

            $data = GameLogProxy::where('company', 'AMBSLOT')
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

                $balance = ($oldbalance - $session['amount']);
                if ($balance >= 0) {

                    $member->balance_free -= $session['amount'];
                    $member->save();

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
                    $session_in['company'] = 'AMBSLOT';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
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
            $session_in['company'] = 'AMBSLOT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
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

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBSLOT')
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

                $member->balance_free += $session['amount'];
                $member->save();

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
                $session_in['company'] = 'AMBSLOT';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
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
            $session_in['company'] = 'AMBSLOT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
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

    public function settle(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBSLOT')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'settle')
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

                $member->balance_free += $session['amount'];
                $member->save();

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
                $session_in['company'] = 'AMBSLOT';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
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
            $session_in['company'] = 'AMBSLOT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
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

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBSLOT')
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

                $member->balance_free += $session['amount'];
                $member->save();

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
                $session_in['company'] = 'AMBSLOT';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
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
            $session_in['company'] = 'AMBSLOT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
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

    public function voidBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'AMBSLOT')
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

                $member->balance_free += $session['amount'];
                $member->save();

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
                $session_in['company'] = 'AMBSLOT';
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
            $session_in['company'] = 'AMBSLOT';
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
