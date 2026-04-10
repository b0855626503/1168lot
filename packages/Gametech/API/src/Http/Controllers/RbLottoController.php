<?php

namespace Gametech\API\Http\Controllers;

use Gametech\API\Models\GameLogProxy;
use Gametech\Game\Repositories\GameUserRepository;
use Gametech\Member\Models\MemberProxy;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use MongoDB\BSON\UTCDateTime;

class RbLottoController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $gameUserRepository;

    public function __construct(
        BankPaymentRepository $repository,
        MemberRepository $memberRepo,
        GameUserRepository $gameUserRepo
    ) {
        $this->_config = request('_config');

        $this->middleware('api');

        $this->repository = $repository;

        $this->memberRepository = $memberRepo;

        $this->gameUserRepository = $gameUserRepo;
    }

    public function getBalance(Request $request)
    {
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $param = [
                'username' => $session['username'],
                'balance' => (float) $member->balance,
            ];

        } else {

            $param = [
                'error_code' => 1000,
                'message' => 'user not found',
            ];

        }
        //
        $path = storage_path('logs/seamless/rb7lotto'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r('-- GET BALANCE --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function placeBet(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $txnDup = GameLogProxy::where('company', 'RB7LOTTO')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
                ->where('con_1', $session['request_id'])
                ->exists();

            if ($txnDup) {

                $param = [
                    'username' => $session['username'],
                    'balance' => (float) $member->balance,
                    'error_code' => 0,
                ];

            } else {

                $txnCancel = GameLogProxy::where('company', 'RB7LOTTO')
                    ->where('response', 'in')
                    ->where('game_user', $member->user_name)
                    ->where('method', 'CANCEL')
                    ->where('con_3', (string) $session['ticket_id'])
                    ->first();

                if ($txnCancel) {
                    $param = [
                        'username' => $session['username'],
                        'balance' => (float) $member->balance,
                        'error_code' => 0,
                    ];

                    $this->createGameLog([
                        'input' => $session,
                        'output' => $param,
                        'company' => 'RB7LOTTO',
                        'game_user' => $member->user_name,
                        'method' => 'OPEN',
                        'response' => 'in',
                        'amount' => abs($session['amount']),
                        'con_1' => (string) $session['request_id'],
                        'con_2' => (string) $session['round_id'],
                        'con_3' => (string) $session['ticket_id'],
                        'con_4' => 'CANCEL_'.$txnCancel->_id,
                        'before_balance' => $oldbalance,
                        'after_balance' => $member->balance,
                        'date_create' => now()->toDateTimeString(),
                        'expireAt' => new UTCDateTime(now()->addDays(16)),
                    ]);
                } else {

                    $balance = $member->balance - abs($session['amount']);
                    if ($balance < 0) {
                        $param = [
                            'error_code' => -1,
                            'message' => 'Insufficient balance',
                        ];

                    } else {

                        MemberProxy::where('user_name', $session['username'])->decrement('balance', abs($session['amount']));
                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        $param = [
                            'username' => $session['username'],
                            'balance' => (float) $member->balance,
                            'error_code' => 0,
                        ];

                        $this->createGameLog([
                            'input' => $session,
                            'output' => $param,
                            'company' => 'RB7LOTTO',
                            'game_user' => $member->user_name,
                            'method' => 'OPEN',
                            'response' => 'in',
                            'amount' => abs($session['amount']),
                            'con_1' => (string) $session['request_id'],
                            'con_2' => (string) $session['round_id'],
                            'con_3' => (string) $session['ticket_id'],
                            'con_4' => null,
                            'before_balance' => $oldbalance,
                            'after_balance' => $member->balance,
                            'date_create' => now()->toDateTimeString(),
                            'expireAt' => new UTCDateTime(now()->addDays(16)),
                        ]);
                    }
                }
            }
        } else {

            $param = [
                'error_code' => 1000,
                'message' => 'user not found',
            ];
        }

        $path = storage_path('logs/seamless/rb7lotto'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r('-- BET --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    protected function createGameLog(array $data)
    {
        return GameLogProxy::create($data);
    }

    public function settleBet(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $log = GameLogProxy::where('company', 'RB7LOTTO')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
                ->where('con_3', (string) $session['ticket_id'])
                ->whereNull('con_4')
                ->latest('created_at')
                ->first();

            if (! $log) {

                $param = [
                    'username' => $session['username'],
                    'balance' => (float) $member->balance,
                    'error_code' => 0,
                ];

            } else {

                MemberProxy::where('user_name', $session['username'])->increment('balance', $session['amount']);
                $member = MemberProxy::where('user_name', $session['username'])->first();

                $param = [
                    'username' => $session['username'],
                    'balance' => (float) $member->balance,
                    'error_code' => 0,
                ];

                $logId = $this->createGameLog([
                    'input' => $session,
                    'output' => $param,
                    'company' => 'RB7LOTTO',
                    'game_user' => $member->user_name,
                    'method' => 'SETTLED',
                    'response' => 'in',
                    'amount' => $session['amount'],
                    'con_1' => (string) $session['request_id'],
                    'con_2' => (string) $session['round_id'],
                    'con_3' => (string) $session['ticket_id'],
                    'con_4' => null,
                    'before_balance' => $oldbalance,
                    'after_balance' => $member->balance,
                    'date_create' => now()->toDateTimeString(),
                    'expireAt' => new UTCDateTime(now()->addDays(16)),
                ])->id;

                $log->con_4 = 'SETTLED_'.$logId;
                $log->save();
            }
        } else {

            $param = [
                'error_code' => 1000,
                'message' => 'user not found',
            ];
        }

        $path = storage_path('logs/seamless/rb7lotto'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r('-- SETTLE --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function cancelBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {
            $oldbalance = $member->balance;

            $log = GameLogProxy::where('company', 'RB7LOTTO')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'OPEN')
                ->where('con_3', (string) $session['ticket_id'])
                ->first();

            if (! $log) {

                $param = [
                    'username' => $session['username'],
                    'balance' => (float) $member->balance,
                    'error_code' => 0,
                ];

                $this->createGameLog([
                    'input' => $session,
                    'output' => $param,
                    'company' => 'RB7LOTTO',
                    'game_user' => $member->user_name,
                    'method' => 'CANCEL',
                    'response' => 'in',
                    'amount' => 0,
                    'con_1' => (string) $session['request_id'],
                    'con_2' => (string) $session['round_id'],
                    'con_3' => (string) $session['ticket_id'],
                    'con_4' => null,
                    'before_balance' => $oldbalance,
                    'after_balance' => $member->balance,
                    'date_create' => now()->toDateTimeString(),
                    'expireAt' => new UTCDateTime(now()->addDays(16)),
                ]);

            } else {

                if (Str::contains($log->con_4, 'CANCEL')) {

                    $param = [
                        'error_code' => 10,
                        'message' => 'Bet ticket id already have been cancelled',
                    ];

                } else {

                    $betAmount = $log->amount;

                    MemberProxy::where('user_name', $session['username'])->increment('balance', $betAmount);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

                    $param = [
                        'username' => $session['username'],
                        'balance' => (float) $member->balance,
                        'error_code' => 0,
                    ];

                    $logId = $this->createGameLog([
                        'input' => $session,
                        'output' => $param,
                        'company' => 'RB7LOTTO',
                        'game_user' => $member->user_name,
                        'method' => 'CANCEL',
                        'response' => 'in',
                        'amount' => $betAmount,
                        'con_1' => (string) $session['request_id'],
                        'con_2' => (string) $session['round_id'],
                        'con_3' => (string) $session['ticket_id'],
                        'con_4' => null,
                        'before_balance' => $oldbalance,
                        'after_balance' => $member->balance,
                        'date_create' => now()->toDateTimeString(),
                        'expireAt' => new UTCDateTime(now()->addDays(16)),
                    ])->id;

                    $log->con_4 = 'CANCEL_'.$logId;
                    $log->save();
                }
            }

        } else {

            $param = [
                'error_code' => 1000,
                'message' => 'user not found',
            ];

        }

        $path = storage_path('logs/seamless/rb7lotto'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r('-- CANCEL --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function rollBack(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $log = GameLogProxy::where('company', 'RB7LOTTO')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'SETTLED')
                ->where('con_1', $session['rollback_target_request_id'])
                ->first();

            if (! $log) {

                $param = [
                    'error_code' => 10,
                    'message' => 'Not Found Rollback Target Request ID',
                ];

            } else {

                if (Str::contains($log->con_4, 'ROLLBACK')) {

                    $param = [
                        'error_code' => 10,
                        'message' => 'Target Request ID Rollback already done',
                    ];

                } else {

                    $amount = $log->amount;
                    MemberProxy::where('user_name', $session['username'])->decrement('balance', $amount);
                    $member = MemberProxy::where('user_name', $session['username'])->first();

                    $param = [
                        'username' => $session['username'],
                        'balance' => (float) $member->balance,
                        'error_code' => 0,
                    ];

                    $logId = $this->createGameLog([
                        'input' => $session,
                        'output' => $param,
                        'company' => 'RB7LOTTO',
                        'game_user' => $member->user_name,
                        'method' => 'ROLLBACK',
                        'response' => 'in',
                        'amount' => $amount,
                        'con_1' => (string) $session['request_id'],
                        'con_2' => (string) $session['rollback_target_request_id'],
                        'con_3' => null,
                        'con_4' => null,
                        'before_balance' => $oldbalance,
                        'after_balance' => $member->balance,
                        'date_create' => now()->toDateTimeString(),
                        'expireAt' => new UTCDateTime(now()->addDays(16)),
                    ])->id;

                    $log->con_4 = 'ROLLBACK_'.$logId;
                    $log->save();

                    GameLogProxy::where('con_4', $log->method.'_'.$log->id)
                        ->where('method', 'OPEN')
                        ->where('company', 'RB7LOTTO')
                        ->where('game_user', $member->user_name)
                        ->update(['con_4' => null]);
                }
            }

        } else {

            $param = [
                'error_code' => 1000,
                'message' => 'user not found',
            ];

        }

        $path = storage_path('logs/seamless/rb7lotto'.now()->format('Y_m_d').'.log');
        file_put_contents($path, print_r('-- ROLLBACK --', true), FILE_APPEND);
        file_put_contents($path, print_r($session, true), FILE_APPEND);
        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function unsettleBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

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
                        'balance' => (float) $member->balance,
                        'lastUpdate' => now()->toDateTimeString(),
                    ],
                    'balance' => [
                        'before' => (float) $oldbalance,
                        'after' => (float) $member->balance,
                    ],
                    'refId' => $session['refId'],
                ],
            ];

        }

        //        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r('-- UNSETTLE --', true), FILE_APPEND);
        //        file_put_contents($path, print_r($session, true), FILE_APPEND);
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return Response::json($param);
    }

    public function adjustBets(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'ACE333')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'adjust')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 20001,
                    'balance' => (float) $member->balance,
                    'timestampMillis' => now()->getTimestampMs(),
                    'productId' => $session['productId'],
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['betAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'ACE333';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'adjust';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {

                    $datasub = GameLogProxy::where('company', 'ACE333')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'ajsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $adjust = $item['betAmount'] - $datasub['amount'];

                        $newbalance = $member->balance - $adjust;

                        //                        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
                        //                        file_put_contents($path, print_r('-- CAL ADJUST --', true), FILE_APPEND);
                        //                        file_put_contents($path, print_r($item['betAmount'] . ' - ' . $datasub['amount'], true), FILE_APPEND);

                        //                        if ($adjust >= 0) {

                        if ($newbalance >= 0) {

                            MemberProxy::where('user_name', $session['username'])->decrement('balance', $adjust);
                            $member = MemberProxy::where('user_name', $session['username'])->first();

                            //                            $member->balance = $member->balance - $adjust;
                            //                            $member->save();

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 0,
                                'currency' => 'THB',
                                'productId' => $session['productId'],
                                'username' => $member->user_name,
                                'balanceBefore' => (float) $oldbalance,
                                'balanceAfter' => (float) $member->balance,
                                'timestampMillis' => now()->getTimestampMs(),
                            ];

                            GameLogProxy::where('company', 'ACE333')
                                ->where('response', 'in')
                                ->where('game_user', $member->user_name)
                                ->where('method', 'ajsub')
                                ->where('con_1', $item['id'])
                                ->where('con_2', $item['roundId'])
                                ->where('con_3', $item['txnId'])
                                ->whereNull('con_4')
                                ->update(['con_4' => 'complete']);

                            $session_in['input'] = $item;
                            $session_in['output'] = $param;
                            $session_in['company'] = 'ACE333';
                            $session_in['game_user'] = $member->user_name;
                            $session_in['method'] = 'ajsub';
                            $session_in['response'] = 'in';
                            $session_in['amount'] = $item['betAmount'];
                            $session_in['con_1'] = $item['id'];
                            $session_in['con_2'] = $item['roundId'];
                            $session_in['con_3'] = $item['txnId'];
                            $session_in['con_4'] = null;
                            $session_in['before_balance'] = $oldbalance;
                            $session_in['after_balance'] = $member->balance;
                            $session_in['date_create'] = now()->toDateTimeString();
                            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                            GameLogProxy::create($session_in);

                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 10002,
                                'balance' => (float) $member->balance,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId'],
                            ];
                            break;

                        }

                    } else {

                        $datasubs = GameLogProxy::where('company', 'ACE333')
                            ->where('response', 'in')
                            ->where('game_user', $member->user_name)
                            ->where('method', 'betsub')
                            ->where('con_1', $item['id'])
                            ->where('con_2', $item['roundId'])
                            ->where('con_3', $item['txnId'])
                            ->whereNull('con_4')
                            ->first();

                        if ($datasubs) {

                            $adjust = $item['betAmount'] - $datasubs['amount'];

                            if ($adjust >= 0) {

                                $newbalance = $member->balance - $adjust;

                                if ($newbalance >= 0) {

                                    MemberProxy::where('user_name', $session['username'])->decrement('balance', $adjust);
                                    $member = MemberProxy::where('user_name', $session['username'])->first();

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 0,
                                        'currency' => 'THB',
                                        'productId' => $session['productId'],
                                        'username' => $member->user_name,
                                        'balanceBefore' => (float) $oldbalance,
                                        'balanceAfter' => (float) $member->balance,
                                        'timestampMillis' => now()->getTimestampMs(),
                                    ];

                                    $session_in['input'] = $item;
                                    $session_in['output'] = $param;
                                    $session_in['company'] = 'ACE333';
                                    $session_in['game_user'] = $member->user_name;
                                    $session_in['method'] = 'ajsub';
                                    $session_in['response'] = 'in';
                                    $session_in['amount'] = $item['betAmount'];
                                    $session_in['con_1'] = $item['id'];
                                    $session_in['con_2'] = $item['roundId'];
                                    $session_in['con_3'] = $item['txnId'];
                                    $session_in['con_4'] = null;
                                    $session_in['before_balance'] = $oldbalance;
                                    $session_in['after_balance'] = $member->balance;
                                    $session_in['date_create'] = now()->toDateTimeString();
                                    $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                    GameLogProxy::create($session_in);

                                } else {

                                    $param = [
                                        'id' => $session['id'],
                                        'statusCode' => 10002,
                                        'balance' => (float) $member->balance,
                                        'timestampMillis' => now()->getTimestampMs(),
                                        'productId' => $session['productId'],
                                    ];
                                    break;

                                }

                            } else {

                                MemberProxy::where('user_name', $session['username'])->increment('balance', abs($adjust));
                                $member = MemberProxy::where('user_name', $session['username'])->first();

                                $param = [
                                    'id' => $session['id'],
                                    'statusCode' => 0,
                                    'currency' => 'THB',
                                    'productId' => $session['productId'],
                                    'username' => $member->user_name,
                                    'balanceBefore' => (float) $oldbalance,
                                    'balanceAfter' => (float) $member->balance,
                                    'timestampMillis' => now()->getTimestampMs(),
                                ];

                                $session_in['input'] = $item;
                                $session_in['output'] = $param;
                                $session_in['company'] = 'ACE333';
                                $session_in['game_user'] = $member->user_name;
                                $session_in['method'] = 'ajsub';
                                $session_in['response'] = 'in';
                                $session_in['amount'] = $item['betAmount'];
                                $session_in['con_1'] = $item['id'];
                                $session_in['con_2'] = $item['roundId'];
                                $session_in['con_3'] = $item['txnId'];
                                $session_in['con_4'] = null;
                                $session_in['before_balance'] = $oldbalance;
                                $session_in['after_balance'] = $member->balance;
                                $session_in['date_create'] = now()->toDateTimeString();
                                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                                GameLogProxy::create($session_in);

                            }

                        } else {

                            $param = [
                                'id' => $session['id'],
                                'statusCode' => 20001,
                                'balance' => (float) $member->balance,
                                'timestampMillis' => now()->getTimestampMs(),
                                'productId' => $session['productId'],
                            ];
                            break;

                        }
                    }
                }
            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ACE333';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'adjust';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId'],
            ];

        }

        //        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r('-- ADJUST --', true), FILE_APPEND);
        //        file_put_contents($path, print_r($session, true), FILE_APPEND);
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }

    public function winRewards(Request $request)
    {
        $param = [];
        $amount = 0;
        $session = $request->all();

        $member = $this->memberRepository->findOneWhere(['user_name' => $session['username'], 'enable' => 'Y']);

        if ($member) {

            $oldbalance = $member->balance;

            $data = GameLogProxy::where('company', 'ACE333')
                ->where('response', 'in')
                ->where('game_user', $member->user_name)
                ->where('method', 'win')
                ->where('con_1', $session['id'])
                ->where('con_2', $session['productId'])
                ->whereNull('con_3')
                ->whereNull('con_4')
                ->first();

            if ($data) {

                $param = [
                    'id' => $session['id'],
                    'statusCode' => 0,
                    'currency' => 'THB',
                    'productId' => $session['productId'],
                    'username' => $member->user_name,
                    'balanceBefore' => (float) $oldbalance,
                    'balanceAfter' => (float) $member->balance,
                    'timestampMillis' => now()->getTimestampMs(),
                ];

            } else {

                foreach ($session['txns'] as $item) {
                    $amount += $item['payoutAmount'];
                }

                $session_in['input'] = $session;
                $session_in['output'] = $param;
                $session_in['company'] = 'ACE333';
                $session_in['game_user'] = $member->user_name;
                $session_in['method'] = 'win';
                $session_in['response'] = 'in';
                $session_in['amount'] = $amount;
                $session_in['con_1'] = $session['id'];
                $session_in['con_2'] = $session['productId'];
                $session_in['con_3'] = null;
                $session_in['con_4'] = null;
                $session_in['before_balance'] = $oldbalance;
                $session_in['after_balance'] = $member->balance;
                $session_in['date_create'] = now()->toDateTimeString();
                $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                GameLogProxy::create($session_in);

                foreach ($session['txns'] as $item) {
                    //                    $amount += $item['payoutAmount'];

                    $datasub = GameLogProxy::where('company', 'ACE333')
                        ->where('response', 'in')
                        ->where('game_user', $member->user_name)
                        ->where('method', 'winsub')
                        ->where('con_1', $item['id'])
                        ->where('con_2', $item['roundId'])
                        ->where('con_3', $item['txnId'])
                        ->whereNull('con_4')
                        ->first();

                    if ($datasub) {

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => 'THB',
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float) $oldbalance,
                            'balanceAfter' => (float) $member->balance,
                            'timestampMillis' => now()->getTimestampMs(),
                        ];

                    } else {

                        MemberProxy::where('user_name', $session['username'])->increment('balance', $item['payoutAmount']);
                        $member = MemberProxy::where('user_name', $session['username'])->first();

                        //                        $member->balance += $item['payoutAmount'];
                        //                        $member->save();

                        $param = [
                            'id' => $session['id'],
                            'statusCode' => 0,
                            'currency' => 'THB',
                            'productId' => $session['productId'],
                            'username' => $member->user_name,
                            'balanceBefore' => (float) $oldbalance,
                            'balanceAfter' => (float) $member->balance,
                            'timestampMillis' => now()->getTimestampMs(),
                        ];

                        $session_in['input'] = $item;
                        $session_in['output'] = $param;
                        $session_in['company'] = 'ACE333';
                        $session_in['game_user'] = $member->user_name;
                        $session_in['method'] = 'winsub';
                        $session_in['response'] = 'in';
                        $session_in['amount'] = $item['payoutAmount'];
                        $session_in['con_1'] = $item['id'];
                        $session_in['con_2'] = $item['roundId'];
                        $session_in['con_3'] = $item['txnId'];
                        $session_in['con_4'] = null;
                        $session_in['before_balance'] = $oldbalance;
                        $session_in['after_balance'] = $member->balance;
                        $session_in['date_create'] = now()->toDateTimeString();
                        $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
                        GameLogProxy::create($session_in);

                    }

                } // loop

            }

            $session_in['input'] = $session;
            $session_in['output'] = $param;
            $session_in['company'] = 'ACE333';
            $session_in['game_user'] = $member->user_name;
            $session_in['method'] = 'win';
            $session_in['response'] = 'out';
            $session_in['amount'] = $amount;
            $session_in['con_1'] = $session['id'];
            $session_in['con_2'] = $session['productId'];
            $session_in['con_3'] = null;
            $session_in['con_4'] = null;
            $session_in['before_balance'] = $oldbalance;
            $session_in['after_balance'] = $member->balance;
            $session_in['date_create'] = now()->toDateTimeString();
            $session_in['expireAt'] = new UTCDateTime(now()->addDays(2));
            GameLogProxy::create($session_in);

        } else {

            $param = [
                'id' => $session['id'],
                'statusCode' => 10001,
                'timestampMillis' => now()->getTimestampMs(),
                'productId' => $session['productId'],
            ];

        }

        //        $path = storage_path('logs/seamless/ACE333' . now()->format('Y_m_d') . '.log');
        //        file_put_contents($path, print_r('-- WIN --', true), FILE_APPEND);
        //        file_put_contents($path, print_r($session, true), FILE_APPEND);
        //        file_put_contents($path, print_r($param, true), FILE_APPEND);

        return $param;
    }
}
