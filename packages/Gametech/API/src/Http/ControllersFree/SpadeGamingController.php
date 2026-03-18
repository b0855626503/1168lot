<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class SpadeGamingController extends AppBaseController
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
        $header = $request->header('api');
        $session = $request->all();


        $session['api'] = $header;


        switch ($header) {
            case 'authorize':
                $param = $this->verify($session);
                break;
            case 'getBalance':
                $param = $this->getBalance($session);
                break;
            case 'transfer':
                if ($session['type'] == 1) {
                    $param = $this->transferOut($session);
                } elseif ($session['type'] == 2) {
                    $param = $this->cancelBet($session);
                } elseif ($session['type'] == 4) {
                    $param = $this->transferIn($session);
                } elseif ($session['type'] == 7) {
                    $param = $this->transferBonus($session);
                } else {
                    $param = [
                        'code' => 50100,
                        'msg' => 'Acct Not Found'
                    ];
                }

                break;

            case 'refund':
                $param = $this->cancelBet($session);
                break;
            default:
                $param = [
                    'code' => 50100,
                    'msg' => 'Acct Not Found'
                ];
        }


        return $param;

    }

    public function verify($session)
    {


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {


            $param = [
                'code' => 0,
                'msg' => 'success',
                'serialNo' => $session['serialNo'],
                'acctInfo' => [
                    'acctId' => $member->user_name,
                    'userName' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ]
            ];


        } else {
            $param = [
                'code' => 50104,
                'msg' => 'Token Validation Failed',
                'serialNo' => $session['serialNo'],
                'acctInfo' => [
                    'acctId' => $session['acctId'],
                    'userName' => $session['acctId'],
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];
        }


        return $param;

    }


    public function getBalance($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'code' => 0,
                'msg' => 'success',
                'serialNo' => $session['serialNo'],
                'acctInfo' => [
                    'acctId' => $member->user_name,
                    'userName' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ]
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SPADEGAMING';
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
                'code' => 50100,
                'msg' => 'Acct Not Found',
                'serialNo' => $session['serialNo'],
                'acctInfo' => [
                    'acctId' => $session['acctId'],
                    'userName' => $session['acctId'],
                    'balance' => 0,
                    'currency' => 'THB'
                ]
            ];
        }


        return $param;
    }

    public function transferOut($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SPADEGAMING')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['transferId'])
                ->where('con_2', 1)
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'transferId' => $session['transferId'],
                    'merchantTxId' => $session['transferId'],
                    'acctId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'code' => 0,
                    'msg' => 'success',
                    'serialNo' => $session['serialNo'],
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'SPADEGAMING')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
//                    ->where('con_1', $session['transferId'])
//                    ->where('con_2', 1)
                    ->where('con_3', $session['transferId'])
//                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'transferId' => $session['transferId'],
                        'merchantTxId' => $session['transferId'],
                        'acctId' => $member->user_name,
                        'balance' => (float)$member->balance_free,
                        'code' => 0,
                        'msg' => 'success',
                        'serialNo' => $session['serialNo'],
                    ];

                } else {


                    $balance = ($oldbalance - $session['amount']);
                    if ($balance >= 0) {

                        $member->balance_free -= $session['amount'];
                        $member->save();

                        $param = [
                            'transferId' => $session['transferId'],
                            'merchantTxId' => $session['transferId'],
                            'acctId' => $member->user_name,
                            'balance' => (float)$member->balance_free,
                            'code' => 0,
                            'msg' => 'success',
                            'serialNo' => $session['serialNo'],
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'SPADEGAMING';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['transferId'];
                        $session_in['con_2'] = $session['type'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    } else {

                        $param = [
                            'transferId' => $session['transferId'],
                            'merchantTxId' => $session['transferId'],
                            'acctId' => $member->user_name,
                            'balance' => (float)$member->balance_free,
                            'code' => 50110,
                            'msg' => 'Insufficient Balance',
                            'serialNo' => $session['serialNo'],
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'SPADEGAMING';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = 0;
                        $session_in['con_1'] = $session['transferId'];
                        $session_in['con_2'] = $session['type'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SPADEGAMING';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transferId'];
            $session_in['con_2'] = $session['type'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'transferId' => $session['transferId'],
                'merchantTxId' => $session['transferId'],
                'acctId' => $session['acctId'],
                'balance' => 0,
                'code' => 50100,
                'msg' => 'Acct Not Found',
                'serialNo' => $session['serialNo'],
            ];

        }


        return $param;
    }

    public function cancelBet($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SPADEGAMING')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['transferId'])
                ->where('con_2', 2)
//                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'transferId' => $session['transferId'],
                    'merchantTxId' => $session['referenceId'],
                    'acctId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'code' => 0,
                    'msg' => 'success',
                    'serialNo' => $session['serialNo'],
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'SPADEGAMING')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
//                    ->whereIn('method', ['bet','payout'])
                    ->where('method', 'bet')
                    ->where('con_1', $session['referenceId'])
//                    ->where('con_2', $session['type'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if (!$datasub) {

                    $param = [

                        'code' => 109,
                        'msg' => 'Reference No Not found',

                    ];

                } else {

                    $data_sub = GameLogProxy::where('company', 'SPADEGAMING')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
//                    ->whereIn('method', ['bet','payout'])
                        ->where('method', 'payout')
                        ->where('con_3', $session['referenceId'])
//                    ->where('con_2', $session['type'])
//                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($data_sub) {

                        $param = [
                            'transferId' => $session['transferId'],
                            'merchantTxId' => $session['referenceId'],
                            'acctId' => $member->user_name,
                            'balance' => (float)$member->balance_free,
                            'code' => 0,
                            'msg' => 'success',
                            'serialNo' => $session['serialNo'],
                        ];

                    } else {


                        if ($datasub['amount'] != 0) {

                            $member->balance_free += $datasub['amount'];
                            $member->save();

                        }

                        $param = [
                            'transferId' => $session['transferId'],
                            'merchantTxId' => $session['referenceId'],
                            'acctId' => $member->user_name,
                            'balance' => (float)$member->balance_free,
                            'code' => 0,
                            'msg' => 'success',
                            'serialNo' => $session['serialNo'],
                        ];


                    }
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SPADEGAMING';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['transferId'];
                $session_in['con_2'] = $session['type'];
                $session_in['con_3'] = $session['referenceId'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SPADEGAMING';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transferId'];
            $session_in['con_2'] = $session['type'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'transferId' => $session['transferId'],
                'merchantTxId' => $session['referenceId'],
                'acctId' => $session['acctId'],
                'balance' => 0,
                'code' => 50100,
                'msg' => 'Acct Not Found',
                'serialNo' => $session['serialNo'],
            ];
        }


        return $param;
    }

    public function transferIn($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SPADEGAMING')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['transferId'])
                ->where('con_2', 4)
//                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'transferId' => $session['transferId'],
                    'merchantTxId' => $session['referenceId'],
                    'acctId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'code' => 0,
                    'msg' => 'success',
                    'serialNo' => $session['serialNo'],
                ];

            } else {


                $datasub = GameLogProxy::where('company', 'SPADEGAMING')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['referenceId'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if (!$datasub) {

                    $param = [
                        'code' => 109,
                        'msg' => 'Reference No Not found',
                    ];

                } else {


                    $member->balance_free += $session['amount'];
                    $member->save();

                    $param = [
                        'transferId' => $session['transferId'],
                        'merchantTxId' => $session['referenceId'],
                        'acctId' => $member->user_name,
                        'balance' => (float)$member->balance_free,
                        'code' => 0,
                        'msg' => 'success',
                        'serialNo' => $session['serialNo'],
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'SPADEGAMING';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['transferId'];
                    $session_in['con_2'] = $session['type'];
                    $session_in['con_3'] = $session['referenceId'];
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
            $session_in['company'] = 'SPADEGAMING';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transferId'];
            $session_in['con_2'] = $session['type'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'transferId' => $session['transferId'],
                'merchantTxId' => $session['referenceId'],
                'acctId' => $session['acctId'],
                'balance' => 0,
                'code' => 50100,
                'msg' => 'Acct Not Found',
                'serialNo' => $session['serialNo'],
            ];
        }


        return $param;
    }

    public function transferBonus($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['acctId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'SPADEGAMING')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bonus')
                ->where('con_1', $session['transferId'])
                ->where('con_2', 7)
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'transferId' => $session['transferId'],
                    'merchantTxId' => $session['transferId'],
                    'acctId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'code' => 109,
                    'msg' => 'Duplicated transferId',
                    'serialNo' => $session['serialNo'],
                ];

            } else {

                $member->balance_free += $session['amount'];
                $member->save();

                $param = [
                    'transferId' => $session['transferId'],
                    'merchantTxId' => $session['transferId'],
                    'acctId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'code' => 0,
                    'msg' => 'success',
                    'serialNo' => $session['serialNo'],
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'SPADEGAMING';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bonus';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['transferId'];
                $session_in['con_2'] = $session['type'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SPADEGAMING';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bonus';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transferId'];
            $session_in['con_2'] = $session['type'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'transferId' => $session['transferId'],
                'merchantTxId' => $session['transferId'],
                'acctId' => $session['acctId'],
                'balance' => 0,
                'code' => 50100,
                'msg' => 'Acct Not Found',
                'serialNo' => $session['serialNo'],
            ];
        }


        return $param;
    }


}
