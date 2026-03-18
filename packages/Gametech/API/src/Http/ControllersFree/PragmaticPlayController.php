<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class PragmaticPlayController extends AppBaseController
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
                'userId' => $member->user_name,
                'currency' => 'THB',
                'cash' => $member->balance_free,
                'bonus' => 0,
                'country' => 'TH',
                'error' => 0,
                'description' => 'Success'
            ];
        } else {
            $param = [
                "error" => 4,
                "description" => "Player authentication failed due to invalid, not found or expired token.."
            ];
        }

//        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- VERIFY --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'currency' => 'THB',
                'cash' => $member->balance_free,
                'bonus' => 0,
                'error' => 0,
                'description' => 'Success'
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRAGMATIC';
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
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];
        }

//        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GETBALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRAGMATIC')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['hash'])
                ->where('con_2', $session['reference'])
                ->where('con_3', $session['gameId'])
                ->where('con_4', $session['roundId'])
                ->first();

            if ($data) {

            } else {

                $balance = $member->balance_free - $session['amount'];
                if ($balance >= 0) {

                    $member->balance_free -= $session['amount'];
                    $member->save();

                    $param = [
                        'transactionId' => now()->getTimestampMs(),
                        'currency' => 'THB',
                        'cash' => $member->balance_free,
                        'bonus' => 0,
                        'usedPromo' => 0,
                        'error' => 0,
                        'description' => 'Success'
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'PRAGMATIC';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['hash'];
                    $session_in['con_2'] = $session['reference'];
                    $session_in['con_3'] = $session['gameId'];
                    $session_in['con_4'] = $session['roundId'];
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRAGMATIC';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['hash'];
            $session_in['con_2'] = $session['reference'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = $session['roundId'];
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

//        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRAGMATIC')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['hash'])
                ->where('con_2', $session['reference'])
                ->where('con_3', $session['gameId'])
                ->where('con_4', $session['roundId'])
                ->first();

            if ($data) {

            } else {

                $member->balance_free += $session['amount'];
                $member->save();

                $param = [
                    'transactionId' => now()->getTimestampMs(),
                    'currency' => 'THB',
                    'cash' => $member->balance_free,
                    'bonus' => 0,
                    'usedPromo' => 0,
                    'error' => 0,
                    'description' => 'Success'
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRAGMATIC';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['hash'];
                $session_in['con_2'] = $session['reference'];
                $session_in['con_3'] = $session['gameId'];
                $session_in['con_4'] = $session['roundId'];
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRAGMATIC';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['hash'];
            $session_in['con_2'] = $session['reference'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = $session['roundId'];
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- PAYOUT --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function endRound(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $param = [
                'cash' => $member->balance_free,
                'bonus' => 0,
                'error' => 0,
                'description' => 'Success'
            ];


        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- ENDROUND --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function refund(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRAGMATIC')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'refund')
                ->where('con_1', $session['hash'])
                ->where('con_2', $session['reference'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

            } else {

                $member->balance_free += $session['amount'];
                $member->save();

                $param = [
                    'transactionId' => now()->getTimestampMs(),
                    'currency' => 'THB',
                    'cash' => $member->balance_free,
                    'bonus' => 0,
                    'usedPromo' => 0,
                    'error' => 0,
                    'description' => 'Success'
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRAGMATIC';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'refund';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['hash'];
                $session_in['con_2'] = $session['reference'];
                $session_in['con_3'] = $session['gameId'];
                $session_in['con_4'] = $session['roundId'];
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRAGMATIC';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['hash'];
            $session_in['con_2'] = $session['reference'];
            $session_in['con_3'] = $session['gameId'];
            $session_in['con_4'] = $session['roundId'];
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- REFUND --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function bonusWin(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {
//
//            $oldbalance = $member->balance_free;
//
//            $data = GameLogProxy::where('company', 'PRAGMATIC')
//                ->where('response', 'in')
//                ->where('game_user', $member->user_name)
//                ->where('method', 'refund')
//                ->where('con_1', $session['hash'])
//                ->where('con_2', $session['reference'])
//                ->whereNull('con_3')
//                ->whereNull('con_4')
//                ->first();
//
//            if ($data) {
//
//            } else {
//
//                $member->balance_free += $session['amount'];
//                $member->save();
//
            $param = [
                'transactionId' => now()->getTimestampMs(),
                'currency' => 'THB',
                'cash' => $member->balance_free,
                'bonus' => 0,
                'usedPromo' => 0,
                'error' => 0,
                'description' => 'Success'
            ];
//
//                $session_in['input'] = $session;
//                $session_in['output'] = $param;
//                $session_in['company'] = 'PRAGMATIC';
//                $session_in['game_user'] = $member->user_name;
//                $session_in['method'] = 'refund';
//                $session_in['response'] = 'in';
//                $session_in['amount'] = $session['amount'];
//                $session_in['con_1'] = $session['hash'];
//                $session_in['con_2'] = $session['reference'];
//                $session_in['con_3'] = $session['gameId'];
//                $session_in['con_4'] = $session['roundId'];
//                $session_in['before_balance'] = $oldbalance;
//                $session_in['after_balance'] = $member->balance_free;
//                $session_in['date_create'] = now()->toDateTimeString();
//                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                GameLogProxy::create($session_in);
//
//            }
//
//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'PRAGMATIC';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'payout';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['hash'];
//            $session_in['con_2'] = $session['reference'];
//            $session_in['con_3'] = $session['gameId'];
//            $session_in['con_4'] = $session['roundId'];
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);
//
        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- BONUSWIN --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function jackpotWin(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {
//
//            $oldbalance = $member->balance_free;
//
//            $data = GameLogProxy::where('company', 'PRAGMATIC')
//                ->where('response', 'in')
//                ->where('game_user', $member->user_name)
//                ->where('method', 'refund')
//                ->where('con_1', $session['hash'])
//                ->where('con_2', $session['reference'])
//                ->whereNull('con_3')
//                ->whereNull('con_4')
//                ->first();
//
//            if ($data) {
//
//            } else {
//
//                $member->balance_free += $session['amount'];
//                $member->save();
//
            $param = [
                'transactionId' => now()->getTimestampMs(),
                'currency' => 'THB',
                'cash' => $member->balance_free,
                'bonus' => 0,
                'usedPromo' => 0,
                'error' => 0,
                'description' => 'Success'
            ];
//
//                $session_in['input'] = $session;
//                $session_in['output'] = $param;
//                $session_in['company'] = 'PRAGMATIC';
//                $session_in['game_user'] = $member->user_name;
//                $session_in['method'] = 'refund';
//                $session_in['response'] = 'in';
//                $session_in['amount'] = $session['amount'];
//                $session_in['con_1'] = $session['hash'];
//                $session_in['con_2'] = $session['reference'];
//                $session_in['con_3'] = $session['gameId'];
//                $session_in['con_4'] = $session['roundId'];
//                $session_in['before_balance'] = $oldbalance;
//                $session_in['after_balance'] = $member->balance_free;
//                $session_in['date_create'] = now()->toDateTimeString();
//                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                GameLogProxy::create($session_in);
//
//            }
//
//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'PRAGMATIC';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'payout';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['hash'];
//            $session_in['con_2'] = $session['reference'];
//            $session_in['con_3'] = $session['gameId'];
//            $session_in['con_4'] = $session['roundId'];
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);
//
        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- JACKPOTWIN --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }

    public function promoWin(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['userId'], 'enable' => 'Y']);

        if ($member) {
//
//            $oldbalance = $member->balance_free;
//
//            $data = GameLogProxy::where('company', 'PRAGMATIC')
//                ->where('response', 'in')
//                ->where('game_user', $member->user_name)
//                ->where('method', 'refund')
//                ->where('con_1', $session['hash'])
//                ->where('con_2', $session['reference'])
//                ->whereNull('con_3')
//                ->whereNull('con_4')
//                ->first();
//
//            if ($data) {
//
//            } else {
//
//                $member->balance_free += $session['amount'];
//                $member->save();
//
            $param = [
                'transactionId' => now()->getTimestampMs(),
                'currency' => 'THB',
                'cash' => $member->balance_free,
                'bonus' => 0,
                'usedPromo' => 0,
                'error' => 0,
                'description' => 'Success'
            ];
//
//                $session_in['input'] = $session;
//                $session_in['output'] = $param;
//                $session_in['company'] = 'PRAGMATIC';
//                $session_in['game_user'] = $member->user_name;
//                $session_in['method'] = 'refund';
//                $session_in['response'] = 'in';
//                $session_in['amount'] = $session['amount'];
//                $session_in['con_1'] = $session['hash'];
//                $session_in['con_2'] = $session['reference'];
//                $session_in['con_3'] = $session['gameId'];
//                $session_in['con_4'] = $session['roundId'];
//                $session_in['before_balance'] = $oldbalance;
//                $session_in['after_balance'] = $member->balance_free;
//                $session_in['date_create'] = now()->toDateTimeString();
//                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//                GameLogProxy::create($session_in);
//
//            }
//
//            $session_in['input'] = $session;
//            $session_in['output'] = $param;
//            $session_in['company'] = 'PRAGMATIC';
//            $session_in['game_user'] = $member->user_name;
//            $session_in['method'] = 'payout';
//            $session_in['response'] = 'out';
//            $session_in['amount'] = $session['amount'];
//            $session_in['con_1'] = $session['hash'];
//            $session_in['con_2'] = $session['reference'];
//            $session_in['con_3'] = $session['gameId'];
//            $session_in['con_4'] = $session['roundId'];
//            $session_in['before_balance'] = $oldbalance;
//            $session_in['after_balance'] = $member->balance_free;
//            $session_in['date_create'] = now()->toDateTimeString();
//            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
//            GameLogProxy::create($session_in);
//
        } else {

            $param = [
                "error" => 2,
                "description" => "Player not found or is logged out"
            ];

        }

        $path = storage_path('logs/seamless/PRAGMATIC' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- PROMOWIN --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);


        return $param;
    }


}
