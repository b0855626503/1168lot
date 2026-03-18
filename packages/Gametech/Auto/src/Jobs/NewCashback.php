<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;


class NewCashback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 60;

    public $tries = 1;

    public $maxExceptions = 3;

    public $retryAfter = 3;

    protected $date;

    public function __construct($date)
    {
        $this->date = $date;

    }


    public function handle()
    {

        $startdate = $this->date;
        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            return false;
        }

        $pro_code = $promotion->code;
        $pro_name = $promotion->id;
        $turnpro = $promotion->turnpro;
        $withdraw_limit = $promotion->withdraw_limit;
        $withdraw_limit_rate = $promotion->withdraw_limit_rate;

        $bonus = $promotion->bonus_percent;
        $bonusmin = $promotion->bonus_min;
        $bonusmax = $promotion->bonus_max;

        $param = [
            'startDate' => date('Y-m-d', strtotime('-7 day')),
            'endDate' => date("Y-m-d", strtotime("yesterday"))
        ];

        $lists = app('Gametech\Game\Repositories\GameUserRepository')->checkUserTurn(1, 0, $param);

        foreach ($lists['result'] as $items) {

            $winlose = $items['winLost'];

            if ($winlose > 0) {
                $topup = 'X';
            } else {
                $topup = 'N';
            }

            if($winlose < 0) {


                $cashback = ((($winlose * -1) * $bonus) / 100);

                if ($bonusmin > 0) {
                    if ($cashback < $bonusmin) {
                        $cashback = 0;
                    }
                }
                if ($bonusmax > 0) {
                    if ($cashback > $bonusmax) {
                        $cashback = $bonusmax;
                    }
                }

            }else{
                $cashback = 0;
            }



            $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('user_name', $items['username']);
            if ($user) {

                $member = app('Gametech\Member\Repositories\MemberRepository')->findOneByField('code', $user->member_code);
                if ($member) {

                    $member_code = $member->code;
                }else{
//                    $member = app('Gametech\Member\Repositories\MemberRepository')->findOneByField('code', 0);
                    continue;
//                    $member_code = 0;
//                    $member->code = 0;
                }
            }else{
                $member_code = 0;
            }

            $game_user = app('Gametech\Game\Repositories\GameUserEventRepository')->findOneWhere(['method' => 'CASHBACK', 'member_code' => $member->code, 'game_code' => 1, 'enable' => 'Y']);
            if (!$game_user) {
                $game_user = app('Gametech\Game\Repositories\GameUserEventRepository')->create([
                    'game_code' => 1,
                    'member_code' => $member->code,
                    'pro_code' => $pro_code,
                    'method' => 'CASHBACK',
                    'user_name' => $member->user_name,
                    'amount' => 0,
                    'bonus' => 0,
                    'turnpro' => 0,
                    'amount_balance' => 0,
                    'withdraw_limit' => 0,
                    'withdraw_limit_rate' => 0,
                    'withdraw_limit_amount' => 0,
                ]);
            }



            $chk = DB::table('members_cashback')->whereDate('date_cashback', $startdate)->where('game_user', $items['username']);
            if ($chk->doesntExist()) {

//                $data = [
//                    'date_cashback' => $startdate,
//                    'member_code' => $member_code   ,
//                    'game_user' => $items['username'],
//                    'cashback' => $cashback,
//                    'topupic' => $topup,
//                    'ip_admin' => request()->ip(),
//                    'turnpro' => $items['turn'],
//                    'winlose' => $items['winLost'],
//                    'startdate' => $param['startDate'],
//                    'enddate' => $param['endDate'],
//                    'emp_code' => 0,
//                    'user_create' => 'SYSTEM',
//                    'user_update' => 'SYSTEM'
//                ];
//                $bill = DB::table('members_cashback2')->insertGetId($data);
                $bill = app('Gametech\Member\Repositories\MemberCashbackRepository')->create([
                    'date_cashback' => $startdate,
                    'member_code' => $member_code   ,
                    'game_user' => $items['username'],
                    'cashback' => $cashback,
                    'topupic' => $topup,
                    'ip_admin' => request()->ip(),
                    'turnpro' => $items['turn'],
                    'winlose' => $items['winLost'],
                    'startdate' => $param['startDate'],
                    'enddate' => $param['endDate'],
                    'emp_code' => 0,
                    'user_create' => 'SYSTEM',
                    'user_update' => 'SYSTEM'
                ]);

                $member->cashback = $cashback;
                $member->save();

                $game_user->amount = $member->balance;
                $game_user->pro_code = $pro_code;
                $game_user->bill_code = $bill->code;
                $game_user->turnpro = $turnpro;
                $game_user->bonus += $cashback;
                $game_user->amount_balance += ($cashback * $turnpro);
                $game_user->withdraw_limit += $withdraw_limit;
                $game_user->withdraw_limit_rate = $withdraw_limit_rate;
                $game_user->withdraw_limit_amount += ($cashback * $withdraw_limit_rate);
                $game_user->save();

                app('Gametech\Member\Repositories\MemberCreditLogRepository')->create([
                    'ip' => request()->ip(),
                    'credit_type' => 'D',
                    'game_code' => 1,
                    'gameuser_code' => $user->code,
                    'amount' => $cashback,
                    'bonus' => 0,
                    'total' => $cashback,
                    'balance_before' => 0,
                    'balance_after' => 0,
                    'credit' => 0,
                    'credit_bonus' => 0,
                    'credit_total' => 0,
                    'credit_before' => 0,
                    'credit_after' => 0,
                    'member_code' => $member->code,
                    'pro_code' => $pro_code,
                    'refer_code' => $bill->code,
                    'refer_table' => 'members_cashback',
                    'auto' => 'Y',
                    'remark' => 'ได้รับยอด Cashback จากการคำนวนรายสัปดาห์ '.$param['startDate'].'-'.$param['endDate'].' (เข้ากระเป๋าโบนัส รอลูกค้ากดรับ)',
                    'kind' => 'CASHBACK',
                    'amount_balance' => $game_user->amount_balance,
                    'withdraw_limit' => $game_user->withdraw_limit,
                    'withdraw_limit_amount' => $game_user->withdraw_limit_amount,
                    'user_create' => "System Auto",
                    'user_update' => "System Auto"
                ]);
            }
        }

    }

}
