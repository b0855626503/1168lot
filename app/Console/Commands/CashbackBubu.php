<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CashbackBubu extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashback:start';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start Calculating Cashback for members';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $ip = request()->ip();
        $startDate = now()->subDays(1)->toDateString();

        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            return false;
        }

        $latestBi = DB::table('bills')
            ->select('bills.member_code', DB::raw('SUM(bills.credit_bonus)  as bonus_amount'), DB::raw("DATE_FORMAT(bills.date_create,'%Y-%m-%d') as date_approve"))
            ->where('bills.enable', 'Y')
            ->where('bills.transfer_type', 1)
            ->when($startDate, function ($query, $startDate) {
                $query->whereDate('bills.date_create', $startDate);
            })
            ->groupBy('bills.member_code', DB::raw('Date(bills.date_create)'));

        $latestWD = DB::table('withdraws', 'withdraws')
            ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'), DB::raw("DATE_FORMAT(withdraws.date_approve,'%Y-%m-%d') as date_approve"))
            ->where('withdraws.enable', 'Y')
            ->where('withdraws.status', 1)
            ->when($startDate, function ($query, $startDate) {
                $query->whereDate('withdraws.date_approve', $startDate);
            })
            ->groupBy('withdraws.member_code', DB::raw('Date(withdraws.date_approve)'));

        $latestBP = DB::table('bank_payment')
            ->select(DB::raw('MAX(bank_payment.code) as code'), DB::raw('MAX(bank_payment.date_approve) as date_approve'), DB::raw('SUM(bank_payment.value) as deposit_amount'), DB::raw("DATE_FORMAT(bank_payment.date_approve,'%Y-%m-%d') as date_cashback"), 'bank_payment.member_topup')
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startDate, function ($query, $startDate) {
                $query->whereDate('bank_payment.date_approve', $startDate);
            })
            ->groupBy('bank_payment.member_topup', DB::raw('Date(bank_payment.date_approve)'));

        $lists = DB::table('members')
            ->select('members.upline_code', 'members.code as member_code', 'members.user_name as user_name', 'members.name as member_name', 'members.balance as balance', DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'), DB::raw('IFNULL(bonus_amount,0) as bonus_amount'), 'bank_payment.deposit_amount', 'bank_payment.date_cashback', 'bank_payment.date_approve', 'bank_payment.code')
            ->orderByDesc('bank_payment.code')
            ->joinSub($latestBP, 'bank_payment', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'members.code');
            })
            ->leftJoinSub($latestBi, 'bills', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'bills.member_code');
                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'bills.date_approve');

            })
            ->leftJoinSub($latestWD, 'withdraws', function ($join) {
                $join->on('bank_payment.member_topup', '=', 'withdraws.member_code');
                $join->on(DB::raw('Date(bank_payment.date_approve)'), '=', 'withdraws.date_approve');

            });

        //        dd($lists->get());

        $lists->chunk(50, function ($itemlist) use ($promotion, $startDate) {

            foreach ($itemlist as $items) {

                \App\Jobs\CashbackBubu::dispatch($startDate, $items, $promotion)->onQueue('cashback');

            }

        });

    }
}
