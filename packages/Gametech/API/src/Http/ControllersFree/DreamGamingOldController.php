<?php

namespace Gametech\API\Http\ControllersFree;


use Gametech\API\Models\GameLogFreeProxy as GameLogProxy;
use Gametech\Game\Repositories\GameUserFreeRepository as GameUserRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use MongoDB\BSON\UTCDateTime;

class DreamGamingOldController extends AppBaseController
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

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['member']['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'data' => [
                    'player_name' => $member->user_name,
                    'nickname' => $member->user_name,
                    'currency' => 'THB',
                    'reminder_time' => now()->timestamp
                ],
                'error' => null
            ];
        } else {
            $param = [
                'data' => null,
                'error' => [
                    'code' => 3004,
                    'message' => "Player isn't exist"
                ]
            ];
        }

        return $param;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['member']['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'codeId' => 0,
                'token' => $session['token'],
                'member' => [
                    'username' => $member->user_name,
                    'balance' => (float)$member->balance_free
                ]
            ];


            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'DREAM';
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
                'codeId' => 503,
                'token' => $session['token'],
            ];
        }


        return $param;
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['member']['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'DREAM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'bet')
                ->where('con_1', $session['data'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'codeId' => 0,
                    'token' => $session['token'],
                    'data' => $session['data'],
                    'member' => [
                        'username' => $member->user_name,
                        'amount' => $session['member']['amount'],
                        'balance' => $oldbalance
                    ]
                ];

            } else {

                if ($session['member']['amount'] >= 0) {

                    $member->balance_free += $session['member']['amount'];
                    $member->save();

                    $param = [
                        'codeId' => 0,
                        'token' => $session['token'],
                        'data' => $session['data'],
                        'member' => [
                            'username' => $member->user_name,
                            'amount' => $session['member']['amount'],
                            'balance' => $member->balance_free
                        ]
                    ];

                    $session_in['input'] = $session;
                    $session_in['output'] = $param;
                    $session_in['company'] = 'DREAM';
                    $session_in['game_user'] = $member->user_name;
                    $session_in['method'] = 'bet';
                    $session_in['response'] = 'in';
                    $session_in['amount'] = $session['member']['amount'];
                    $session_in['con_1'] = $session['data'];
                    $session_in['con_2'] = null;
                    $session_in['con_3'] = null;
                    $session_in['con_4'] = null;
                    $session_in['before_balance'] = $oldbalance;
                    $session_in['after_balance'] = $member->balance_free;
                    $session_in['date_create'] = now()->toDateTimeString();
                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                    GameLogProxy::create($session_in);

                } else {

                    $balance = ($member->balance_free - $session['member']['amount']);
                    if ($balance >= 0) {

                        $member->balance_free -= $session['member']['amount'];
                        $member->save();

                        $param = [
                            'codeId' => 0,
                            'token' => $session['token'],
                            'data' => $session['data'],
                            'member' => [
                                'username' => $member->user_name,
                                'amount' => $session['member']['amount'],
                                'balance' => $balance
                            ]
                        ];

                        $session_in['input'] = $session;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'DREAM';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'bet';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $session['member']['amount'];
                        $session_in['con_1'] = $session['data'];
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
                            'codeId' => 120,
                            'token' => $session['token'],
                        ];
                    }

                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'DREAM';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'bet';
                $session_in['response'] = 'out';
                $session_in['amount'] = $session['member']['amount'];
                $session_in['con_1'] = $session['data'];
                $session_in['con_2'] = null;
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

            }

        } else {

            $param = [
                'codeId' => 102,
                'token' => $session['token'],
            ];

        }


        return $param;
    }

    public function cancelBet(Request $request)
    {
        $param = [];
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['member']['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance_free;

            $data = GameLogProxy::where('company', 'DREAM')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'cancel')
                ->where('con_1', $session['data'])
                ->whereNull('con_2')
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'codeId' => 102,
                    'token' => $session['token'],
                ];

            } else {

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'DREAM';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'in';
                $session_in['amount'] = $session['member']['amount'];
                $session_in['con_1'] = $session['data'];
                $session_in['con_2'] = null;
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                if ($session['member']['amount'] < 0) {
                    $member->balance_free += $session['member']['amount'];
                    $member->save();
                } else {
                    $member->balance_free -= $session['member']['amount'];
                    $member->save();
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'DREAM';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'cancel';
                $session_in['response'] = 'out';
                $session_in['amount'] = $session['member']['amount'];
                $session_in['con_1'] = $session['data'];
                $session_in['con_2'] = null;
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance_free;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);


            }

        } else {

            $param = [
                'codeId' => 102,
                'token' => $session['token'],
            ];

        }


        return $param;
    }

}
