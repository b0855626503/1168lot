<?php

namespace Gametech\Auto\Console\Commands;


use Gametech\Auto\Jobs\MemberCashback as MemberCashbackJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;


class Cashback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cashback:list {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and Refill Cashback to member';

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
        $startdate = $this->argument('date');

        if (empty($startdate)) {
            $startdate = now()->subDays(1)->toDateString();
        }


        $promotion = DB::table('promotions')->where('id', 'pro_cashback')->first();

        if ($promotion->enable != 'Y' || $promotion->active != 'Y' || $promotion->use_auto != 'Y') {
            return false;
        }

        $bonus = $promotion->bonus_percent;


        $latestBi = DB::table('bills')
            ->select('bills.member_code', DB::raw('SUM(bills.credit_bonus)  as bonus_amount'), DB::raw("DATE_FORMAT(bills.date_create,'%Y-%m-%d') as date_approve"))
            ->where('bills.enable', 'Y')
            ->where('bills.transfer_type', 1)
            ->when($startdate, function ($query, $startdate) {
                $query->whereDate('bills.date_create', $startdate);
            })
            ->groupBy('bills.member_code', DB::raw('Date(bills.date_create)'));

        $latestWD = DB::table('withdraws')
            ->select('withdraws.member_code', DB::raw('SUM(withdraws.amount)  as withdraw_amount'), DB::raw("DATE_FORMAT(withdraws.date_approve,'%Y-%m-%d') as date_approve"))
            ->where('withdraws.enable', 'Y')
            ->where('withdraws.status', 1)
            ->when($startdate, function ($query, $startdate) {
                $query->whereDate('withdraws.date_approve', $startdate);
            })
            ->groupBy('withdraws.member_code', DB::raw('Date(withdraws.date_approve)'));

        $latestBP = DB::table('bank_payment')
            ->select(DB::raw('MAX(bank_payment.code) as code'), DB::raw('MAX(bank_payment.date_approve) as date_approve'), DB::raw('SUM(bank_payment.value) as deposit_amount'), DB::raw("DATE_FORMAT(bank_payment.date_approve,'%Y-%m-%d') as date_cashback"), 'bank_payment.member_topup')
            ->where('bank_payment.value', '>', 0)
            ->where('bank_payment.bankstatus', 1)
            ->where('bank_payment.enable', 'Y')
            ->where('bank_payment.status', 1)
            ->when($startdate, function ($query, $startdate) {
                $query->whereDate('bank_payment.date_approve', $startdate);
            })
            ->groupBy('bank_payment.member_topup', DB::raw('Date(bank_payment.date_approve)'));




        $lists = DB::table('members')
            ->select('members.upline_code', 'members.code as member_code', 'members.user_name as user_name', 'members.name as member_name', 'members.balance_free as balance', DB::raw('IFNULL(withdraw_amount,0) as withdraw_amount'), DB::raw('IFNULL(bonus_amount,0) as bonus_amount'), 'bank_payment.deposit_amount', 'bank_payment.date_cashback', 'bank_payment.date_approve', 'bank_payment.code')
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

        $bar = $this->output->createProgressBar($lists->count());
        $bar->start();

        foreach ($lists->cursor() as $items) {
            $items->bonus = $bonus;
            $items->ip = $ip;
            $items->emp_code = 0;
            $items->emp_name = 'SYSTEM';
            if ($items->bonus_amount > 0 || ($items->deposit_amount - $items->withdraw_amount) <= 0) {
                $bar->advance();
                continue;
            }
            $items->balance_total = ($items->deposit_amount - $items->withdraw_amount);
            $chk = DB::table('members_cashback')->whereDate('date_cashback', $startdate)->where('downline_code', $items->member_code)->where('topupic', 'Y');
            if ($chk->doesntExist()) {
                MemberCashbackJob::dispatch($startdate, $items)->onQueue('cashback');
            }
            $bar->advance();
        }
        $bar->finish();

    }

}
