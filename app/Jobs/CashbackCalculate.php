<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CashbackCalculate implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item;

    protected $date;

    protected $promotion;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($date, $item, $promotion)
    {
        $this->date = $date;
        $this->item = $item;
        $this->promotion = $promotion;
    }

    public function tags()
    {
        return ['render', 'cashback:'.$this->item->member_code];
    }

    public function uniqueId()
    {
        return $this->item->member_code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $startDate = $this->date;
        $item = $this->item;
        $promotion = $this->promotion;

        $bonus = $promotion->bonus_percent;
        $bonusmin = $promotion->bonus_min;
        $bonusmax = $promotion->bonus_max;

        $balance = $item->balance;

        if ($item->bonus_amount > 0 || ($item->deposit_amount - $item->withdraw_amount - $balance) <= 0) {
            return;
        }

        $item->bonusmax = $bonusmax;
        $item->bonus = $bonus;
        $item->ip = request()->ip();
        $item->emp_code = 0;
        $item->emp_name = 'SYSTEM';

        $item->balance_total = ($item->deposit_amount - $item->withdraw_amount - $balance);
        $cashback = (($item->balance_total * $bonus) / 100);

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

        if ($cashback > 0) {
            $topup = 'N';
        } else {
            $topup = 'X';
        }

        $chk = DB::table('members_cashback')->whereDate('date_cashback', $startDate)->where('downline_code', $item->member_code);
        if ($chk->doesntExist()) {
            $response = app('Gametech\Member\Repositories\MemberCashbackRepository')->create([
                'member_code' => $item->upline_code,
                'downline_code' => $item->member_code,
                'date_cashback' => $startDate,
                'balance' => $item->balance_total,
                'cashback' => $cashback,
                'amount' => $item->balance,
                'topupic' => $topup,
                'ip_admin' => request()->ip(),
                'emp_code' => $item->emp_code,
                'user_create' => $item->emp_name,
                'user_update' => $item->emp_name,
                'sum_balance' => $item->balance,
                'sum_deposit' => $item->deposit_amount,
                'sum_withdraw' => $item->withdraw_amount,
            ]);

            if ($topup === 'N') {
                $data = [
                    'code' => $response->code,
                    'upline_code' => $item->upline_code,
                    'member_code' => $item->member_code,
                    'balance' => $item->balance_total,
                    'cashback' => $cashback,
                    'date_cashback' => $startDate,
                    'ip' => request()->ip(),
                    'emp_code' => $item->emp_code,
                    'emp_name' => $item->emp_name,
                    'sum_balance' => $item->balance,
                    'sum_deposit' => $item->deposit_amount,
                    'sum_withdraw' => $item->withdraw_amount,
                ];

                app('Gametech\Member\Repositories\MemberCashbackRepository')->refillSeamlessDirect($data);
            }

        }
    }
}
