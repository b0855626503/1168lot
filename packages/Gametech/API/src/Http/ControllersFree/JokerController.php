<?php

namespace Gametech\API\Http\ControllersFree;

use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class JokerController extends AppBaseController
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
                'Status' => 0,
                'Message' => "Success",
                'Username' => $member->user_name,
                'Balance' => (float)$member->balance_free
            ];

        } else {
            $param = [
                'Status' => 3,
                'Message' => "Invalid Token",
                'Username' => 'foobar',
                'Balance' => 0
            ];
        }

        return $param;
    }


    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'Status' => 0,
                'Message' => "Success",
                'Username' => $member->user_name,
                'Balance' => (float)$member->balance_free
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JOKER';
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
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }

        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['roundid'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'JOKER')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'cancel')
//                    ->where('con_1', $session['id'])
                    ->where('con_2', $session['roundid'])
//                    ->where('con_3', $session['betid'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'Status' => 0,
                        'Message' => "Success",
                        'Username' => $member->user_name,
                        'Balance' => (float)$member->balance_free
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'JOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['id'];
                    $session_in['con_2'] = $session['roundid'];
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
                        MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $session['amount']);
                        $member = MemberProxy::where('user_name', $session['username'])->first();


                        $param = [
                            'Status' => 0,
                            'Message' => "Success",
                            'Username' => $member->user_name,
                            'Balance' => (float)$member->balance_free
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'JOKER';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['amount'];
                        $session_in['con_1'] = $session['id'];
                        $session_in['con_2'] = $session['roundid'];
                        $session_in['con_3'] = null;
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    } else {

                        $param = [
                            'Error' => "100",
                            'Description' => "Insufficient fund"
                        ];
                    }
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['roundid'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['roundid'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();

//                $member->balance_free += $session['amount'];
//                $member->save();

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['roundid'];
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
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['roundid'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

    public function bonusWin(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bonuswin')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['roundid'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();

//                $member->balance_free += $session['amount'];
//                $member->save();

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bonuswin';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['roundid'];
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
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bonuswin';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['roundid'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

    public function jackpotWin(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'jackpotwin')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['roundid'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();


            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                $balance = ($oldbalance + $session['amount']);

//                $member->balance_free += $session['amount'];
//                $member->save();

                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();


                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'jackpotwin';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['roundid'];
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
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'jackpotwin';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['roundid'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

    public function transaction(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {


            $param = [
                'Status' => 0,
                'Message' => "Success",
                'Username' => $member->user_name,
                'Balance' => (float)$member->balance_free
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'transaction';
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
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];

        }


        return $param;
    }

    public function withdraw(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'withdraw')
                ->where('con_1', $session['id'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                $balance = ($oldbalance - $session['amount']);

                if ($balance >= 0) {

                    MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $session['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

//                    $member->balance_free -= $session['amount'];
//                    $member->save();


                    $param = [
                        'Status' => 0,
                        'Message' => "Success",
                        'Username' => $member->user_name,
                        'Balance' => (float)$member->balance_free
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'JOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'withdraw';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['amount'];
                    $session_in['con_1'] = $session['id'];
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
                        'Error' => '100',
                        'Description' => "Insufficient fund",
                        'Username' => $member->user_name,
                        'Balance' => (float)$member->balance_free
                    ];
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'withdraw';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
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
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

    public function deposit(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'deposit')
                ->where('con_1', $session['id'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                $balance = ($oldbalance + $session['amount']);

                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();


//                $member->balance_free += $session['amount'];
//                $member->save();

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'JOKER';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'deposit';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['amount'];
                $session_in['con_1'] = $session['id'];
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
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'deposit';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['amount'];
            $session_in['con_1'] = $session['id'];
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
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }


    public function cancelBet(Request $request)
    {
        $session = $request->all();
        $amount = 0;

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'JOKER')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['roundid'])
                ->where('con_3', $session['betid'])
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 0,
                    'Message' => "Success",
                    'Username' => $member->user_name,
                    'Balance' => (float)$member->balance_free
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'JOKER')
                    ->where('response', 'in')
                    ->where('method', 'bet')
                    ->where('game_user', $member->user_name)
//                    ->where('con_1', $session['betid'])
                    ->where('con_2', $session['roundid'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {
                    $amount = $datasub['input']['amount'];
//                    $member->balance_free += $datasub['input']['amount'];
//                    $member->save();

                    MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasub['input']['amount']);
                    $member = MemberProxy::where('user_name', $session['username'])->first();


                    $param = [
                        'Status' => 0,
                        'Message' => "Success",
                        'Username' => $member->user_name,
                        'Balance' => (float)$member->balance_free
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'JOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amount;
                    $session_in['con_1'] = $session['id'];
                    $session_in['con_2'] = $session['roundid'];
                    $session_in['con_3'] = $session['betid'];
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $param = [
                        'Status' => 0,
                        'Message' => "Success",
                        'Username' => $member->user_name,
                        'Balance' => (float)$member->balance_free
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'JOKER';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $amount;
                    $session_in['con_1'] = $session['id'];
                    $session_in['con_2'] = $session['roundid'];
                    $session_in['con_3'] = $session['betid'];
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
            $session_in['company'] = 'JOKER';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['roundid'];
            $session_in['con_3'] = $session['betid'];
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'Status' => 7,
                'Message' => "Invalid username or password",
                'Username' => $session['username'],
                'Balance' => 0
            ];
        }


        return $param;
    }

}
