<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class UpgSlotController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'balance' => (float)$member->balance_free,
                'currency' => 'THB'
            ];

        } else {
            $param = [
                "code" => 404,
                "message" => "player not found"
            ];
        }

        return $param;
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);

        if ($member) {


            $param = [
                'balance' => (float)$member->balance_free,
                'currency' => 'THB'
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'UPG';
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
                "code" => 404,
                "message" => "player not found"
            ];
        }


        return $param;
    }

    public function updateBalance(Request $request)
    {
        $session = $request->all();
        if ($session['txnType'] == 'DEBIT') {

            return $this->transferOut($session);

        } elseif ($session['txnType'] == 'CREDIT') {

            return $this->transferIn($session);

        }

    }

    public function transferOut($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);

        if ($member) {

            if ($session['amount'] < 0) {
                $param = [
                    "code" => 400,
                    "message" => "Bad request"
                ];
                return $param;
            }

            $data = GameLogProxy::where('company', 'UPG')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['txnId'])
                ->where('con_2', $session['betId'])
//                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'extTxnId' => $session['txnId'],
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'UPG')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['txnId'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'extTxnId' => $session['txnId'],
                        'balance' => (float)$member->balance_free,
                        'currency' => 'THB'
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'UPG';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['txnId'];
                    $session_in['con_2'] = $session['betId'];
//                    $session_in['con_2'] = null;
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {


                    $balance = ($oldbalance - $session['amount']);

                    if ($balance >= 0) {

                        $member->balance_free -= $session['amount'];
                        $member->save();

                        $param = [
                            'extTxnId' => $session['txnId'],
                            'balance' => (float)$member->balance_free,
                            'currency' => 'THB'
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'UPG';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['txnId'];
                        $session_in['con_2'] = $session['betId'];
//                        $session_in['con_2'] = null;
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    } else {

                        $param = [
                            "code" => 402,
                            "message" => "Not enough available balance"
                        ];
                    }
                }
            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'UPG';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'bet';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['txnId'];
//            $session_in['con_2'] = null;
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);


        } else {
            $param = [
                "code" => 404,
                "message" => "player not found"
            ];
        }


        return $param;
    }

    public function transferIn($session)
    {


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);

        if ($member) {

            if ($session['amount'] < 0) {
                $param = [
                    "code" => 400,
                    "message" => "Bad request"
                ];
                return $param;
            }

            $data = GameLogProxy::where('company', 'UPG')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['txnId'])
                ->where('con_2', $session['betId'])
//                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'extTxnId' => $session['txnId'],
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'UPG')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['txnId'])
                    ->where('con_2', $session['betId'])
//                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $datasubs = GameLogProxy::where('company', 'UPG')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'cancel')
                        ->where('con_1', $datasub['con_1'])
//                        ->where('con_2', $session['betId'])
                        ->whereNull('con_2')
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasubs) {

                        $param = [
                            "code" => 404,
                            "message" => "player/transaction not found"
                        ];

                    } else {


                        $member->balance_free += $session['amount'];
                        $member->save();

                        $param = [
                            'extTxnId' => $session['txnId'],
                            'balance' => (float)$member->balance_free,
                            'currency' => 'THB'
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'UPG';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'payout';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['txnId'];
                        $session_in['con_2'] = $session['betId'];
//                    $session_in['con_2'] = null;
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }
                } else {

                    $param = [
                        "code" => 404,
                        "message" => "player/transaction not found"
                    ];

                }

            }

//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'UPG';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'payout';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['txnId'];
//            $session_in['con_2'] = null;
//            $session_in['con_3'] = null;
//            $session_in['con_4'] = null;
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);


        } else {
            $param = [
                "code" => 404,
                "message" => "player not found"
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['playerId'], 'enable' => 'Y']);

        if ($member) {

            if ($session['amount'] < 0) {
                $param = [
                    "code" => 400,
                    "message" => "Bad request"
                ];
                return $param;
            }

            $data = GameLogProxy::where('company', 'UPG')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['txnId'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'extTxnId' => $session['txnId'],
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'UPG')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
//                    ->whereIn('method', ['bet', 'payout'])
                    ->where('method', 'bet')
                    ->where('con_1', $session['txnId'])
//                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {


                    $datasubs = GameLogProxy::where('company', 'UPG')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
//                    ->whereIn('method', ['bet', 'payout'])
                        ->where('method', 'payout')
                        ->where('con_2', $datasub['con_2'])
//                    ->whereNull('con_2')
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasubs) {

                        $param = [
                            "code" => 404,
                            "message" => "player/transaction not found"
                        ];

                    } else {

                        $member->balance_free += $datasub['amount'];
                        $member->save();


//                    if ($datasub['method'] == 'bet') {
//
//                        $member->balance_free += $session['amount'];
//                        $member->save();
//
//                    } elseif ($datasub['method'] == 'payout') {
//
//                        $member->balance_free -= $session['amount'];
//                        $member->save();
//
//                    }

                        $param = [
                            'extTxnId' => $session['txnId'],
                            'balance' => (float)$member->balance_free,
                            'currency' => 'THB'
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'UPG';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'cancel';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['txnId'];
                        $session_in['con_2'] = null;
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                        GameLogProxy::where('company', 'UPG')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'bet')
                            ->where('con_1', $session['txnId'])
                            ->whereNull('con_3')
                            ->whereNull('con_4')
                            ->update(['con_4' => 'complete']);

                    }
                } else {

                    $param = [
                        'extTxnId' => $session['txnId'],
                        'balance' => (float)$member->balance_free,
                        'currency' => 'THB'
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'UPG';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['txnId'];
                    $session_in['con_2'] = null;
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
            $session_in['company'] = 'UPG';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['txnId'];
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                "code" => 404,
                "message" => "player not found"
            ];
        }

        return $param;
    }

    public function lists(Request $request)
    {

    }


}
