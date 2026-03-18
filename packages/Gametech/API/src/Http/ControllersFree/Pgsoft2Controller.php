<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class Pgsoft2Controller extends AppBaseController
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
//        $mtime = microtime();
//        $mtime = explode(" ",$mtime);
//        $mtime = $mtime[1] + $mtime[0];
//        $starttime = $mtime;
        $session = $request->all();
//        $path = storage_path('logs/seamless/pgsoft2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
//        file_put_contents($path, print_r(now()->toDateTimeString(), true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['session_id' => $session['sessionToken'], 'user_name' => $session['username'], 'enable' => 'Y']);
        if ($member) {

            $param = [
                'id' => $session['id'],
                'statusCode' => 0,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId'],
                'currency' => 'THB',
                'balance' => (float)$member->balance_free,
                'username' => $member->user_name,
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'PGSOFT2';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'getbalance';
            $session_in['response'] = 'in';
            $session_in['amount'] = (float)$member->balance_free;
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
                'statusCode' => 30001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }

//        $mtime = microtime();
//        $mtime = explode(" ",$mtime);
//        $mtime = $mtime[1] + $mtime[0];
//        $endtime = $mtime;
//        $totaltime = ($endtime - $starttime);
//        $path = storage_path('logs/seamless/pgsoft2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- GET BALANCE end --', true), FILE_APPEND);
//        file_put_contents($path, print_r(now()->toDateTimeString(), true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);
        return $param;
    }

    public function transferOut(Request $request)
    {

        $session = $request->all();
//        $path = storage_path('logs/seamless/pgsoft2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- settle start --', true), FILE_APPEND);
////        file_put_contents($path, print_r('', true), FILE_APPEND);
//        file_put_contents($path, print_r(now()->toDateTimeString(), true), FILE_APPEND);
//        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;


            foreach ($session['txns'] as $item) {

                $datasub = GameLogProxy::where('company', 'PGSOFT2')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'betsub')
                    ->where('con_1', $item['id'])
                    ->where('con_2', $item['roundId'])
                    ->where('con_3', $item['gameCode'])
                    ->whereNull('con_4')
                    ->first();

                if ($datasub) {

                    $param = [
                        'id' => $session['id'],
                        'statusCode' => 0,
                        'timestampMillis' => now()->getTimestampMs(),
                        'productId' => $session['productId'],
                        'currency' => 'THB',
                        'balanceBefore' => (float)$member->balance_free,
                        'balanceAfter' => (float)$member->balance_free,
                        'username' => $session['username'],
                    ];

                } else {

                    if (!isset($item['skipBalanceUpdate'])) {


                        $balance = ($member->balance_free - $item['betAmount']);
                        if ($balance >= 0) {
                            MemberProxy::where('user_name', $session['username'])->decrement('balance_free', $item['betAmount']);
                            MemberProxy::where('user_name', $session['username'])->increment('balance_free', $item['payoutAmount']);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

                            $sumbalance = $item['payoutAmount'] - $item['betAmount'];
//                            $member->balance_free -= $item['betAmount'];
//                            $member->balance_free += $item['payoutAmount'];
//                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId'],
                                'currency' => 'THB',
                                'balanceBefore' => (float)$oldbalance,
                                'balanceAfter' => (float)$member->balance_free,
                                'username' => $session['username'],
                            ];

                            $session_in['input'] = $session;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'PGSOFT2';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'betsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $sumbalance;
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['gameCode'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance_free;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'timestampMillis' => now()->getTimestampMs(),
                                'balance' => (float)$member->balance_free,
                                'productId' => $session['productId'],
                            ];

                        }

                    }

                }
            }

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 30001,
                'timestampMillis' => now()->getTimestampMs(),
                'balance' => 0,
                'productId' => $session['productId'],
            ];

        }


//        $path = storage_path('logs/seamless/pgsoft2' . now()->format('Y_m_d') . '.log');
//        file_put_contents($path, print_r('-- settle end --', true), FILE_APPEND);
////        file_put_contents($path, print_r('', true), FILE_APPEND);
//        file_put_contents($path, print_r(now()->toDateTimeString(), true), FILE_APPEND);
//        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }


}
