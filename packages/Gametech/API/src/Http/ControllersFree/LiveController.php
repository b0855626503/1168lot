<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class LiveController extends AppBaseController
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


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['PlayerId'], 'enable' => 'Y']);
        if ($member) {


            $param = [
                'Status' => 200,
                'Description' => "OK",
                'ResponseDateTime' => now()->toDateTimeString(),
                'Balance' => (float)$member->balance_free
            ];

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'LIVE22';
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
                'Status' => 900404,
                'Description' => "Invalid player/password",
                'ResponseDateTime' => now()->toDateTimeString(),
                'OldBalance' => 0,
                'NewBalance' => 0
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['PlayerId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'LIVE22')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['BetId'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 900409,
                    'Description' => "Duplicate Transaction",
                    'ResponseDateTime' => now()->toDateTimeString(),
                    'OldBalance' => (float)$oldbalance,
                    'NewBalance' => (float)$member->balance_free
                ];

            } else {

                $balance = ($oldbalance - $session['BetAmount']);
                if ($balance >= 0) {

                    MemberProxy::where('user_name', $session['PlayerId'])->decrement('balance_free', $session['BetAmount']);
                    $member = MemberProxy::where('user_name', $session['PlayerId'])->first();


//                    $member->balance_free -= $session['BetAmount'];
//                    $member->save();

                    $param = [
                        'Status' => 200,
                        'Description' => "OK",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free,
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'LIVE22';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['BetAmount'];
                    $session_in['con_1'] = $session['BetId'];
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
                        'Status' => 900605,
                        'Description' => "Insufficient Balance",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free
                    ];
                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'LIVE22';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['BetAmount'];
            $session_in['con_1'] = $session['BetId'];
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
                'Status' => 900404,
                'Description' => "Invalid player/password",
                'ResponseDateTime' => now()->toDateTimeString(),
                'OldBalance' => 0,
                'NewBalance' => 0

            ];
        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['PlayerId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'LIVE22')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['BetId'])
                ->where('con_2', $session['ResultId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 900409,
                    'Description' => "Duplicate Transaction",
                    'ResponseDateTime' => now()->toDateTimeString(),
                    'OldBalance' => (float)$oldbalance,
                    'NewBalance' => (float)$member->balance_free
                ];

            } else {


                $datasub = GameLogProxy::where('company', 'LIVE22')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['BetId'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {


                    MemberProxy::where('user_name', $session['PlayerId'])->increment('balance_free', $session['Payout']);
                    $member = MemberProxy::where('user_name', $session['PlayerId'])->first();


//                $member->balance_free += $session['Payout'];
//                $member->save();

                    $param = [
                        'Status' => 200,
                        'Description' => "OK",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free,
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'LIVE22';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'payout';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['Payout'];
                    $session_in['con_1'] = $session['BetId'];
                    $session_in['con_2'] = $session['ResultId'];
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $param = [
                        'Status' => 900415,
                        'Description' => "Bet Transaction Not Found",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free
                    ];

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'LIVE22';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['Payout'];
            $session_in['con_1'] = $session['BetId'];
            $session_in['con_2'] = $session['ResultId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);


        } else {
            $param = [
                'Status' => 900404,
                'Description' => "Invalid player/password",
                'ResponseDateTime' => now()->toDateTimeString(),
                'OldBalance' => 0,
                'NewBalance' => 0
            ];
        }


        return $param;
    }

    public function rollBack(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['PlayerId'], 'enable' => 'Y']);

        if ($member) {

            $data = GameLogProxy::where('company', 'LIVE22')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['BetId'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            $oldbalance = $member->balance_free;

            if ($data) {

                $param = [
                    'Status' => 900409,
                    'Description' => "Duplicate Transaction",
                    'ResponseDateTime' => now()->toDateTimeString(),
                    'OldBalance' => (float)$oldbalance,
                    'NewBalance' => (float)$member->balance_free
                ];

            } else {

                $datasub = GameLogProxy::where('company', 'LIVE22')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'bet')
                    ->where('con_1', $session['BetId'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $balance = ($oldbalance + $session['BetAmount']);

                    MemberProxy::where('user_name', $session['PlayerId'])->increment('balance_free', $session['BetAmount']);
                    $member = MemberProxy::where('user_name', $session['PlayerId'])->first();


//                $member->balance_free += $session['BetAmount'];
//                $member->save();

                    $param = [
                        'Status' => 200,
                        'Description' => "OK",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free,
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'LIVE22';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'cancel';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['BetAmount'];
                    $session_in['con_1'] = $session['BetId'];
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
                        'Status' => 900415,
                        'Description' => "Bet Transaction Not Found",
                        'ResponseDateTime' => now()->toDateTimeString(),
                        'OldBalance' => (float)$oldbalance,
                        'NewBalance' => (float)$member->balance_free,
                    ];

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'LIVE22';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $session['BetAmount'];
            $session_in['con_1'] = $session['BetId'];
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
                'Status' => 900404,
                'Description' => "Invalid player/password",
                'ResponseDateTime' => now()->toDateTimeString(),
                'OldBalance' => 0,
                'NewBalance' => 0
            ];
        }


        return $param;
    }

}
