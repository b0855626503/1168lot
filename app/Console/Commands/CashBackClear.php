<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CashBackClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:cashback';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear CashBack';

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
     * @return mixed
     */
    public function handle()
    {

        $config = core()->getConfigData();
        if ($config->freecredit_open === 'Y') {
            $table = 'members_credit_free_log';
        } else {
            $table = 'members_credit_log';
        }

        $lists = DB::table('members')->where('cashback', '>', 0)->orderByDesc('code');

        $bar = $this->output->createProgressBar($lists->count());
        $bar->start();

        foreach ($lists->get() as $items) {

            DB::table('members')->where('code', $items->code)->update(['cashback' => 0]);
            DB::table('games_user_event')->where('method', 'CASHBACK')->where('member_code', $items->code)->update([
                'bonus' => 0,
            ]);
            DB::table($table)->insert([
                'refer_code' => 0,
                'refer_table' => '',
                'credit_type' => 'W',
                'amount' => $items->cashback,
                'bonus' => 0,
                'total' => $items->cashback,
                'balance_before' => 0,
                'balance_after' => 0,
                'credit' => 0,
                'credit_bonus' => 0,
                'credit_total' => 0,
                'credit_before' => 0,
                'credit_after' => 0,
                'member_code' => $items->code,
                'kind' => 'CASHBACK',
                'auto' => 'N',
                'remark' => 'เคลียยอด Cashback จำนวน ' . $items->cashback . ' หมดเวลารับ',
                'emp_code' => 0,
                'ip' => request()->ip(),
                'amount_balance' => 0,
                'withdraw_limit' => 0,
                'withdraw_limit_amount' => 0,
                'user_create' => 'SYSTEM',
                'user_update' => 'SYSTEM',
                'date_create' => now()->toDateTimeString(),
                'date_update' => now()->toDateTimeString(),
            ]);
            $bar->advance();
        }
    }
}
