<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class EvoplayController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);

        if ($member) {

            switch ($session['name']) {
                case 'init':
                    $param = $this->getBalance($session);
                    break;
                case 'bet':
                    $param = $this->transferOut($session);
                    break;
                case 'win':
                    $param = $this->transferIn($session);
                    break;
                case 'refund':
                    $param = $this->cancelBet($session);
                    break;
            }


            return $param;
        }

        return [
            'status' => 'error',
            'error' => [
                'scope' => "internal",
                'no_refund' => "1",
                'message' => 'Invalid token'
            ]
        ];

    }


    public function getBalance($session)
    {


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);
        if ($member) {


            $param = [
                'status' => 'ok',
                'data' => [
                    'balance' => (string)$member->balance_free,
                    'currency' => 'THB'
                ]
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'EVOPLAY';
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
                'status' => 'error',
                'error' => [
                    'scope' => "internal",
                    'no_refund' => "1",
                    'message' => 'Invalid token'
                ]
            ];
        }


        return $param;
    }

    public function transferOut($session)
    {


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'EVOPLAY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['data']['round_id'])
                ->where('con_2', $session['data']['action_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => 'error',
                    'error' => [
                        'scope' => "user",
                        'no_refund' => "1",
                        'message' => 'Duplicate Bet'
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'EVOPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['data']['round_id'])
                    ->where('con_2', $session['data']['action_id'])
//                    ->where('con_3', $session['data']['final_action'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'status' => 'ok',
                        'data' => [
                            'balance' => (string)$member->balance_free,
                            'currency' => 'THB'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'EVOPLAY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['data']['amount'];
                    $session_in['con_1'] = $session['data']['round_id'];
                    $session_in['con_2'] = $session['data']['action_id'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {


                    $balance = ($oldbalance - $session['data']['amount']);
                    if ($balance >= 0) {


                        $member->balance_free -= $session['data']['amount'];
                        $member->save();

                        $param = [
                            'status' => 'ok',
                            'data' => [
                                'balance' => (string)$member->balance_free,
                                'currency' => 'THB'
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'EVOPLAY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['data']['amount'];
                        $session_in['con_1'] = $session['data']['round_id'];
                        $session_in['con_2'] = $session['data']['action_id'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    } else {

                        $param = [
                            'status' => 'error',
                            'error' => [
                                'scope' => "internal",
                                'no_refund' => "1",
                                'message' => 'Insufficient balance'
                            ]
                        ];
                    }
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'EVOPLAY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['data']['amount'];
            $session_in['con_1'] = $session['data']['round_id'];
            $session_in['con_2'] = $session['data']['action_id'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'status' => 'error',
                'error' => [
                    'scope' => "internal",
                    'no_refund' => "1",
                    'message' => 'Invalid token'
                ]
            ];
        }


        return $param;
    }

    public function transferIn($session)
    {

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'EVOPLAY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['data']['round_id'])
                ->where('con_2', $session['data']['action_id'])
                ->where('con_3', $session['data']['final_action'])
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => 'ok',
                    'data' => [
                        'balance' => (string)$member->balance_free,
                        'currency' => 'THB'
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'EVOPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['data']['round_id'])
                    ->where('con_2', $session['data']['action_id'])
//                    ->where('con_3', $session['data']['final_action'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'status' => 'error',
                        'error' => [
                            'scope' => "user",
                            'no_refund' => "1",
                            'message' => 'Transaction already cancel'
                        ]
                    ];

                } else {


                    $member->balance_free += $session['data']['amount'];
                    $member->save();

                    $param = [
                        'status' => 'ok',
                        'data' => [
                            'balance' => (string)$member->balance_free,
                            'currency' => 'THB'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'EVOPLAY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['data']['amount'];
                    $session_in['con_1'] = $session['data']['round_id'];
                    $session_in['con_2'] = $session['data']['action_id'];
                    $session_in['con_3'] = $session['data']['final_action'];
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
            $session_in['company'] = 'EVOPLAY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['data']['amount'];
            $session_in['con_1'] = $session['data']['round_id'];
            $session_in['con_2'] = $session['data']['action_id'];
            $session_in['con_3'] = $session['data']['final_action'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'status' => 'error',
                'error' => [
                    'scope' => "internal",
                    'no_refund' => "1",
                    'message' => 'Invalid token'
                ]
            ];
        }


        return $param;
    }

    public function cancelBet($session)
    {


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'EVOPLAY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['data']['refund_round_id'])
                ->where('con_2', $session['data']['refund_action_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => 'ok',
                    'data' => [
                        'balance' => (string)$member->balance_free,
                        'currency' => 'THB'
                    ]
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'EVOPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'payout')
                    ->where('con_1', $session['data']['refund_round_id'])
                    ->where('con_2', $session['data']['refund_action_id'])
//                    ->whereNull('con_3')
//                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'status' => 'error',
                        'error' => [
                            'scope' => 'user',
                            'no_refund' => '1',
                            'message' => 'Transaction already settle'
                        ]
                    ];

                } else {

                    $datasubs = GameLogProxy::where('company', 'EVOPLAY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'bet')
                        ->where('con_1', $session['data']['refund_round_id'])
                        ->where('con_2', $session['data']['refund_action_id'])
//                    ->whereNull('con_3')
//                    ->whereNull('con_4')
                        ->first();

                    if ($datasubs) {

                        $balance = ($oldbalance + $datasubs['amount']);

                        $member->balance_free += $datasubs['amount'];
                        $member->save();
                    }

                    $param = [
                        'status' => 'ok',
                        'data' => [
                            'balance' => (string)$member->balance_free,
                            'currency' => 'THB'
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'EVOPLAY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['data']['amount'];
                    $session_in['con_1'] = $session['data']['refund_round_id'];
                    $session_in['con_2'] = $session['data']['refund_action_id'];
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
            $session_in['company'] = 'EVOPLAY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['data']['amount'];
            $session_in['con_1'] = $session['data']['refund_round_id'];
            $session_in['con_2'] = $session['data']['refund_action_id'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'status' => 'error',
                'error' => [
                    'scope' => "internal",
                    'no_refund' => "1",
                    'message' => 'Invalid token'
                ]
            ];
        }


        return $param;
    }


}
