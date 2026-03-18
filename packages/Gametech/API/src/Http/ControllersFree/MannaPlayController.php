<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameListProxy;
use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class MannaPlayController extends AppBaseController
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

            $param = [
                'errorCode' => 0,
                'message' => 'success',
                'currency' => 'THB',
                'username' => $member->user_name,
                'balance' => (float)$member->balance_free,
                'token' => $session['token']
            ];

        } else {
            $param = [
                "errorCode" => 4,
                "message" => "Token expired"
            ];
        }

        return $param;
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();

        if ($session['sessionId']) {

            $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['account'], 'enable' => 'Y']);

            if ($member) {

                $param = [
                    'balance' => (float)$member->balance_free,
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'MANNAPLAY';
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
                    "errorCode" => 10105,
                    "message" => "SessionId or apikey is wrong or empty."
                ];
            }

        } else {

            $param = [
                "errorCode" => 10105,
                "message" => "SessionId or apikey is wrong or empty."
            ];


        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();

        if ($session['game_id']) {

            $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['account'], 'enable' => 'Y']);

            if ($member) {

                $datamain = GameListProxy::where('product', 'MANNA')->where('code', $session['game_id'])->first();

                if (!$datamain) {
                    return [
                        "errorCode" => 10109,
                        "message" => "Game not found!"
                    ];

                }

                $data = GameLogProxy::where('company', 'MANNAPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['transaction_id'])
//                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();


                $oldbalance = $member->balance_free;

                if ($data) {

                    $param = [
                        "errorCode" => 10208,
                        "message" => "Transaction id exists!"
                    ];


                } else {

                    if ($session['amount'] < 0) {
                        $param = [
                            "errorCode" => 10201,
                            "message" => "Warning value must not be less 0."
                        ];
                    } else {


                        $balance = ($oldbalance - $session['amount']);
                        if ($balance >= 0) {

                            MemberProxy::where('user_name', $session['account'])->decrement('balance_free', $session['amount']);
                            $member = MemberProxy::where('user_name', $session['account'])->first();

//                            $member->balance_free -= $session['amount'];
//                            $member->save();

                            $param = [
                                'balance' => (float)$member->balance_free,
                                'transaction_id' => $session['transaction_id']
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'MANNAPLAY';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'bet';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['amount'];
                            $session_in['con_1'] = $session['transaction_id'];
                            $session_in['con_2'] = $session['round_id'];
                            $session_in['con_3'] = null;
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);


                        } else {

                            $param = [
                                "errorCode" => 10203,
                                "message" => "Balance value error. Insufficient balance"
                            ];
                        }

                    }
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'MANNAPLAY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'out';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['round_id'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            } else {
                $param = [
                    "errorCode" => 10105,
                    "message" => "SessionId or apikey is wrong or empty."
                ];
            }

        } else {

            $param = [
                "errorCode" => 10109,
                "message" => "Game not found!"
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['account'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'MANNAPLAY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['transaction_id'])
//                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    "errorCode" => 10208,
                    "message" => "Transaction id exists!"
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'MANNAPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
//                    ->where('con_1', $session['transaction_id'])
                    ->where('con_2', $session['round_id'])
//                ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    if ($session['amount'] < 0) {
                        $param = [
                            "errorCode" => 10201,
                            "message" => "Warning value must not be less 0."
                        ];
                    } else {

                        MemberProxy::where('user_name', $session['account'])->increment('balance_free', $session['amount']);
                        $member = MemberProxy::where('user_name', $session['account'])->first();

//                        $member->balance_free += $session['amount'];
//                        $member->save();

                        $param = [
                            'balance' => (float)$member->balance_free,
                            'transaction_id' => $session['transaction_id']
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'MANNAPLAY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'payout';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['transaction_id'];
                        $session_in['con_2'] = $session['round_id'];
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
                        "errorCode" => 10212,
                        "message" => "Round was not found!"
                    ];
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'MANNAPLAY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['transaction_id'];
            $session_in['con_2'] = $session['round_id'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                "errorCode" => 10105,
                "message" => "SessionId or apikey is wrong or empty."
            ];
        }


        return $param;
    }

    public function transferInJackpot(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['account'], 'enable' => 'Y']);
        if ($member) {

            $oldbalance = $member->balance_free;

            if ($session['transaction_id'] == '') {

                $param = [
                    "errorCode" => 10211,
                    "message" => "Transaction id not found!"
                ];

            } else {


                $data = GameLogProxy::where('company', 'MANNAPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'jackpot')
                    ->where('con_1', $session['transaction_id'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();


                if ($data) {

                    $param = [
                        "errorCode" => 10208,
                        "message" => "Transaction id exists!"
                    ];

                } else {

                    $balance = ($oldbalance + $session['jp_win']);

                    MemberProxy::where('user_name', $session['account'])->increment('balance_free', $session['jp_win']);
                    $member = MemberProxy::where('user_name', $session['account'])->first();


//                    $member->balance_free += $session['jp_win'];
//                    $member->save();

                    $param = [
                        'balance' => (float)$member->balance_free,
                        'transaction_id' => $session['transaction_id']
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'MANNAPLAY';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'jackpot';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['jp_win'];
                    $session_in['con_1'] = $session['transaction_id'];
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
            $session_in['company'] = 'MANNAPLAY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'jackpot';
            $session_in['response'] = 'in';
            $session_in['amount'] = $session['jp_win'];
            $session_in['con_1'] = $session['transaction_id'];
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
                "errorCode" => 10105,
                "message" => "SessionId or apikey is wrong or empty."
            ];

        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();

        if ($session['target_transaction_id']) {


            $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['account'], 'enable' => 'Y']);

            if ($member) {

                $data = GameLogProxy::where('company', 'MANNAPLAY')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['transaction_id'])
//                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                $oldbalance = $member->balance_free;
                $amount = 0;

                if ($data) {

                    $param = [
                        "errorCode" => 10208,
                        "message" => "Transaction id exists!"
                    ];

                } else {

                    $datasub = GameLogProxy::where('company', 'MANNAPLAY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->whereIn('method', ['bet', 'payout'])
                        ->where('con_1', $session['target_transaction_id'])
//                        ->whereNull('con_2')
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        if ($datasub['method'] == 'bet') {

                            $amount = $datasub['input']['amount'];

                            MemberProxy::where('user_name', $session['account'])->increment('balance_free', $datasub['input']['amount']);
                            $member = MemberProxy::where('user_name', $session['account'])->first();


//                            $member->balance_free += $datasub['input']['amount'];
//                            $member->save();

                            $param = [
                                'balance' => (float)$member->balance_free,
                                'transaction_id' => $session['transaction_id']
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'MANNAPLAY';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'cancel';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $amount;
                            $session_in['con_1'] = $session['transaction_id'];
                            $session_in['con_2'] = $session['target_transaction_id'];
                            $session_in['con_3'] = null;
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'balance' => (float)$member->balance_free,
                                'transaction_id' => $session['transaction_id']
                            ];

                        }

                    } else {

                        $param = [
                            "errorCode" => 10210,
                            "message" => "Target transaction id not found!"
                        ];

                    }
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'MANNAPLAY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'out';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['target_transaction_id'];

                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            } else {
                $param = [
                    "errorCode" => 10100,
                    "message" => "Server is not ready!"
                ];
            }

        } else {

            $param = [
                "errorCode" => 10102,
                "message" => "Post data is missing some necessary parameters."
            ];

        }


        return $param;
    }


}
