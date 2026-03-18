<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class CreativeGamingController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionId'], 'user_name' => $session['accountId'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'errorCode' => 0,
                'accountId' => $member->user_name,
                'nickName' => $member->user_name
            ];

        } else {
            $param = [
                "errorCode" => 102
            ];
        }

        return $param;
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);
        if ($member) {


            $session['company'] = 'CREATIVE';
            $session['game_user'] = $session['accountId'];
            $session['method'] = 'getbalance';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['accountId'], 'enable' => 'Y']);

            $param = [
                'errorCode' => 0,
                'accountId' => $member->user_name,
                'balance' => (float)$user->balance,
                'currency' => 'THB',
                'returnTime' => now()->toISOString()
            ];

            $session_out = $param;
            $session_out['company'] = 'CREATIVE';
            $session_out['game_user'] = $session['accountId'];
            $session_out['method'] = 'getbalance';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $user->balance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);

        } else {

            $param = [
                "errorCode" => 102,
                "returnTime" => now()->toISOString(),
                "accountId" => $session['accountId'],
                "balance" => 0,
                "currency" => 'THB'
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'CREATIVE')
                ->where('method', 'bet')
                ->where('mtcode', $session['mtcode'])
                ->first();

            $session['company'] = 'CREATIVE';
            $session['game_user'] = $session['accountId'];
            $session['method'] = 'bet';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['accountId'], 'enable' => 'Y']);
            $oldbalance = $user->balance;

            if ($data) {

                $param = [
                    'errorCode' => 105,
                    'accountId' => $member->user_name,
                    'balance' => (float)$user->balance,
                    'currency' => 'THB',
                    'returnTime' => now()->toISOString()
                ];

            } else {

                $balance = ($oldbalance - $session['amount']);
                if ($balance >= 0) {

                    $user->balance -= $session['amount'];
                    $user->save();

                    $member->balance_free -= $session['amount'];
                    $member->save();

                    $param = [
                        'errorCode' => 0,
                        'accountId' => $member->user_name,
                        'balance' => (float)$user->balance,
                        'currency' => 'THB',
                        'returnTime' => now()->toISOString()
                    ];

                } else {

                    $param = [
                        'errorCode' => 101,
                        'accountId' => $member->user_name,
                        'balance' => (float)$user->balance,
                        'currency' => 'THB',
                        'returnTime' => now()->toISOString()
                    ];
                }

            }

            $session_out = $param;
            $session_out['company'] = 'CREATIVE';
            $session_out['game_user'] = $session['accountId'];
            $session_out['method'] = 'bet';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $oldbalance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);


        } else {

            $param = [
                "errorCode" => 102,
                "returnTime" => now()->toISOString(),
                "accountId" => $session['accountId'],
                "balance" => 0,
                "currency" => 'THB'
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'CREATIVE')
                ->where('method', 'payout')
                ->where('mtcode', $session['mtcode'])
                ->first();

            $session['company'] = 'CREATIVE';
            $session['game_user'] = $session['accountId'];
            $session['method'] = 'payout';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['accountId'], 'enable' => 'Y']);
            $oldbalance = $user->balance;

            if ($data) {

                $param = [
                    'errorCode' => 105,
                    'accountId' => $member->user_name,
                    'balance' => (float)$user->balance,
                    'currency' => 'THB',
                    'returnTime' => now()->toISOString()
                ];

            } else {

                $user->balance += $session['amount'];
                $user->save();

                $member->balance_free += $session['amount'];
                $member->save();

                $param = [
                    'errorCode' => 0,
                    'accountId' => $member->user_name,
                    'balance' => (float)$user->balance,
                    'currency' => 'THB',
                    'returnTime' => now()->toISOString()
                ];

            }


            $session_out = $param;
            $session_out['company'] = 'CREATIVE';
            $session_out['game_user'] = $session['accountId'];
            $session_out['method'] = 'payout';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $oldbalance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);

        } else {
            $param = [
                "errorCode" => 102,
                "returnTime" => now()->toISOString(),
                "accountId" => $session['accountId'],
                "balance" => 0,
                "currency" => 'THB'
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['accountId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'CREATIVE')
                ->where('method', 'cancel')
                ->where('mtcode', $session['mtcode'])
                ->first();

            $session['company'] = 'CREATIVE';
            $session['game_user'] = $session['accountId'];
            $session['method'] = 'cancel';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['accountId'], 'enable' => 'Y']);
            $oldbalance = $user->balance;

            if ($data) {

                $param = [
                    'errorCode' => 105,
                    'accountId' => $member->user_name,
                    'balance' => (float)$user->balance,
                    'currency' => 'THB',
                    'returnTime' => now()->toISOString()
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'CREATIVE')
                    ->where('method', 'bet')
                    ->where('mtcode', $session['mtcode'])
                    ->first();

                if ($datasub) {

                    $user->balance += $datasub['amount'];
                    $user->save();

                    $member->balance_free += $datasub['amount'];
                    $member->save();

                    $param = [
                        'errorCode' => 0,
                        'accountId' => $member->user_name,
                        'balance' => (float)$user->balance,
                        'currency' => 'THB',
                        'returnTime' => now()->toISOString()
                    ];


                } else {

                    $param = [
                        'errorCode' => 103,
                        'accountId' => $member->user_name,
                        'balance' => (float)$user->balance,
                        'currency' => 'THB',
                        'returnTime' => now()->toISOString()
                    ];

                }

            }

            $session_out = $param;
            $session_out['company'] = 'CREATIVE';
            $session_out['game_user'] = $session['accountId'];
            $session_out['method'] = 'cancel';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $oldbalance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);

        } else {
            $param = [
                "errorCode" => 102,
                "returnTime" => now()->toISOString(),
                "accountId" => $session['accountId'],
                "balance" => 0,
                "currency" => 'THB'
            ];
        }


        return $param;
    }

    public function lists(Request $request)
    {

        $session = $request->all();

        $data = GameLogProxy::where('company', 'CREATIVE')
            ->where('response', 'out')
            ->where('roundId', $session['roundId'])
            ->where('mtcode', $session['mtcode'])
            ->first();

        if ($data) {

            $param = [
                "errorCode" => 0,
                "returnTime" => now()->toISOString(),
                "data" => [
                    "transaction_id" => $data['roundId'],
                    "action" => $data['method'],
                    "target" => [
                        "account" => $data['accountId']
                    ],
                    "balance" => [
                        "before" => $data['before_balance'],
                        "after" => $data['after_balance']
                    ],
                    "status" => [
                        "createtime" => $data['eventTime'],
                        "endtime" => $data['eventTime'],
                        "status" => "success",
                        "message" => "success"
                    ],
                    "currency" => 'THB',
                    "incident" => [
                        "mtcode" => $data['mtcode'],
                        "amount" => $data['amount'],
                        "eventtime" => $data['eventTime']
                    ]
                ]
            ];

        } else {

            $param = [
                "errorCode" => 107,
                "returnTime" => now()->toISOString(),
                "accountId" => null,
                "balance" => 0,
                "currency" => 'THB'
            ];

        }

        return $param;

    }


}
