<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;


class NewMemberIcV2 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $uniqueFor = 10;

    public $timeout = 60;

    public $tries = 0;

    public $maxExceptions = 3;

//    public $retryAfter = 70;

    protected $item;

    protected $date;

    public function __construct($date)
    {
        $this->date = $date;

    }



    public function handle()
    {
        $startdate = $this->date;

        $promotion = DB::table('promotions')->where('id', 'pro_ic')->first();
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
            'startDate' => now()->startOfMonth()->subMonthsNoOverflow()->toDateString(),
            'endDate' => now()->subMonthsNoOverflow()->endOfMonth()->toDateString()
        ];

        $lists = app('Gametech\Game\Repositories\GameUserRepository')->checkUserTurn(1, 0, $param);

        foreach ($lists['result'] as $items) {

            $winlose = $items['winLost'];

            if ($winlose > 0) {
                $topup = 'X';
            } else {
                $topup = 'N';
            }

            if ($winlose < 0) {
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
            } else {
                $cashback = 0;
            }

            $user = app('Gametech\Game\Repositories\GameUserRepository')->findOneByField('user_name', $items['username']);
            if (!$user) {
                $member = '';
                $member_code = 0;
                $upline_code = 0;
                $upline_username = '';
                $upline_balance = 0;

            } else {

                $member = app('Gametech\Member\Repositories\MemberRepository')->findOneByField('code', $user->member_code);
                if(!$member){
                    continue;
                }
//                $this->info($user->member_code);
                if ($member->upline_code == 0) {
                    continue;
                }

                $member_code = $member->code;
                $upline_code = $member->upline_code;

                $member_up = app('Gametech\Member\Repositories\MemberRepository')->findOneByField('code', $upline_code);
                if(!$member_up){
                    continue;
                }
                $upline_username = $member_up->user_name;
                $upline_balance = $member_up->balance;
                $member_up->ic +=  $cashback;
                $member_up->save();
            }

            $game_user = app('Gametech\Game\Repositories\GameUserEventRepository')->findOneWhere(['method' => 'IC', 'member_code' => $upline_code, 'game_code' => 1, 'enable' => 'Y']);
            if (!$game_user) {
                $game_user = app('Gametech\Game\Repositories\GameUserEventRepository')->create([
                    'game_code' => 1,
                    'member_code' => $upline_code,
                    'pro_code' => $pro_code,
                    'method' => 'IC',
                    'user_name' => $upline_username,
                    'amount' => 0,
                    'bonus' => 0,
                    'turnpro' => 0,
                    'amount_balance' => 0,
                    'withdraw_limit' => 0,
                    'withdraw_limit_rate' => 0,
                    'withdraw_limit_amount' => 0,
                ]);
            }


            $chk = DB::table('members_ic')->whereDate('date_cashback', $startdate)->where('game_user', $items['username']);
            if ($chk->doesntExist()) {
                $bill = app('Gametech\Member\Repositories\MemberIcRepository')->create([
                    'date_cashback' => $startdate,
                    'member_code' => $upline_code,
                    'downline_code' => $member_code,
                    'game_user' => $items['username'],
                    'ic' => $cashback,
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

                $game_user->amount = $upline_balance;
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
                    'gameuser_code' => $game_user->code,
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
                    'member_code' => $upline_code,
                    'pro_code' => $pro_code,
                    'refer_code' => $bill->code,
                    'refer_table' => 'members_ic',
                    'auto' => 'Y',
                    'remark' => 'ได้รับยอด IC จากการคำนวนรายวัน '.$startdate.' (เข้ากระเป๋าโบนัส รอลูกค้ากดรับ)',
                    'kind' => 'IC',
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
