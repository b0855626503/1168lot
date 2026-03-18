<?php

namespace Gametech\API\Http\Controllers;

use Gametech\API\Models\GameLogProxy;
use Gametech\API\Traits\LogSeamlessOld as LogSeamless;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use MongoDB\BSON\UTCDateTime;

class HuayDragonController extends AppBaseController
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

    protected function createGameLog(array $data)
    {
        return GameLogProxy::create($data);
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'balance' => (float)$member->balance,
                ],
            ];

        } else {
            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'balance' => 0,
                ],
            ];
        }
        //
        $path = storage_path('logs/seamless/HuayDragon' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function transferOut(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $path = storage_path('logs/seamless/HuayDragon_BET' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
                ->where('con_1', $session['refId'])
                ->exists();

            if ($txnDup) {

                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => 'Success',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];

                $this->createGameLog([
                    'input' => $session,
                    'output' => $param,
                    'company' => 'HUAYDRAGON',
                    'game_user' => $member->user_name,
                    'method' => 'betmain',
                    'response' => 'in',
                    'amount' => $session['amount'],
                    'con_1' => $session['refId'],
                    'con_2' => $session['roundId'],
                    'con_3' => $session['poyId'],
                    'con_4' => null,
                    'before_balance' => $oldbalance,
                    'after_balance' => $member->balance,
                    'date_create' => now()->toDateTimeString(),
                    'expireAt' => new UTCDateTime(now()->addDays(15)),
                ]);
                return Response::json($param);
            }


            MemberProxy::where('user_name', $session['username'])->decrement('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

            $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'OPEN',
                'response' => 'in',
                'amount' => $session['amount'],
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ]);

            $txn = [
                'betAmount' => $session['amount'],
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'OPEN'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txn,
                $oldbalance,
                $member->balance,
                false
            );

        }


        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r('-- END --', true), FILE_APPEND);
        return Response::json($param);
    }

    public function transferIn(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $path = storage_path('logs/seamless/HuayDragon_SETTLED' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- SETTLED --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'SETTLED')
                ->where('con_1', $session['refId'])
                ->exists();

            if ($txnDup) {
                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => 'Success',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            $chkBet = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['poyId'])
                ->whereNull('con_4')
                ->first();

            if (!$chkBet) {
                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => 'Cannot Settled  , Bet Already Settled or Canceled',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

            $settle = $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'SETTLED',
                'response' => 'in',
                'amount' => $session['amount'],
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ])->id;

            $chkBet->con_4 = 'SETTLED_' . $settle;
            $chkBet->save();

            $txn = [
                'betAmount' => 0,
                'payoutAmount' => $session['amount'],
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'isSingleState' => true,
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'SETTLED'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txn,
                $oldbalance,
                $member->balance,
                false
            );

        }


        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r('-- END --', true), FILE_APPEND);
        return Response::json($param);
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $path = storage_path('logs/seamless/HuayDragon_CANCEL' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'CANCELBET')
                ->where('con_1', $session['refId'])
                ->exists();

            if ($txnDup) {
                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => 'Success',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            $chkBet = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
//                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['poyId'])
                ->whereNull('con_4')
                ->first();

            if (!$chkBet) {
                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => 'Cannot Cancel Bet Already Settled',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }


            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

            $newlog = $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'CANCELBET',
                'response' => 'in',
                'amount' => $session['amount'],
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ])->id;

            $chkBet->con_4 = 'CANCELBET_' . $newlog;
            $chkBet->save();

            $txn = [
                'betAmount' => -$session['amount'],
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'isSingleState' => false,
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'CANCELBET'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txn,
                $oldbalance,
                $member->balance,
                false
            );

        }

        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r('-- END --', true), FILE_APPEND);

        return Response::json($param);
    }

    public function cancelNumber(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $path = storage_path('logs/seamless/HuayDragon_CANCELNUMBER' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- CANCELNUMBER --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'CANCELNUMBER')
                ->where('con_1', $session['refId'])
                ->exists();

            if ($txnDup) {
                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => 'Success',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            $chkBet = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
//                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['poyId'])
                ->whereNull('con_4')
                ->first();

            if (!$chkBet) {
                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => 'Cannot Cancel Bet Already Settled',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
            $member = MemberProxy::where('user_name', $session['username'])->first();

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

            $newlog = $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'CANCELNUMBER',
                'response' => 'in',
                'amount' => $session['amount'],
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ])->id;

            $chkBet->con_4 = 'CANCELNUMBER_' . $newlog;
            $chkBet->save();

            $txn = [
                'betAmount' => -$chkBet['amount'],
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'isSingleState' => false,
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'CANCELNUMBER'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txn,
                $oldbalance,
                $member->balance,
                false
            );

            $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'OPEN',
                'response' => 'in',
                'amount' => ($chkBet['amount'] - $session['amount']),
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ]);

            $txns = [
                'betAmount' => ($chkBet['amount'] - $session['amount']),
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'skipBalanceUpdate' => true,
                'isSingleState' => false,
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'OPEN'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txns,
                $member->balance,
                $member->balance,
                false
            );

        }


        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r('-- END --', true), FILE_APPEND);

        return Response::json($param);
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $path = storage_path('logs/seamless/HuayDragon_VOID' . now()->format('Y_m_d') . '.log');
        file_put_contents($path, print_r('-- VOID --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);


        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'VOID')
                ->where('con_1', $session['refId'])
                ->exists();

            if ($txnDup) {
                $param = [
                    'status' => [
                        'code' => 0,
                        'message' => 'Success',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            $chkBet = GameLogProxy::where('company', 'HUAYDRAGON')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'SETTLED')
                ->where('con_2', $session['roundId'])
                ->where('con_3', $session['poyId'])
                ->whereNull('con_4')
                ->first();

            if (!$chkBet) {
                $param = [
                    'status' => [
                        'code' => 806,
                        'message' => 'Cannot Void  , Settle Already Void',
                    ],
                    'data' => [
                        'username' => $session['username'],
                        'wallet' => [
                            'balance' => (float)$member->balance,
                            'lastUpdate' => now()->toDateTimeString(),
                        ],
                        'balance' => [
                            'before' => (float)$oldbalance,
                            'after' => (float)$member->balance,
                        ],
                        'refId' => $session['refId'],
                    ],
                ];
                return Response::json($param);
            }

            MemberProxy::where('user_name', $session['username'])->decrement('balance', abs($session['amount']));
            $member = MemberProxy::where('user_name', $session['username'])->first();

            $param = [
                'status' => [
                    'code' => 0,
                    'message' => 'Success',
                ],
                'data' => [
                    'username' => $session['username'],
                    'wallet' => [
                        'balance' => (float)$member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float)$oldbalance,
                        'after' => (float)$member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

            $newlog = $this->createGameLog([
                'input' => $session,
                'output' => $param,
                'company' => 'HUAYDRAGON',
                'game_user' => $member->user_name,
                'method' => 'VOID',
                'response' => 'in',
                'amount' => abs($session['amount']),
                'con_1' => $session['refId'],
                'con_2' => $session['roundId'],
                'con_3' => $session['poyId'],
                'con_4' => null,
                'before_balance' => $oldbalance,
                'after_balance' => $member->balance,
                'date_create' => now()->toDateTimeString(),
                'expireAt' => new UTCDateTime(now()->addDays(15)),
            ])->id;

            $chkBet->con_4 = 'VOID_' . $newlog;
            $chkBet->save();

            GameLogProxy::where('con_4', $chkBet->method . '_' . $chkBet->id)
                ->where('method', 'OPEN')
                ->where('company', 'HUAYDRAGON')
                ->where('game_user', $member->user_name)
                ->update(['con_4' => null]);

            $txn = [
                'payoutAmount' => $session['amount'],
                'id' => $session['poyId'],
                'roundId' => $session['roundId'],
                'playInfo' => $session['gameName'],
                'isSingleState' => false,
                'transactionType' => 'BY_TRANSACTION',
                'status' => 'VOID'
            ];

            LogSeamless::log(
                'HUAYDRAGON',
                $member->user_name,
                $txn,
                $oldbalance,
                $member->balance,
                false
            );

        }

        file_put_contents($path, print_r($param, true), FILE_APPEND);
        file_put_contents($path, print_r('-- END --', true), FILE_APPEND);

        return Response::json($param);
    }

}
