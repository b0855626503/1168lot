<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class SexyGamingController extends AppBaseController
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
        $message = json_decode($session['message']);

        $session['subdata'] = $message;
        $goto = $message->action;

        $path = storage_path('logs/seamless/sexy' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- index --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);


        switch ($goto) {
            case 'authorize':
                $param = $this->verify($session);
                break;
            case 'getBalance':
                $param = $this->getBalance($session);
                break;
            case 'bet':
                $param = $this->transferOut($session);
                break;
            case 'settle':
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

        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function verify($session)
    {

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['acctId'], 'enable' => 'Y']);

            $param = [
                'code' => 0,
                'msg' => 'success',
                'serialNo' => $session['serialNo'],
                'acctInfo' => [
                    'acctId' => $user->user_name,
                    'userName' => $user->user_name,
                    'balance' => (float)$user->balance,
                    'currency' => 'THB'
                ]
            ];


        } else {
            $param = [
                'code' => '50100',
                'msg' => 'Acct Not Found'
            ];
        }


        return $param;

    }


    public function getBalance($session)
    {
        $session['userId'] = $session['subdata']->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'status' => '0000',
                'userId' => $member->user_name,
                'balance' => (string)$member->balance_free,
                'balanceTs' => now()->toIso8601String()
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SEXY';
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
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];
        }

//        $path = storage_path('logs/seamless/kingmaker_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferOut($session)
    {
        $txnss = $session['subdata']->txns;
        $session['txns'] = $txnss;

        foreach ($txnss as $txns) {

            $session['userId'] = $txns->userId;

            $session['betAmount'] = $txns->betAmount;

            $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

            if ($member) {

                $oldbalance = $member->balance_free;

                $data = GameLogProxy::where('company', 'SEXY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $txns->platformTxId)
                    ->where('con_2', $txns->roundId)
                    ->where('con_3', $txns->gameCode)
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];
                    break;

                } else {

                    $datasub = GameLogProxy::where('company', 'SEXY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'cancel')
                        ->where('con_1', $txns->platformTxId)
                        ->where('con_2', $txns->roundId)
                        ->where('con_3', $txns->gameCode)
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'status' => '0000',
                            'balance' => (string)$member->balance_free,
                            'balanceTs' => now()->toIso8601String()
                        ];

                    } else {


                        $balance = ($oldbalance - $session['betAmount']);
                        if ($balance >= 0) {

                            MemberProxy::where('user_name', $session['userId'])->decrement('balance_free', $session['betAmount']);
//
                            $member = MemberProxy::where('user_name', $session['userId'])->first();

//                            $member->balance_free -= $session['betAmount'];
//                            $member->save();

                            $param = [
                                'status' => '0000',
                                'balance' => (string)$member->balance_free,
                                'balanceTs' => now()->toIso8601String()
                            ];


                        } else {

                            $param = [
                                'status' => '1018',
                                'desc' => 'Not Enough Balance'
                            ];
                            break;
                        }

//                        $session_in['input'] = $session;
//                        $session_in['output'] = $param;
//                        $session_in['company'] = 'SEXY';
//                        $session_in['game_user'] = $member->user_name;
//                        $session_in['method'] = 'bet';
//                        $session_in['response'] = 'in';
//                        $session_in['amount'] = $session['betAmount'];
//                        $session_in['con_1'] = $txns->platformTxId;
//                        $session_in['con_2'] = $txns->roundId;
//                        $session_in['con_3'] = $txns->gameCode;
//                        $session_in['con_4'] = null;
//                        $session_in['before_balance'] = $oldbalance;
//                        $session_in['after_balance'] = $member->balance_free;
//                        $session_in['date_create'] = now()->toDateTimeString();
//                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                        GameLogProxy::create($session_in);

                    }

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['betAmount'];
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'out';
                    $session_in['amount'] = $session['betAmount'];
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);
                }

            } else {
                $param = [
                    'status' => '1000',
                    'desc' => 'Invalid user Id'
                ];
            }
        }

        return $param;
    }

    public function transferIn($session)
    {
        $txnss = $session['subdata']->txns;
        $session['txns'] = $txnss;

        foreach ($txnss as $txns) {


            $session['userId'] = $txns->userId;
            $session['winAmount'] = $txns->winAmount;
            $session['winLoss'] = $txns->gameInfo->winLoss;


            $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

            if ($member) {

                $oldbalance = $member->balance_free;

                $data = GameLogProxy::where('company', 'SEXY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'payout')
                    ->where('con_1', $txns->platformTxId)
                    ->where('con_2', $txns->roundId)
                    ->where('con_3', $txns->betAmount)
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];
                    break;

                } else {

                    MemberProxy::where('user_name', $session['userId'])->increment('balance_free', $session['winAmount']);
//
                    $member = MemberProxy::where('user_name', $session['userId'])->first();

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['winAmount'];
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->betAmount;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SEXY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'out';
                $session_in['amount'] = $session['winAmount'];
                $session_in['con_1'] = $txns->platformTxId;
                $session_in['con_2'] = $txns->roundId;
                $session_in['con_3'] = $txns->betAmount;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            } else {
                $param = [
                    'status' => '1000',
                    'desc' => 'Invalid user Id'
                ];
            }

        }

        return $param;
    }

    public function cancelBet($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SEXY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $txns->platformTxId)
                ->where('con_2', $txns->roundId)
                ->where('con_3', $txns->gameCode)
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

            } else {

                $amountsub = 0;

                $datasub = GameLogProxy::where('company', 'SEXY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $txns->platformTxId)
                    ->where('con_2', $txns->roundId)
                    ->where('con_3', $txns->gameCode)
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $amountsub = $datasub['amount'];

                    MemberProxy::where('user_name', $session['userId'])->increment('balance_free', $amountsub);
//
                    $member = MemberProxy::where('user_name', $session['userId'])->first();

//                    $member->balance_free += $amountsub;
//                    $member->save();

                }

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SEXY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amountsub;
                $session_in['con_1'] = $txns->platformTxId;
                $session_in['con_2'] = $txns->roundId;
                $session_in['con_3'] = $txns->gameCode;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SEXY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'out';
                $session_in['amount'] = 0;
                $session_in['con_1'] = $txns->platformTxId;
                $session_in['con_2'] = $txns->roundId;
                $session_in['con_3'] = $txns->gameCode;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];

        }


        return $param;
    }

    public function unsettleBet($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SEXY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'unsettle')
                ->where('con_1', $txns->platformTxId)
                ->where('con_2', $txns->roundId)
                ->where('con_3', $txns->gameCode)
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

            } else {

                $amountsub = 0;

                $datasub = GameLogProxy::where('company', 'SEXY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'payout')
                    ->where('con_1', $txns->platformTxId)
                    ->where('con_2', $txns->roundId)
                    ->where('con_3', $txns->betAmount)
                    ->whereNull('con_4')
                    ->first();


                if ($datasub) {

                    GameLogProxy::where('company', 'SEXY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
                        ->where('con_1', $txns->platformTxId)
                        ->where('con_2', $txns->roundId)
                        ->where('con_3', $txns->betAmount)
                        ->whereNull('con_4')
                        ->update(['con_4' => 'complete']);

                    $amountsub = $datasub->amount;

//                    $balance = ($oldbalance - $amountsub);
//                    if ($balance >= 0) {
//
//                        $member->balance_free -= $amountsub;
//                        $member->save();
//
//                        $param = [
//                            'status' => '0000',
//                            'balance' => (string)$member->balance_free,
//                            'balanceTs' => now()->toIso8601String()
//                        ];
//
//
//
//                    } else {
//
//                        $param = [
//                            'status' => '0000',
//                            'balance' => '0',
//                            'balanceTs' => now()->toIso8601String()
//                        ];
//
//                    }

                    MemberProxy::where('user_name', $session['userId'])->decrement('balance_free', $amountsub);
//
                    $member = MemberProxy::where('user_name', $session['userId'])->first();
//                    $member->balance_free -= $amountsub;
//                    $member->save();

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'unsettle';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amountsub;
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
                    $session_in['con_4'] = 'complete';
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'unsettle';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amountsub;
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
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
            $session_in['company'] = 'SEXY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'unsettle';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amountsub;
            $session_in['con_1'] = $txns->platformTxId;
            $session_in['con_2'] = $txns->roundId;
            $session_in['con_3'] = $txns->gameCode;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];

        }


        return $param;
    }

    public function voidBet($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SEXY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'voidbet')
                ->where('con_1', $txns->platformTxId)
                ->where('con_2', $txns->roundId)
                ->where('con_3', $txns->gameCode)
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

            } else {

                MemberProxy::where('user_name', $session['userId'])->increment('balance_free', $txns->betAmount);
//
                $member = MemberProxy::where('user_name', $session['userId'])->first();

//                $member->balance_free += $txns->betAmount;
//                $member->save();

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SEXY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'voidbet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $txns->betAmount;
                $session_in['con_1'] = $txns->platformTxId;
                $session_in['con_2'] = $txns->roundId;
                $session_in['con_3'] = $txns->gameCode;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SEXY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'voidbet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $txns->betAmount;
            $session_in['con_1'] = $txns->platformTxId;
            $session_in['con_2'] = $txns->roundId;
            $session_in['con_3'] = $txns->gameCode;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];

        }


        return $param;
    }

    public function voidSettle($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SEXY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'voidsettle')
                ->where('con_1', $txns->platformTxId)
                ->where('con_2', $txns->roundId)
                ->where('con_3', $txns->gameCode)
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => '0000',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'SEXY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'payout')
                    ->where('con_1', $txns->platformTxId)
                    ->where('con_2', $txns->roundId)
                    ->where('con_3', $txns->betAmount)
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    GameLogProxy::where('company', 'SEXY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'payout')
                        ->where('con_1', $txns->platformTxId)
                        ->where('con_2', $txns->roundId)
                        ->where('con_3', $txns->betAmount)
                        ->whereNull('con_4')
                        ->update(['con_4' => 'complete']);

                    MemberProxy::where('user_name', $session['userId'])->decrement('balance_free', $datasub['input']['winLoss']);
//
                    $member = MemberProxy::where('user_name', $session['userId'])->first();

//                    $member->balance_free -= $datasub['input']['winLoss'];
//                    $member->save();

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'voidsettle';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $txns->betAmount;
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
                    $session_in['con_4'] = 'complete';
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $param = [
                        'status' => '0000',
                        'balance' => (string)$member->balance_free,
                        'balanceTs' => now()->toIso8601String()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SEXY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'voidsettle';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $txns->betAmount;
                    $session_in['con_1'] = $txns->platformTxId;
                    $session_in['con_2'] = $txns->roundId;
                    $session_in['con_3'] = $txns->gameCode;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);
                }

//                $balance = ($oldbalance - $txns->betAmount);
//                if ($balance >= 0) {
//                    $member->balance_free -= $txns->betAmount;
//                    $member->save();
//
//                    $param = [
//                        'status' => '0000',
//                        'balance' => (string)$member->balance_free,
//                        'balanceTs' => now()->toIso8601String()
//                    ];
//
//                    $session_in['input'] = $session;
//                    $session_in['output'] = $param;
//                    $session_in['company'] = 'SEXY';
//                    $session_in['game_user'] = $member->user_name;
//                    $session_in['method'] = 'voidsettle';
//                    $session_in['response'] = 'in';
//                    $session_in['amount'] = $txns->betAmount;
//                    $session_in['con_1'] = $txns->platformTxId;
//                    $session_in['con_2'] = $txns->roundId;
//                    $session_in['con_3'] = $txns->gameCode;
//                    $session_in['con_4'] = null;
//                    $session_in['before_balance'] = $oldbalance;
//                    $session_in['after_balance'] = $member->balance_free;
//                    $session_in['date_create'] = now()->toDateTimeString();
//                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                    GameLogProxy::create($session_in);
//
//                } else {
//
//                    $param = [
//                        'status' => '1018',
//                        'desc' => 'Not Enough Balance'
//                    ];
//                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SEXY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'voidsettle';
            $session_in['response'] = 'out';
            $session_in['amount'] = $txns->betAmount;
            $session_in['con_1'] = $txns->platformTxId;
            $session_in['con_2'] = $txns->roundId;
            $session_in['con_3'] = $txns->gameCode;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];

        }


        return $param;
    }

    public function Give($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SEXY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'give')
                ->where('con_1', $txns->promotionTxId)
                ->where('con_2', $txns->promotionId)
                ->where('con_3', $txns->promotionTypeId)
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => '0000',
                    'desc' => 'success',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

            } else {


                $member->balance_free += $txns->amount;
                $member->save();

                $param = [
                    'status' => '0000',
                    'desc' => 'success',
                    'balance' => (string)$member->balance_free,
                    'balanceTs' => now()->toIso8601String()
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SEXY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'give';
                $session_in['response'] = 'in';
                $session_in['amount'] = $txns->amount;
                $session_in['con_1'] = $txns->promotionTxId;
                $session_in['con_2'] = $txns->promotionId;
                $session_in['con_3'] = $txns->promotionTypeId;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SEXY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'give';
            $session_in['response'] = 'out';
            $session_in['amount'] = $txns->amount;
            $session_in['con_1'] = $txns->promotionTxId;
            $session_in['con_2'] = $txns->promotionId;
            $session_in['con_3'] = $txns->promotionTypeId;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'status' => '1000',
                'desc' => 'Invalid user Id'
            ];

        }


        return $param;
    }

}
