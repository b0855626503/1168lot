<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class AskmebetController extends AppBaseController
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

    public function getBalance(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'user_name' => $session['account'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'status' => 1,
                'balance' => (float)$member->balance_free
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ASKMEBET';
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
                'status' => 3,
                'balance' => 0
            ];

        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'user_name' => $session['account'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'ASKMEBET')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['trans_id'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => 4,
                    'balance' => (float)$member->balance_free,
                    'trans_id' => $session['trans_id']
                ];

            } else {

                $balance = ($oldbalance - $session['amount']);
                if ($balance >= 0) {

                    MemberProxy::where('user_name', $session['account'])->decrement('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['account'])->first();

//                    $member->balance_free -= $session['amount'];
//                    $member->save();

                    $param = [
                        'status' => 1,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'ASKMEBET';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['trans_id'];
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
                        'status' => 5,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ASKMEBET';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['trans_id'];
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
                'status' => 3,
                'balance' => 0,
                'trans_id' => $session['trans_id']
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['session_id' => $session['token'], 'user_name' => $session['account'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'ASKMEBET')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['trans_id'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'status' => 4,
                    'balance' => (float)$member->balance_free,
                    'trans_id' => $session['trans_id']
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'ASKMEBET')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['trans_id'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $balance = ($oldbalance + $session['amount']);

                    MemberProxy::where('user_name', $session['account'])->increment('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['account'])->first();

//                    $member->balance_free += $session['amount'];
//                    $member->save();

                    $param = [
                        'status' => 1,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'ASKMEBET';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['trans_id'];
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
                        'status' => 6,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ASKMEBET';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['trans_id'];
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
                'status' => 3,
                'balance' => 0,
                'trans_id' => $session['trans_id']
            ];
        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['account'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'ASKMEBET')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['trans_id'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;
            $amount = 0;

            if ($data) {

                $param = [
                    'status' => 4,
                    'balance' => (float)$member->balance_free,
                    'trans_id' => $session['trans_id']
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'ASKMEBET')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['trans_id'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $amount = $datasub['input']['amount'];

                    MemberProxy::where('user_name', $session['account'])->increment('balance_free', $amount);
                    $member = MemberProxy::where('user_name', $session['account'])->first();

//                    $member->balance_free += $amount;
//                    $member->save();

                    $param = [
                        'status' => 1,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'ASKMEBET';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amount;
                    $session_in['con_1'] = $session['trans_id'];
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
                        'status' => 6,
                        'balance' => (float)$member->balance_free,
                        'trans_id' => $session['trans_id']
                    ];

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ASKMEBET';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['trans_id'];
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
                'status' => 3,
                'balance' => 0,
                'trans_id' => $session['trans_id']
            ];
        }


        return $param;
    }


}
