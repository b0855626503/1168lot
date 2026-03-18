<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class XtremeGamingController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['user'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'requestId' => $session['requestId'],
                'status' => 'ok',
                'user' => $member->user_name,
                'currency' => 'THB',
                'balance' => number_format($member->balance, 4, '.', '')

            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'XTREME';
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
                'status' => 'FAILED',
                'message' => "User doesn't exist"
            ];
        }

//        $path = storage_path('logs/seamless/xtreme' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['user'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'XTREME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['transactionId'])
                ->where('con_2', $session['requestId'])
                ->where('con_3', $session['round'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => 'FAILED',
                    'message' => "DUP"

                ];

            } else {

                $data = GameLogProxy::where('company', 'XTREME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['transactionId'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    $param = [
                        'transactionId' => $session['transactionId'],
                        'requestId' => $session['requestId'],
                        'status' => 'ok',
                        'user' => $member->user_name,
                        'currency' => 'THB',
                        'balance' => number_format($member->balance, 4, '.', '')

                    ];

                } else {

                    $balance = ($member->balance - $session['amount']);
                    if ($balance >= 0) {
                        MemberProxy::where('user_name', $session['user'])->decrement('balance', $session['amount']);
                        $member = MemberProxy::where('user_name', $session['user'])->first();


//                        $member->balance -= $session['amount'];
//                        $member->save();

                        $param = [
                            'transactionId' => $session['transactionId'],
                            'requestId' => $session['requestId'],
                            'status' => 'ok',
                            'user' => $member->user_name,
                            'currency' => 'THB',
                            'balance' => number_format($member->balance, 4, '.', '')

                        ];


                    } else {

                        $param = [
                            'status' => 'FAILED',
                            'message' => "NO CREDIT",

                        ];

                        return $param;

                    }

                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'XTREME';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['transactionId'];
                $session_in['con_2'] = $session['requestId'];
                $session_in['con_3'] = $session['round'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);
            }


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'XTREME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transactionId'];
            $session_in['con_2'] = $session['requestId'];
            $session_in['con_3'] = $session['round'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'status' => 'FAILED',
                'message' => "User doesn't exist"
            ];

        }


//        $path = storage_path('logs/seamless/xtreme' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $session = $request->all();

        if (!isset($session['transactionId'])) {
            $param = [
                'status' => 'FAILED',
                'message' => "Transaction not found",
            ];
            return $param;
        }

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['user'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'XTREME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['transactionId'])
                ->where('con_2', $session['wagerId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => 'FAILED',
                    'message' => "DUP",
                ];

            } else {

                $data = GameLogProxy::where('company', 'XTREME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['transactionId'])
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    $datasub = GameLogProxy::where('company', 'XTREME')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'cancel')
                        ->where('con_1', $session['transactionId'])
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'status' => 'FAILED',
                            'message' => "Bet already cancel",
                        ];

                    } else {

                        MemberProxy::where('user_name', $session['user'])->increment('balance', $session['amount']);
                        $member = MemberProxy::where('user_name', $session['user'])->first();

//                    $member->balance += $session['amount'];
//                    $member->save();

                        $param = [
                            'status' => 'ok',
                            'user' => $member->user_name,
                            'currency' => 'THB',
                            'balance' => number_format($member->balance, 4, '.', '')

                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'XTREME';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'payout';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['transactionId'];
                        $session_in['con_2'] = $session['wagerId'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    }


                } else {

                    $param = [
                        'status' => 'FAILED',
                        'message' => "Transaction not found",
                    ];

                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'XTREME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transactionId'];
            $session_in['con_2'] = $session['wagerId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'status' => 'FAILED',
                'message' => "User doesn't exist"
            ];

        }

//        $path = storage_path('logs/seamless/xtreme' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- PAYOUT --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }


    public function cancelBet(Request $request)
    {
        $param = [];
        $session = $request->all();
        $amount = 0;
        $member = $this->memberRepository->findOneWhere(['user_name' => $session['user'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'XTREME')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['transactionId'])
                ->where('con_2', $session['requestId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => 'FAILED',
                    'message' => "DUP",

                ];

            } else {

                $datasub = GameLogProxy::where('company', 'XTREME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'payout')
                    ->where('con_1', $session['transactionId'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'status' => 'FAILED',
                        'message' => "Transaction Already Settle",
                    ];
                    return $param;
                }

                $datasub = GameLogProxy::where('company', 'XTREME')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['transactionId'])
//                    ->whereNull('con_2')
//                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $amount = $datasub['amount'];

                    MemberProxy::where('user_name', $session['user'])->increment('balance', $amount);
                    $member = MemberProxy::where('user_name', $session['user'])->first();


//                    $member->balance += $amount;
//                    $member->save();

                }

                $param = [
                    'requestId' => $session['requestId'],
                    'status' => 'ok',
                    'user' => $member->user_name,
                    'currency' => 'THB',
                    'balance' => number_format($member->balance, 4, '.', '')

                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'XTREME';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['transactionId'];
                $session_in['con_2'] = $session['requestId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'XTREME';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['transactionId'];
            $session_in['con_2'] = $session['requestId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'status' => 'FAILED',
                'message' => "User doesn't exist"
            ];

        }

//        $path = storage_path('logs/seamless/xtreme' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

}
