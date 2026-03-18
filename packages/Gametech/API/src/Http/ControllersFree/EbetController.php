<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;

class EbetController extends AppBaseController
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

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

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
                'username' => $member->user_name,
                'money' => (float)$member->balance_free,
                'currency' => "THB",
                'status' => 200,
                'event' => "syncCredit",
                'seqNo' => $session['seqNo'],
                'timestamp' => now()->getTimestampMs()
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'EBET';
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
                'status' => 4037
            ];
        }


        return $param;
    }

    public function increaseCredit(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;


            if ($session['type'] != 38) {
                $data = GameLogProxy::where('company', 'EBET')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', $session['type'])
                    ->where('con_1', $session['seqNo'])
                    ->where('con_2', $session['detail']['roundCode'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();
            } else {
                $data = GameLogProxy::where('company', 'EBET')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', $session['type'])
                    ->where('con_1', $session['seqNo'])
                    ->whereNull('con_2')
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();
            }


            if ($data) {

            } else {

                foreach ($session['detail']['betList'] as $item) {

                }

                $balance = ($member->balance_free + $session['money']);
                if ($balance >= 0) {


                    $member->balance_free += $session['money'];
                    $member->save();

                    $param = [
                        'username' => $member->user_name,
                        'money' => (float)$member->balance_free,
                        'moneyBefore' => (float)$oldbalance,
                        'status' => 200,
                        'event' => "increaseCredit",
                        'seqNo' => $session['seqNo'],
                        'timestamp' => now()->getTimestampMs()
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'EBET';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = $session['type'];
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['money'];
                    $session_in['con_1'] = $session['seqNo'];
                    if ($session['type'] != 38) {
                        $session_in['con_2'] = $session['detail']['roundCode'];
                    } else {
                        $session_in['con_2'] = null;
                    }
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $member->balance_free;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);


                } else {

                }


            }

        } else {

            $param = [
                'status' => 4037
            ];

        }

        return $param;
    }

    public function queryIncreaseCreditRecord(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $id = Str::of($session['querySeqNo'])->explode(',');

//            if ($id->count() > 1) {


            $chk = $id->each(function ($isub, $key) use ($member, $session) {

                $data = GameLogProxy::where('company', 'EBET')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('con_1', $isub)
                    ->where('con_2', $session['roundCode'])
                    ->whereNull('con_3')
                    ->whereNull('con_4')
                    ->first();

                if ($data) {

                    return [
                        'querySeqNo' => $isub,
                        'type' => $data['method'],
                        'username' => $session['username'],
                        'roundCode' => $session['roundCode'],
                        'status' => 200,
                        'creditTime' => now()->getTimestampMs(),
                        'moneyBefore' => $data['before_balance'],
                        'moneyAfter' => $data['after_balance'],
                        'money' => $data['amount']
                    ];

                }
            });

            $param = [
                'username' => $member->user_name,
                'currency' => "THB",
                'status' => 200,
                'event' => "queryIncreaseCreditRecord",
                'seqNo' => $session['seqNo'],
                'timestamp' => now()->getTimestampMs(),
                'creditRecord' => $chk
            ];


        } else {

            $param = [
                'status' => 4037
            ];

        }

        return $param;
    }


}
