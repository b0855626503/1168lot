<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class BigGamingController extends AppBaseController
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

    public function index(Request $request)
    {
        $param = [
            'code' => 50100,
            'msg' => 'Acct Not Found'
        ];

        $session = $request->all();

        $goto = $session->method;

        switch ($goto) {
            case 'authorize':
                $param = $this->verify($session);
                break;
            case 'open.operator.user.balance':
                $param = $this->getBalance($session);
                break;
            case 'open.operator.order.transfer':
                $param = $this->transferOut($session);
                break;
            case 'open.operator.calc.transfer':
                $param = $this->transferIn($session);
                break;
            case 'cancelBet':
                $param = $this->cancelBet($session);
                break;
            case 'unsettle':
                $param = $this->unsettleBet($session);
                break;
            case 'voidBet':
                $param = $this->voidBet($session);
                break;
            case 'voidSettle':
                $param = $this->voidSettle($session);
                break;
            case 'give':
                $param = $this->Give($session);
                break;
        }

        return $param;
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

    public function getBalance($session)
    {

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['params']['loginId'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'id' => $session['id'],
                'result' => (float)$member->balance,
                'error' => null,
                'jsonrpc' => "2.0"
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'BIGGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $member->balance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'id' => $session['id'],
                'error' => [
                    'code' => 2405,
                    'message' => 'This user account does not exist',
                    'reason' => '',
                    'action' => null
                ],
                'detail' => [
                    'cxt' => 'getbalance',
                    'method' => $session['method'],
                    'elapsed' => 1,
                    'result' => null
                ],
                'jsonrpc' => "2.0"
            ];
        }


        return $param;
    }

    public function transferOut($session)
    {
        $param = [];

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['params']['loginId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;


            $balance = ($member->balance - abs($session['params']['amount']));
            if ($balance >= 0) {

                $member->balance -= abs($session['params']['amount']);
                $member->save();

                $param = [
                    'id' => $session['id'],
                    'result' => [
                        'userId' => $session['params']['userId'],
                        'availableAmount' => (float)$member->balance,
                        'orderResult' => 1,
                        'tranId' => null
                    ],
                    'error' => null,
                    'jsonrpc' => "2.0"
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'BIGGAME';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = abs($session['params']['amount']);
                $session_in['con_1'] = null;
                $session_in['con_2'] = null;
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
                    'error' => [
                        'code' => 2405,
                        'message' => 'This user account does not exist',
                        'reason' => '',
                        'action' => null
                    ],
                    'detail' => [
                        'cxt' => 'placebet',
                        'method' => $session['method'],
                        'elapsed' => 1,
                        'result' => null
                    ],
                    'jsonrpc' => "2.0"
                ];
            }


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = abs($session['params']['amount']);
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
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
                'error' => [
                    'code' => 2414,
                    'message' => 'Abnormal update record of fund withdrawal approval',
                    'reason' => '',
                    'action' => null
                ],
                'detail' => [
                    'cxt' => 'placebet',
                    'method' => $session['method'],
                    'elapsed' => 1,
                    'result' => null
                ],
                'jsonrpc' => "2.0"
            ];

        }


        return $param;
    }

    public function transferIn($session)
    {
        $param = [];

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['params']['loginId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;


            $member->balance += ($session['params']['amount']);
            $member->save();

            $param = [
                'id' => $session['id'],
                'result' => [
                    'userId' => $session['params']['userId'],
                    'availableAmount' => (float)$member->balance,
                    'orderResult' => 1,
                    'tranId' => null
                ],
                'error' => null,
                'jsonrpc' => "2.0"
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'BIGGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'in';
            $session_in['amount'] = ($session['params']['amount']);
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'BIGGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = ($session['params']['amount']);
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
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
                'error' => [
                    'code' => 2405,
                    'message' => 'This user account does not exist',
                    'reason' => '',
                    'action' => null
                ],
                'detail' => [
                    'cxt' => 'placebet',
                    'method' => $session['method'],
                    'elapsed' => 1,
                    'result' => null
                ],
                'jsonrpc' => "2.0"
            ];

        }


        return $param;
    }

    public function refund(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

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
                        'balance' => (float)$member->balance
                    ]
                ];

            } else {

                $balance = ($member->balance - $session['amount']);
                if ($balance >= 0) {

                    $member->balance -= $session['amount'];
                    $member->save();

                    $param = [
                        'errorCode' => 0,
                        'result' => [
                            'balance' => (float)$member->balance
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'WM';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'refund';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['roundId'];
                    $session_in['con_3'] = $session['gameId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance;
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
            $session_in['after_balance'] = $member->balance;
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

    public function void(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'WM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'void')
                ->where('con_1', $session['betId'])
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['gameId'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'errorCode' => 0,
                    'result' => [
                        'balance' => (float)$member->balance
                    ]
                ];

            } else {

                $balance = ($member->balance - $session['amount']);
                if ($balance >= 0) {

                    $member->balance -= $session['amount'];
                    $member->save();

                    $param = [
                        'errorCode' => 0,
                        'result' => [
                            'balance' => (float)$member->balance
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'WM';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'void';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['betId'];
                    $session_in['con_2'] = $session['roundId'];
                    $session_in['con_3'] = $session['gameId'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance;
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

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'WM';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'void';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['betId'];
            $session_in['con_2'] = $session['roundId'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
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
