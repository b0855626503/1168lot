<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class SaGamingController extends AppBaseController
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
                'username' => $member->user_name,
                'currency' => 'THB',
                'amount' => (float)$member->balance_free,
                'error' => 0
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SAGAME';
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
                'username' => $session['username'],
                'currency' => 'THB',
                'amount' => 0,
                'error' => 0
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

            $data = GameLogProxy::where('company', 'SAGAME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['txnid'])
//                ->where('con_2', $session['hostid'])
                ->where('con_3', $session['gameid'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'username' => $member->user_name,
                    'currency' => 'THB',
                    'amount' => (float)$member->balance_free,
                    'error' => 0
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'SAGAME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
//                    ->where('con_2', $session['hostid'])
                    ->where('con_3', $session['gameid'])
                    ->where('con_4', $session['txnid'])
                    ->first();

                if ($datasub) {

                    $param = [
                        'username' => $member->user_name,
                        'currency' => 'THB',
                        'amount' => (float)$member->balance_free,
                        'error' => 0
                    ];

                } else {

                    if ($session['amount'] < 0) {

                        $param = [
                            'username' => $member->user_name,
                            'currency' => 'THB',
                            'amount' => (float)$member->balance_free,
                            'error' => 1002
                        ];

                    } else {
                        $balance = ($member->balance_free - $session['amount']);
                        if ($balance >= 0) {

                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $session['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

//                            $member->balance_free -= $session['amount'];
//                            $member->save();

                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 0
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'SAGAME';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'bet';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['txnid'];
                            $session_in['con_2'] = $session['hostid'];
                            $session_in['con_3'] = $session['gameid'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 1004
                            ];

                        }
                    }

                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SAGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['txnid'];
            $session_in['con_2'] = $session['hostid'];
            $session_in['con_3'] = $session['gameid'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'username' => $session['username'],
                'currency' => 'THB',
                'amount' => 0,
                'error' => 1000
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

            $data = GameLogProxy::where('company', 'SAGAME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['txnid'])
//                ->where('con_2', $session['hostid'])
                ->where('con_3', $session['gameid'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'username' => $member->user_name,
                    'currency' => 'THB',
                    'amount' => (float)$member->balance_free,
                    'error' => 0
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'SAGAME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['txnid'])
                    ->where('con_2', $session['hostid'])
                    ->where('con_3', $session['gameid'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $datasubs = GameLogProxy::where('company', 'SAGAME')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'cancel')
//                    ->where('con_1', $session['txnid'])
                        ->where('con_2', $session['hostid'])
                        ->where('con_3', $session['gameid'])
                        ->where('con_4', $datasub['con_1'])
                        ->first();

                    if ($datasubs) {

                        $param = [
                            'username' => $member->user_name,
                            'currency' => 'THB',
                            'amount' => (float)$member->balance_free,
                            'error' => 0
                        ];

                    } else {


                        if ($session['amount'] < 0) {
                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 1002
                            ];
                        } else {

                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

//                            $member->balance_free += $session['amount'];
//                            $member->save();

                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 0
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'SAGAME';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'payout';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['txnid'];
                            $session_in['con_2'] = $session['hostid'];
                            $session_in['con_3'] = $session['gameid'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);
                        }
                    }

                } else {

                    $param = [
                        'username' => $member->user_name,
                        'currency' => 'THB',
                        'amount' => (float)$member->balance_free,
                        'error' => 0
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SAGAME';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['txnid'];
                    $session_in['con_2'] = $session['hostid'];
                    $session_in['con_3'] = $session['gameid'];
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
            $session_in['company'] = 'SAGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['txnid'];
            $session_in['con_2'] = $session['hostid'];
            $session_in['con_3'] = $session['gameid'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'username' => $session['username'],
                'currency' => 'THB',
                'amount' => 0,
                'error' => 1000
            ];

        }


        return $param;
    }

    public function playerLost(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $param = [
                'username' => $member->user_name,
                'currency' => 'THB',
                'amount' => (float)$member->balance_free,
                'error' => 0
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SAGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'playerlost';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = $session['txnid'];
            $session_in['con_2'] = $session['hostid'];
            $session_in['con_3'] = $session['gameid'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SAGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'playerlost';
            $session_in['response'] = 'out';
            $session_in['amount'] = 0;
            $session_in['con_1'] = $session['txnid'];
            $session_in['con_2'] = $session['hostid'];
            $session_in['con_3'] = $session['gameid'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'username' => $session['username'],
                'currency' => 'THB',
                'amount' => 0,
                'error' => 1000
            ];

        }


        return $param;
    }


    public function cancelBet(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'SAGAME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['txnid'])
//                ->where('con_2', $session['hostid'])
                ->where('con_3', $session['gameid'])
                ->where('con_4', $session['txn_reverse_id'])
                ->first();

            if ($data) {

                $param = [
                    'username' => $member->user_name,
                    'currency' => 'THB',
                    'amount' => (float)$member->balance_free,
                    'error' => 0
                ];

            } else {


                $datasub = GameLogProxy::where('company', 'SAGAME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['txn_reverse_id'])
//                    ->where('con_2', $session['hostid'])
                    ->where('con_3', $session['gameid'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $datasubs = GameLogProxy::where('company', 'SAGAME')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
//                        ->where('con_1', $session['txn_reverse_id'])
                        ->where('con_2', $session['hostid'])
                        ->where('con_3', $session['gameid'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasubs) {
                        $param = [
                            'username' => $member->user_name,
                            'currency' => 'THB',
                            'amount' => (float)$member->balance_free,
                            'error' => 0
                        ];
                    } else {


                        if ($session['amount'] < 0) {

                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 1002
                            ];

                        } else {
                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasub['amount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();
//
//                            $member->balance_free += $datasub['amount'];
//                            $member->save();

                            $param = [
                                'username' => $member->user_name,
                                'currency' => 'THB',
                                'amount' => (float)$member->balance_free,
                                'error' => 0
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'SAGAME';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'cancel';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $datasub['amount'];
                            $session_in['con_1'] = $session['txnid'];
                            $session_in['con_2'] = $session['hostid'];
                            $session_in['con_3'] = $session['gameid'];
                            $session_in['con_4'] = $session['txn_reverse_id'];
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                            GameLogProxy::where('company', 'SAGAME')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'bet')
//                                ->where('amount', $item['betAmount'])
                                ->where('con_1', $session['txn_reverse_id'])
//                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $session['gameid'])
                                ->whereNull('con_4')
                                ->update(['con_4' => 'complete']);
                        }
                    }

                } else {

                    $param = [
                        'username' => $member->user_name,
                        'currency' => 'THB',
                        'amount' => (float)$member->balance_free,
                        'error' => 0
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SAGAME';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['txnid'];
                    $session_in['con_2'] = $session['hostid'];
                    $session_in['con_3'] = $session['gameid'];
                    $session_in['con_4'] = $session['txn_reverse_id'];
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                }


            }


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SAGAME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['txnid'];
            $session_in['con_2'] = $session['hostid'];
            $session_in['con_3'] = $session['gameid'];
            $session_in['con_4'] = $session['txn_reverse_id'];
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'username' => $session['username'],
                'currency' => 'THB',
                'amount' => 0,
                'error' => 1000
            ];

        }


        return $param;
    }

}
