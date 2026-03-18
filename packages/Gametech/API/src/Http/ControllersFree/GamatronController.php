<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class GamatronController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['launchToken'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'errorCode' => 0,
                'description' => 'Success',
                'balance' => (float)$member->balance_free,
                'currency' => 'THB',
                'playerId' => $member->user_name,
                'sessionId' => $session['launchToken'],
                'account' => [
                    'country' => 'TH',
                    'birthDate' => '',
                    'gender' => 'male',
                    'alias' => $member->user_name
                ]
            ];

        } else {

            $param = [
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];

        }

        return $param;
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {


            $param = [
                'errorCode' => 0,
                'description' => 'Success',
                'balance' => (float)$member->balance_free,
                'currency' => 'THB'
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'GAMATRON';
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
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];

        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'GAMATRON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['game'])
                ->where('con_2', $session['gameRound'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'errorCode' => 200,
                    'description' => 'Transaction Declined'
                ];


            } else {

                $balance = ($oldbalance - $session['amount']);
                if ($balance >= 0) {

                    $member->balance_free -= $session['amount'];
                    $member->save();

                    $param = [
                        'errorCode' => 0,
                        'description' => 'Success',
                        'balance' => (float)$member->balance_free,
                        'currency' => 'THB'
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'GAMATRON';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['game'];
                    $session_in['con_2'] = $session['gameRound'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);


                } else {

                    $param = [
                        'errorCode' => 201,
                        'description' => 'Insufficient funds'
                    ];

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'GAMATRON';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['game'];
            $session_in['con_2'] = $session['gameRound'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'GAMATRON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['game'])
                ->where('con_2', $session['gameRound'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'errorCode' => 200,
                    'description' => 'Transaction Declined'
                ];

            } else {

                $member->balance_free += $session['amount'];
                $member->save();

                $param = [
                    'errorCode' => 0,
                    'description' => 'Success',
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'GAMATRON';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['game'];
                $session_in['con_2'] = $session['gameRound'];
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
            $session_in['company'] = 'GAMATRON';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['game'];
            $session_in['con_2'] = $session['gameRound'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];
        }


        return $param;
    }

    public function withdrawDeposit(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'GAMATRON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'withdrawDeposit')
                ->where('con_1', $session['game'])
                ->where('con_2', $session['gameRound'])
                ->where('con_3', $session['roundEnd'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;
            $amount = 0;

            if ($data) {

                $param = [
                    'errorCode' => 200,
                    'description' => 'Transaction Declined'
                ];

            } else {

                $balance = ($oldbalance - $session['withdrawAmount'] + $session['depositAmount']);

                $amount -= $session['withdrawAmount'];
                $amount += $session['depositAmount'];

                $member->balance_free -= $session['withdrawAmount'];
                $member->balance_free += $session['depositAmount'];
                $member->save();

                $param = [
                    'errorCode' => 0,
                    'description' => 'Success',
                    'balance' => (float)$member->balance_free,
                    'currency' => 'THB'
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'GAMATRON';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'withdrawDeposit';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['game'];
                $session_in['con_2'] = $session['gameRound'];
                $session_in['con_3'] = $session['roundEnd'];
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'GAMATRON';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'withdrawDeposit';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['game'];
            $session_in['con_2'] = $session['gameRound'];
            $session_in['con_3'] = $session['roundEnd'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {

            $param = [
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['playerId'], 'enable' => 'Y']);
        if ($member) {

            $data = GameLogProxy::where('company', 'GAMATRON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'rollback')
                ->where('con_1', $session['game'])
                ->where('con_2', $session['gameRound'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;
            $amount = 0;

            if ($data) {

                $param = [
                    'errorCode' => 200,
                    'description' => 'Transaction Declined'
                ];

            } else {

                $datasubs = GameLogProxy::where('company', 'GAMATRON')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', '!=', 'rollback')
                    ->where('con_1', $session['game'])
                    ->where('con_2', $session['gameRound'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->get();


                if (count($datasubs) > 0) {

                    foreach ($datasubs as $datasub) {


                        if ($datasub['method'] == 'bet') {

                            $amount += $datasub['input']['amount'];
                            $member->balance_free += $datasub['input']['amount'];
                            $member->save();


                        } elseif ($datasub['method'] == 'payout') {

                            $balance = ($oldbalance - $datasub['input']['amount']);
                            $amount -= $datasub['input']['amount'];

                            $member->balance_free -= $datasub['input']['amount'];
                            $member->save();


                        } elseif ($datasub['method'] == 'withdrawDeposit') {

                            $amount += $datasub['input']['withdrawAmount'];
                            $amount -= $datasub['input']['depositAmount'];

                            $member->balance_free += $datasub['input']['withdrawAmount'];
                            $member->balance_free -= $datasub['input']['depositAmount'];
                            $member->save();

                        }
                    }

                    $param = [
                        'errorCode' => 0,
                        'description' => 'Success',
                        'balance' => (float)$member->balance_free,
                        'currency' => 'THB'
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'GAMATRON';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'rollback';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amount;
                    $session_in['con_1'] = $session['game'];
                    $session_in['con_2'] = $session['gameRound'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $param = [
                        'errorCode' => 200,
                        'description' => 'Transaction Declined'
                    ];

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'GAMATRON';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'rollback';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['game'];
            $session_in['con_2'] = $session['gameRound'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'errorCode' => 103,
                'description' => 'Session Expired'
            ];
        }


        return $param;
    }

    public function lists(Request $request)
    {

    }


}
