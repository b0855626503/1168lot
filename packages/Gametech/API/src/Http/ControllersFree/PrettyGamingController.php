<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class PrettyGamingController extends AppBaseController
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

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionToken'], 'user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'accessToken' => $session['accessToken'],
                'username' => $member->user_name,
                'sessionToken' => $session['sessionToken'],
                'currency' => 'THB',
                'status' => 200,
                'event' => 'registerOrLogin',
                'seqNo' => $session['seqNo'],
                'nickname' => $member->user_name
            ];
        } else {
            $param = [
                'accessToken' => $session['accessToken'],
                'username' => $session['username'],
                'sessionToken' => $session['sessionToken'],
                'currency' => 'THB',
                'status' => 4037,
                'event' => 'registerOrLogin',
                'seqNo' => $session['seqNo'],
                'nickname' => $session['username']
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
                'id' => $session['id'],
                'statusCode' => 0,
                'currency' => "THB",
                'productId' => $session['productId'],
                'username' => $member->user_name,
                'balance' => (float)$member->balance_free,
                'timestampMillis' => now()->getTimestampMs()
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
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
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRETTY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRETTY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {
//                    $amount += $item['betAmount'];

                    $datasub = GameLogProxy::where('company', 'PRETTY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'betsub')
                        ->where('con_1', $item['txnId'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20002,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$member->balance_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    } else {

                        $datasubs = GameLogProxy::where('company', 'PRETTY')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'refundsub')
                            ->where('con_1', $item['txnId'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['id'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                        } else {


                            $balance = ($member->balance_free - $item['betAmount']);
                            if ($balance >= 0) {

//                                $member = MemberProxy::where('user_name', $session['username'])->first();
                                MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                                $member = MemberProxy::where('user_name', $session['username'])->first();
//                                $newbalance = $members->getChanges('balance');

//                                $member->balance_free -= $item['betAmount'];
//                                $member->save();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];

                            } else {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 10002,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$member->balance_free,
                                    'productId' => $session['productId']
                                ];
                                break;

                            }
                        }

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'PRETTY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'betsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['txnId'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['id'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);


                    }


                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'bet';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRETTY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'payout')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }


                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRETTY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'payout';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
//                    $amount += $item['payoutAmount'];

                    $datasub = GameLogProxy::where('company', 'PRETTY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'betsub')
                        ->where('con_1', $item['txnId'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {


                        $datasubs = GameLogProxy::where('company', 'PRETTY')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'paysub')
                            ->where('con_1', $item['txnId'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['id'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        } else {

                            $datasubss = GameLogProxy::where('company', 'PRETTY')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'refundsub')
                                ->where('con_1', $item['txnId'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['id'])
                                ->whereNull('con_4')
                                ->first();

                            if ($datasubss) {

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 20003,
                                    'timestampMillis' => now()->getTimestampMs(),
                                    'balance' => (float)$member->balance_free,
                                    'productId' => $session['productId']
                                ];
                                break;

                            } else {


//                                $member = MemberProxy::where('user_name', $session['username'])->first();
                                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['payoutAmount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                                $member = MemberProxy::where('user_name', $session['username'])->first();

//                                $member = MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['payoutAmount']);
//                                $oldbalance = $member->getOriginal('balance');
//                                $newbalance = $member->getChanges('balance');
//                                $member->balance_free += $item['payoutAmount'];
//                                $member->save();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];


                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'PRETTY';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'paysub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['payoutAmount'];
                                $session_in['con_1'] = $item['txnId'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['id'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance_free;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);
                            }
                        }

                    } else {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 20001,
                            'timestampMillis' => now()->getTimestampMs(),
                            'balance' => (float)$member->balance_free,
                            'productId' => $session['productId']
                        ];
                        break;

                    }

                }

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'payout';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRETTY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRETTY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'PRETTY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'refundsub')
                        ->where('con_1', $item['txnId'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {

                        $datasubs = GameLogProxy::where('company', 'PRETTY')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'paysub')
                            ->where('con_1', $item['txnId'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['id'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

//                            $param = [
//                                'id' => $session['id'],
//                                'statusCode' => 0,
//                                'currency' => "THB",
//                                'productId' => $session['productId'],
//                                'username' => $member->user_name,
//                                'balanceBefore' => (float)$oldbalance,
//                                'balanceAfter' => (float)$member->balance_free,
//                                'timestampMillis' => now()->getTimestampMs()
//                            ];

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20004,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$member->balance_free,
                                'productId' => $session['productId']
                            ];

                        } else {

//                            $subamount = 0;

                            $datasubss = GameLogProxy::where('company', 'PRETTY')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'betsub')
                                ->where('con_1', $item['txnId'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['id'])
                                ->whereNull('con_4')
                                ->first();

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'PRETTY';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'refundsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['txnId'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['id'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                            if ($datasubss) {

//                                $member = MemberProxy::where('user_name', $session['username'])->first();
                                MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasubss['amount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                                $member = MemberProxy::where('user_name', $session['username'])->first();

//                                $member = MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasubss['amount']);
//                                $oldbalance = $member->getOriginal('balance');
//                                $newbalance = $member->getChanges('balance');

//                                $member->balance_free += $datasubss['amount'];
//                                $member->save();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];


                            } else {

//                                $param = [
//                                    'id' => $session['id'],
//                                    'statusCode' => 20001,
//                                    'timestampMillis' => now()->getTimestampMs(),
//                                    'productId' => $session['productId']
//                                ];

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => "THB",
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float)$oldbalance,
                                    'balanceAfter' => (float)$member->balance_free,
                                    'timestampMillis' => now()->getTimestampMs()
                                ];
                                break;

                            }

                        }


                    }


                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'cancel';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function placeTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRETTY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'tips')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRETTY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'tips';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'PRETTY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'tipsub')
                        ->where('con_1', $item['txnId'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {

                        $balance = ($member->balance_free - $item['betAmount']);
                        if ($balance >= 0) {

                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
//                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['betAmount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                            $member = MemberProxy::where('user_name', $session['username'])->first();


//                            $member->balance_free -= $item['betAmount'];
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];


                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$member->balance_free,
                                'productId' => $session['productId']
                            ];

                            break;

                        }

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'PRETTY';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'tipsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['betAmount'];
                        $session_in['con_1'] = $item['txnId'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['id'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance_free;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                }


            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'tips';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

    public function cancelTips(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'PRETTY')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'canceltip')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20002,
                    'timestampMillis' => now()->getTimestampMs(),
                    'balance' => (float)$member->balance_free,
                    'productId' => $session['productId']
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'PRETTY';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'canceltip';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'PRETTY')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'ctsub')
                        ->where('con_1', $item['txnId'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['id'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => "THB",
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float)$oldbalance,
                            'balanceAfter' => (float)$member->balance_free,
                            'timestampMillis' => now()->getTimestampMs()
                        ];

                    } else {

                        $datasubs = GameLogProxy::where('company', 'PRETTY')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'tipsub')
                            ->where('con_1', $item['txnId'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['id'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

//                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $datasubs['amount']);
//                                MemberProxy::where('user_name',$session['username'])->update(['balance' => DB::raw('balance - '.$item['betAmount'])]);;
                            $member = MemberProxy::where('user_name', $session['username'])->first();

//                            $member->balance_free += $datasubs['amount'];
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => "THB",
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'timestampMillis' => now()->getTimestampMs()
                            ];

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'PRETTY';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'ctsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $datasubs['amount'];
                            $session_in['con_1'] = $item['txnId'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['id'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$member->balance_free,
                                'productId' => $session['productId']
                            ];
                            break;

                        }
                    }
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PRETTY';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'canceltip';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance_free;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId']
            ];

        }


        return $param;
    }

}
