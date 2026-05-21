<?php

namespace App\Jobs;

use Carbon\Carbon;
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

    protected string $dateStart;

    protected string $dateEnd;

    protected string $cashbackDate;

    protected object $item;

    protected object $promotion;

    protected string $target;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        string $dateStart,
        string $dateEnd,
        string $cashbackDate,
        object $item,
        object $promotion,
        string $target = 'wallet'
    ) {
        $this->dateStart = $dateStart;
        $this->dateEnd = $dateEnd;
        $this->cashbackDate = $cashbackDate;
        $this->item = $item;
        $this->promotion = $promotion;
        $this->target = $target;
    }

    public function tags(): array
    {
        return ['render', 'cashback:'.$this->item->member_code];
    }

    public function uniqueId(): string
    {
        return implode(':', [
            'cashback',
            $this->cashbackDate,
            $this->dateStart,
            $this->dateEnd,
            $this->target,
            (string) $this->item->member_code,
        ]);
    }

    public function getDateStart(): string
    {
        return $this->dateStart;
    }

    public function getDateEnd(): string
    {
        return $this->dateEnd;
    }

    public function getCashbackDate(): string
    {
        return $this->cashbackDate;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function getItem(): object
    {
        return $this->item;
    }

    public function handle(): void
    {
        $startDate = Carbon::parse($this->dateStart);
        $endDate = Carbon::parse($this->dateEnd);
        $item = $this->item;
        $promotion = $this->promotion;
        $target = $this->normalizeTarget($this->target);

        $bonus = $promotion->bonus_percent;
        $bonusmin = $promotion->bonus_min;
        $bonusmax = $promotion->bonus_max;

        $balance = $item->balance;

        $netAmount = ($item->deposit_amount - $item->withdraw_amount - $balance);
        if ($netAmount <= 0) {
            $this->recordSkippedCashback($item, $netAmount);

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

        $chk = DB::table('members_cashback')
            ->whereDate('date_cashback', $this->cashbackDate)
            ->where('downline_code', $item->member_code);

        if ($chk->doesntExist()) {
            $response = app('Gametech\Member\Repositories\MemberCashbackRepository')->create([
                'member_code' => $item->upline_code,
                'downline_code' => $item->member_code,
                'date_cashback' => $this->cashbackDate,
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
                    'date_cashback' => $this->cashbackDate,
                    'ip' => request()->ip(),
                    'emp_code' => $item->emp_code,
                    'emp_name' => $item->emp_name,
                    'sum_balance' => $item->balance,
                    'sum_deposit' => $item->deposit_amount,
                    'sum_withdraw' => $item->withdraw_amount,
                ];

                if ($target === 'cashback') {
                    app('Gametech\Member\Repositories\MemberCashbackRepository')->refillSeamless($data);
                } else {
                    app('Gametech\Member\Repositories\MemberCashbackRepository')->refillSeamlessDirect($data);
                }
            }

        }
    }

    private function normalizeTarget(string $target): string
    {
        return strtolower(trim($target)) === 'cashback' ? 'cashback' : 'wallet';
    }

    private function recordSkippedCashback(object $item, float $netAmount): void
    {
        $existing = DB::table('members_cashback')
            ->whereDate('date_cashback', $this->cashbackDate)
            ->where('downline_code', $item->member_code)
            ->exists();

        if (! $existing) {
            app('Gametech\Member\Repositories\MemberCashbackRepository')->create([
                'member_code' => $item->upline_code,
                'downline_code' => $item->member_code,
                'date_cashback' => $this->cashbackDate,
                'balance' => $netAmount,
                'cashback' => 0,
                'amount' => $item->balance,
                'topupic' => 'X',
                'ip_admin' => request()->ip(),
                'emp_code' => 0,
                'user_create' => 'SYSTEM',
                'user_update' => 'SYSTEM',
                'sum_balance' => $item->balance,
                'sum_deposit' => $item->deposit_amount,
                'sum_withdraw' => $item->withdraw_amount,
            ]);
        }

        $remark = 'ไม่ผ่านคำนวน Cashback รอบ '.$this->cashbackDate
            .' เนื่องจากยอดคำนวน <= 0 (ฝาก '.$item->deposit_amount
            .' ถอน '.$item->withdraw_amount
            .' ยอดเงินตอนคำนวน '.$item->balance
            .' คิดเป็นยอดคำนวน '.$netAmount.')';

        $alreadyLogged = DB::table('members_credit_log')
            ->where('member_code', $item->member_code)
            ->where('kind', 'CASHBACK')
            ->where('refer_table', 'members_cashback')
            ->where('remark', $remark)
            ->exists();

        if ($alreadyLogged) {
            return;
        }

        DB::table('members_credit_log')->insert([
            'refer_code' => 0,
            'refer_table' => 'members_cashback',
            'credit_type' => 'W',
            'amount' => 0,
            'bonus' => 0,
            'total' => 0,
            'balance_before' => 0,
            'balance_after' => 0,
            'credit' => 0,
            'credit_bonus' => 0,
            'credit_total' => 0,
            'credit_before' => 0,
            'credit_after' => 0,
            'member_code' => $item->member_code,
            'kind' => 'CASHBACK',
            'auto' => 'Y',
            'remark' => $remark,
            'emp_code' => 0,
            'ip' => request()->ip() ?? 'SYSTEM',
            'amount_balance' => 0,
            'withdraw_limit' => 0,
            'withdraw_limit_amount' => 0,
            'user_create' => 'SYSTEM',
            'user_update' => 'SYSTEM',
            'date_create' => now()->toDateTimeString(),
            'date_update' => now()->toDateTimeString(),
        ]);
    }
}
