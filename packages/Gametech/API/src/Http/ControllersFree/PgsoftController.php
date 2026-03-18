<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use MongoDB\BSON\UTCDateTime;

class PgsoftController extends AppBaseController
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

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['operator_player_session'], 'enable' => 'Y']);

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


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['operator_player_session'], 'user_name' => $session['player_name'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'data' => [
                    'currency_code' => 'THB',
                    'balance_amount' => (float)$member->balance_free,
                    'updated_time' => now()->timestamp
                ],
                'error' => null
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PGSOFT';
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
            GameLogProxy::insert($session_in);

        } else {
            $param = [
                'data' => null,
                'error' => [
                    'code' => 3005,
                    'message' => "Player wallet doesn't exist"
                ]
            ];
        }


        return Response::json($param);
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        if (isset($session['is_validate_bet']) == 'True' || isset($session['is_adjustment']) == 'True') {
            $member = $this->memberRepository->findOneWhere(['user_name' => $session['player_name'], 'enable' => 'Y']);
        } else {

            if ($session['operator_player_session']) {

                $member = $this->memberRepository->findOneWhere(['session_id' => $session['operator_player_session'], 'user_name' => $session['player_name'], 'enable' => 'Y']);

            } else {

                return [
                    'data' => null,
                    'error' => [
                        'code' => 3001,
                        'message' => "Value cannot be null"
                    ]
                ];

            }
        }

        if ($member) {

            $data = GameLogProxy::where('company', 'PGSOFT')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['bet_id'])
                ->where('con_2', $session['transaction_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {
                $param = [
                    'data' => [
                        'currency_code' => 'THB',
                        'balance_amount' => (float)$member->balance_free,
                        'updated_time' => now()->timestamp
                    ],
                    'error' => null
                ];


            } else {

                $balance = ($member->balance_free - $session['transfer_amount']);
                if ($balance >= 0) {


                    $member->balance_free -= $session['transfer_amount'];
                    $member->save();

                    $param = [
                        'data' => [
                            'currency_code' => 'THB',
                            'balance_amount' => (float)$member->balance_free,
                            'updated_time' => now()->timestamp
                        ],
                        'error' => null
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'PGSOFT';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['transfer_amount'];
                    $session_in['con_1'] = $session['bet_id'];
                    $session_in['con_2'] = $session['transaction_id'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::insert($session_in);


                } else {

                    $param = [
                        'data' => null,
                        'error' => [
                            'code' => 3202,
                            'message' => "Not enough cash balance to bet"
                        ]
                    ];
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PGSOFT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['transfer_amount'];
            $session_in['con_1'] = $session['bet_id'];
            $session_in['con_2'] = $session['transaction_id'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session_in);

        } else {
            $param = [
                'data' => null,
                'error' => [
                    'code' => 1034,
                    'message' => "Invalid request"
                ]
            ];
        }


        return Response::json($param);
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        if (isset($session['is_validate_bet']) == 'True' || isset($session['is_adjustment']) == 'True') {
            $member = $this->memberRepository->findOneWhere(['user_name' => $session['player_name'], 'enable' => 'Y']);
        } else {

            if ($session['operator_player_session']) {

                $member = $this->memberRepository->findOneWhere(['session_id' => $session['operator_player_session'], 'user_name' => $session['player_name'], 'enable' => 'Y']);

            } else {

                return [
                    'data' => null,
                    'error' => [
                        'code' => 3001,
                        'message' => "Value cannot be null"
                    ]
                ];
            }

        }

        if ($member) {

            $data = GameLogProxy::where('company', 'PGSOFT')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['bet_id'])
                ->where('con_2', $session['transaction_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'data' => [
                        'currency_code' => 'THB',
                        'balance_amount' => (float)$member->balance_free,
                        'updated_time' => now()->timestamp
                    ],
                    'error' => null
                ];

            } else {

                $member->balance_free += $session['transfer_amount'];
                $member->save();

                $param = [
                    'data' => [
                        'currency_code' => 'THB',
                        'balance_amount' => (float)$member->balance_free,
                        'updated_time' => now()->timestamp
                    ],
                    'error' => null
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PGSOFT';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['transfer_amount'];
                $session_in['con_1'] = $session['bet_id'];
                $session_in['con_2'] = $session['transaction_id'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::insert($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PGSOFT';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['transfer_amount'];
            $session_in['con_1'] = $session['bet_id'];
            $session_in['con_2'] = $session['transaction_id'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session_in);


        } else {
            $param = [
                'data' => null,
                'error' => [
                    'code' => 1034,
                    'message' => "Invalid request"
                ]
            ];
        }


        return Response::json($param);
    }

}
