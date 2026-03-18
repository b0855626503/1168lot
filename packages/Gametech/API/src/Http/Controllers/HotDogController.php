<?php

namespace Gametech\API\Http\Controllers;

use Gametech\API\Models\GameLogProxy;
use Gametech\API\Traits\LogSeamless;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class HotDogController extends AppBaseController
{
    use LogSeamless;

    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    protected $request;

    protected $member;

    //    protected $balance;
    protected $balances;

    protected $game;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo,
        Request $request
    ) {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;

        $this->request = $request;

        if (isset($this->request['session_id'])) {
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['player_id'])->where('session_id', $this->request['session_id'])->where('enable', 'Y')->first();

        } else {
            //            $this->member = $this->memberRepository->findOneWhere(['user_name' => $this->request['username'], 'enable' => 'Y']);
            $this->member = MemberProxy::without('bank')->where('user_name', $this->request['player_id'])->where('enable', 'Y')->first();
        }

        //        $this->member->balance = $this->member->balance;

        $this->balances = 'balance';

        $this->game = 'HOTDOG';
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

        //                $path = storage_path('logs/seamless/nextspin' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
        //        file_put_contents($path, print_r($this->member, true), FILE_APPEND);
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);

        if ($this->member) {

            $param = [
                'status' => true,
                'balance' => (float) $this->member->balance,
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = $this->game;
            $session_in['game_user'] = $this->member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = 0;
            $session_in['con_1'] = null;
            $session_in['con_2'] = null;
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $this->member->balance;
            $session_in['after_balance'] = $this->member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {
            $param = [
                'status' => false,
                'balance' => 0,
            ];
        }

        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['transaction_id'])
                ->where('con_2', $session['round_id'])
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => false,
                    'balance' => (float) $this->member->balance,
                ];

            } else {

                $balance = ($this->member->balance - $session['amount']);
                if ($balance < 0) {

                    $param = [
                        'status' => false,
                        'balance' => 0,
                    ];

                    return $param;

                }

                $this->member->decrement($this->balances, $session['amount']);

                $param = [
                    'status' => true,
                    'balance' => (float) $this->member->balance,
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['round_id'];
                $session_in['con_3'] = $session['parent_id'] ?? null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                $newparam = [
                    'playInfo' => $session['game_id'],
                    'betAmount' => $session['amount'],
                    'status' => 'OPEN',
                    'id' => $session['transaction_id'],
                    'roundId' => $session['round_id'],
                ];
                LogSeamless::log($this->game, $this->member->user_name, $newparam, $oldbalance, $this->member->balance);
            }

        } else {

            $param = [
                'status' => false,
                'balance' => 0,
            ];

        }

        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['transaction_id'])
                ->where('con_2', $session['round_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => false,
                    'balance' => (float) $this->member->balance,
                ];

            } else {

                $this->member->increment($this->balances, $session['payout']);

                $param = [
                    'status' => true,
                    'balance' => (float) $this->member->balance,
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['payout'];
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['round_id'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                $newparam = [
                    'playInfo' => $session['game_id'],
                    'payoutAmount' => $session['payout'],
                    'status' => 'SETTLED',
                    'id' => $session['transaction_id'],
                    'roundId' => $session['round_id'],
                    'transactionType' => 'BY_ROUND',
                    'isSingleState' => false,
                ];
                LogSeamless::log($this->game, $this->member->user_name, $newparam, $oldbalance, $this->member->balance);

            }

        } else {

            $param = [
                'status' => false,
                'balance' => 0,
            ];

        }

        return $param;
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance;

            $checkDup = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'refund')
                ->where('con_1', $session['transaction_id'])
                ->where('con_2', $session['round_id'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($checkDup) {

                $param = [
                    'status' => false,
                    'balance' => (float) $this->member->balance,
                ];

            } else {

                $data = GameLogProxy::where('company', $this->game)
                    ->where('response', 'in')
                    ->where('game_user', $this->member->user_name)
                    ->whereIn('method', ['bet', 'payout'])
                    ->where('con_1', $session['transaction_id'])
                    ->where('con_2', $session['round_id'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if (! $data) {

                    $param = [
                        'status' => true,
                        'balance' => (float) $this->member->balance,
                    ];

                    return $param;
                }

                if ($data['method'] === 'bet') {
                    $this->member->increment($this->balances, $session['amount']);
                }

                if ($data['method'] === 'payout') {
                    $balance = ($this->member->balance - $data['amount']);
                    if ($balance < 0) {

                        $param = [
                            'status' => false,
                            'balance' => 0,
                        ];

                        return $param;

                    }

                    $this->member->decrement($this->balances, $data['amount']);

                }

                $param = [
                    'status' => true,
                    'balance' => (float) $this->member->balance,
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'refund';
                $session_in['response'] = 'in';
                $session_in['amount'] = $data['amount'];
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['round_id'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                if ($data['method'] == 'bet') {
                    $newparam = [
                        'playInfo' => $session['game_id'],
                        'payoutAmount' => $data['amount'],
                        'status' => 'REFUND',
                        'id' => $session['transaction_id'],
                        'roundId' => $session['round_id'],
                        'transactionType' => 'BY_ROUND',
                        'isSingleState' => false,
                    ];
                } else {
                    $newparam = [
                        'playInfo' => $session['game_id'],
                        'betAmount' => $data['amount'],
                        'status' => 'REFUND',
                        'id' => $session['transaction_id'],
                        'roundId' => $session['round_id'],
                        'transactionType' => 'BY_ROUND',
                        'isSingleState' => false,
                    ];

                }

                LogSeamless::log($this->game, $this->member->user_name, $newparam, $oldbalance, $this->member->balance);

            }

        } else {

            $param = [
                'status' => false,
                'balance' => 0,
            ];

        }

        return $param;
    }

    public function winReward(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $oldbalance = $this->member->balance;

            $data = GameLogProxy::where('company', $this->game)
                ->where('response', 'in')
                ->where('game_user', $this->member->user_name)
                ->where('method', 'reward')
                ->where('con_1', $session['transaction_id'])
//                ->where('con_2', $session['round_id'])
//                ->whereNull('con_3')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'status' => false,
                    'balance' => (float) $this->member->balance,
                ];

            } else {

                $this->member->increment($this->balances, $session['payout']);

                $param = [
                    'status' => true,
                    'balance' => (float) $this->member->balance,
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = $this->game;
                $session_in['game_user'] = $this->member->user_name;
                $session_in['method'] = 'reward';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['payout'];
                $session_in['con_1'] = $session['transaction_id'];
                $session_in['con_2'] = $session['bonus_id'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $this->member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                $newparam = [
                    'playInfo' => $session['game_id'],
                    'payoutAmount' => $session['payout'],
                    'status' => 'WINREWARD',
                    'id' => $session['transaction_id'],
                    'roundId' => $session['round_id'],
                    'transactionType' => 'BY_ROUND',
                    'isSingleState' => false,
                ];
                LogSeamless::log($this->game, $this->member->user_name, $newparam, $oldbalance, $this->member->balance);

            }

        } else {

            $param = [
                'status' => false,
                'balance' => 0,
            ];

        }

        return $param;
    }

    public function kickOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        if ($this->member) {

            $param = [
                'status' => true,
            ];

        } else {

            $param = [
                'status' => false,
            ];

        }

        return $param;
    }
}
