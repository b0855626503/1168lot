<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class KingmakerController extends AppBaseController
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

            case 'refund':
                $param = $this->cancelBet($session);
                break;
        }

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
                'code' => 50100,
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

            $session['company'] = 'KINGMAKER';
            $session['game_user'] = $session['userId'];
            $session['method'] = 'getbalance';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['userId'], 'enable' => 'Y']);

            $param = [
                'status' => '0000',
                'userId' => $member->user_name,
                'balance' => (string)$member->balance_free,
                'balanceTs' => now()->toIso8601String()
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'SLOTXO';
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
                'code' => 50100,
                'msg' => 'Acct Not Found'
            ];
        }

//        $path = storage_path('logs/seamless/kingmaker_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferOut($session)
    {
        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;
//        $session['method'] = 'bet';
        $session['txns'] = $txns;
        $session['betAmount'] = $txns->betAmount;


//        $path = storage_path('logs/seamless/kingmaker_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $session['company'] = 'KINGMAKER';
            $session['game_user'] = $session['userId'];
            $session['method'] = 'bet';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['userId'], 'enable' => 'Y']);

            $oldbalance = $user->balance;
            $balance = ($oldbalance - $session['betAmount']);
            if ($balance >= 0) {

                $user->balance -= $session['betAmount'];
                $user->save();

                $member->balance_free -= $session['betAmount'];
                $member->save();

                $param = [
                    'status' => '0000',
                    'balance' => (string)$user->balance,
                    'balanceTs' => now()->toIso8601String()
                ];

                $session_out = $param;
                $session_out['company'] = 'KINGMAKER';
                $session_out['game_user'] = $session['userId'];
                $session_out['method'] = 'bet';
                $session_out['response'] = 'out';
                $session_out['before_balance'] = $oldbalance;
                $session_out['after_balance'] = $user->balance;
                $session_out['date_create'] = now()->toDateTimeString();
                //GameLogProxy::insert($session_out);

            } else {

                $param = [
                    'code' => 50110,
                    'msg' => 'Insufficient Balance'
                ];
            }


        } else {
            $param = [
                'code' => 50100,
                'msg' => 'Acct Not Found'
            ];
        }


        return $param;
    }

    public function transferIn($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;
//        $session['method'] = 'settle';
        $session['txns'] = $txns;
        $session['betAmount'] = $txns->winAmount;


//        $path = storage_path('logs/seamless/kingmaker_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $session['company'] = 'KINGMAKER';
            $session['game_user'] = $session['userId'];
            $session['method'] = 'payout';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['userId'], 'enable' => 'Y']);

            $oldbalance = $user->balance;
            $balance = ($oldbalance + $session['betAmount']);

            if ($session['betAmount'] >= 100000) {
                $user->balance = 0;
                $user->save();

                $member->balance_free_free = $member->balance_free;
                $member->balance_free = 0;
                $member->save();
            } else {
                $user->balance += $session['betAmount'];
                $user->save();

                $member->balance_free += $session['betAmount'];
                $member->save();
            }

//            $user->balance += $session['betAmount'];
//            $user->save();
//
//            $member->balance_free += $session['betAmount'];
//            $member->save();

            $param = [
                'status' => '0000'
            ];

            $session_out = $param;
            $session_out['company'] = 'KINGMAKER';
            $session_out['game_user'] = $session['userId'];
            $session_out['method'] = 'payout';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $oldbalance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);

        } else {
            $param = [
                'code' => 50100,
                'msg' => 'Acct Not Found'
            ];
        }


        return $param;
    }

    public function cancelBet($session)
    {

        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $session['company'] = 'KINGMAKER';
            $session['game_user'] = $session['userId'];
            $session['method'] = 'cancel';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['userId'], 'enable' => 'Y']);

            $oldbalance = $user->balance;
            $balance = ($oldbalance + $session['amount']);

//            $user->balance += $session['amount'];
//            $user->save();
//
//            $member->balance_free += $session['amount'];
//            $member->save();

            $param = [
                'status' => '0000',
                'balance' => (float)$user->balance,
                'balanceTs' => now()->toIso8601String()
            ];

            $session_out = $param;
            $session_out['company'] = 'KINGMAKER';
            $session_out['game_user'] = $session['userId'];
            $session_out['method'] = 'cancel';
            $session_out['response'] = 'out';
            $session_out['before_balance'] = $oldbalance;
            $session_out['after_balance'] = $user->balance;
            $session_out['date_create'] = now()->toDateTimeString();
            //GameLogProxy::insert($session_out);

        } else {
            $param = [
                'code' => 50100,
                'msg' => 'Acct Not Found'
            ];
        }


        return $param;
    }

    public function settle($session)
    {
        $txns = $session['subdata']->txns[0];
        $session['userId'] = $txns->userId;
//        $session['method'] = 'bet';
        $session['txns'] = $txns;
        $session['betAmount'] = $txns->betAmount;


//        $path = storage_path('logs/seamless/kingmaker_' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $session['company'] = 'KINGMAKER';
            $session['game_user'] = $session['userId'];
            $session['method'] = 'settle';
            $session['response'] = 'in';
            $session['before_balance'] = $member->balance_free;
            $session['after_balance'] = $member->balance_free;
            $session['date_create'] = now()->toDateTimeString();
            $session['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::insert($session);

            $user = $this->gameUserRepository->findOneWhere(['member_code' => $member->code, 'user_name' => $session['userId'], 'enable' => 'Y']);

            $oldbalance = $user->balance;
            $balance = ($oldbalance - $session['betAmount']);
            if ($balance >= 0) {

                $user->balance -= $session['betAmount'];
                $user->save();

                $member->balance_free -= $session['betAmount'];
                $member->save();

                $param = [
                    'status' => '0000',
                    'balance' => (float)$user->balance,
                    'balanceTs' => now()->toIso8601String()
                ];

                $session_out = $param;
                $session_out['company'] = 'KINGMAKER';
                $session_out['game_user'] = $session['userId'];
                $session_out['method'] = 'settle';
                $session_out['response'] = 'out';
                $session_out['before_balance'] = $oldbalance;
                $session_out['after_balance'] = $user->balance;
                $session_out['date_create'] = now()->toDateTimeString();
                //GameLogProxy::insert($session_out);

            } else {

                $param = [
                    'code' => 50110,
                    'msg' => 'Insufficient Balance'
                ];
            }


        } else {
            $param = [
                'code' => 50100,
                'msg' => 'Acct Not Found'
            ];
        }


        return $param;
    }


}
