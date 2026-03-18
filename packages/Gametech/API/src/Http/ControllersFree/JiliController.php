<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class JiliController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'errorCode' => 0,
                'accountId' => $member->user_name,
                'balance' => (float)$member->balance_free,
                'currency' => 'THB',
                'returnTime' => now()->toISOString()
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JILI';
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
                "errorCode" => 102,
                "Description" => "player not found"
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JILI')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['game'])
                ->where('con_2', $session['round'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    "errorCode" => 1,
                    "message" => "Already accepted"
                ];

            } else {

                $balance = ($oldbalance - $session['betAmount']);
                if ($balance >= 0) {

//                    $balance = $balance + $session['winloseAmount'];
//                    $member->balance_free = $balance;
//                    $member->save();

                    MemberProxy::where('user_name', $member->user_name)->decrement('balance_free', $session['betAmount']);
                    MemberProxy::where('user_name', $member->user_name)->increment('balance_free', $session['winloseAmount']);
                    $member = MemberProxy::where('user_name', $member->user_name)->first();


                    $param = [
                        'errorCode' => 0,
                        'message' => 'success',
                        'currency' => 'THB',
                        'username' => $member->user_name,
                        'balance' => (float)$member->balance_free,
                        'token' => $session['token'],
                        'txId' => $session['round']
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'JILI';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['winloseAmount'] - $session['betAmount'];
                    $session_in['con_1'] = $session['game'];
                    $session_in['con_2'] = $session['round'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);


                } else {

                    $param = [
                        "errorCode" => 2,
                        "message" => "Not enough balance"
                    ];
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JILI';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['winloseAmount'] - $session['betAmount'];
            $session_in['con_1'] = $session['game'];
            $session_in['con_2'] = $session['round'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                "errorCode" => 4,
                "message" => "Token expired"
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JILI')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['round'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    "errorCode" => 1,
                    "message" => "Already canceled"
                ];

            } else {

                $balance = ($oldbalance + $session['amount']);

//                $member->balance_free += $session['amount'];
//                $member->save();

                MemberProxy::where('user_name', $member->user_name)->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $member->user_name)->first();


                $param = [
                    'errorCode' => 0,
                    'accountId' => $member->user_name,
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB',
                    'returnTime' => now()->toISOString()
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JILI';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['round'];
                $session_in['con_2'] = null;
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
            $session_in['company'] = 'JILI';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['round'];
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
                "errorCode" => 102,
                "Description" => "player not found"
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        if ($session['round']) {

            $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'enable' => 'Y']);

            if ($member) {

                $data = GameLogProxy::where('company', 'JILI')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
                    ->where('con_1', $session['game'])
                    ->where('con_2', $session['round'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                $oldbalance = $member->balance_free;

                if ($data) {

                    $param = [
                        "errorCode" => 1,
                        "message" => "Already canceled"
                    ];

                } else {

                    $datasub = GameLogProxy::where('company', 'JILI')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'bet')
                        ->where('con_1', $session['game'])
                        ->where('con_2', $session['round'])
                        ->whereNull('con_3')
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $balance = ($oldbalance - $session['winloseAmount'] + $session['betAmount']);

                        if ($balance < 0) {

                            $param = [
                                "errorCode" => 6,
                                "message" => "The bet is already accepted and cannot be cancelled"
                            ];

                        } else {


                            MemberProxy::where('user_name', $member->user_name)->decrement('balance_free', $session['winloseAmount']);
                            MemberProxy::where('user_name', $member->user_name)->increment('balance_free', $session['betAmount']);
                            $member = MemberProxy::where('user_name', $member->user_name)->first();

                            $param = [
                                'errorCode' => 0,
                                'message' => 'success',
                                'currency' => 'THB',
                                'username' => $member->user_name,
                                'balance' => (float)$member->balance_free,
                                'token' => $session['token'],
                                'txId' => $session['round']
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'JILI';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'cancel';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $session['betAmount'] - $session['winloseAmount'];
                            $session_in['con_1'] = $session['game'];
                            $session_in['con_2'] = $session['round'];
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
                            "errorCode" => 2,
                            "message" => "Round not found"
                        ];

                    }

                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JILI';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'out';
                $session_in['amount'] = $session['betAmount'] - $session['winloseAmount'];
                $session_in['con_1'] = $session['game'];
                $session_in['con_2'] = $session['round'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            } else {
                $param = [
                    "errorCode" => 3,
                    "message" => "Invalid parameter"
                ];
            }

        } else {

            $param = [
                "errorCode" => 2,
                "message" => "Round not found"
            ];

        }


        return $param;
    }


}
