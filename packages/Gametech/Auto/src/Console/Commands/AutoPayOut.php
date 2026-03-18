<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\AutoPayOutNormal as AutoPayOutNormalJob;
use Gametech\Payment\Models\BankAccount;
use Gametech\Payment\Models\Withdraw;
use Gametech\Payment\Models\WithdrawSeamless;
use Illuminate\Console\Command;
use Gametech\Auto\Jobs\AutoPayOutSeamless as AutoPayOutSeamlessJob;

class AutoPayOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Transaction and insert to Bank Payment By Bank Account';

    protected $config;
    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->config = core()->getConfigData();
        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $config = $this->config;
        $bank = BankAccount::where('auto_transfer', 'Y')->where('status_auto', 'Y')->where('enable', 'Y')->where('bank_type', 2)->first();
        if (isset($bank)) {

            if($config->seamless == 'Y') {
                $data = WithdrawSeamless::where('enable', 'Y')->where('status', 0)->where('status_withdraw', 'W')->where('emp_approve', 0)->whereBetween('amount', [$bank->min_amount, $bank->max_amount])->orderByDesc('code')->get();
            }else{
                $data = Withdraw::where('enable', 'Y')->where('status', 0)->where('status_withdraw', 'W')->where('emp_approve', 0)->whereBetween('amount', [$bank->min_amount, $bank->max_amount])->orderByDesc('code')->get();
            }

            $bar = $this->output->createProgressBar($data->count());
            $bar->start();
            foreach ($data as $item) {
                if($config->seamless == 'Y') {
                    AutoPayOutSeamlessJob::dispatch($bank, $item)->onQueue('topup');
                }else{
                    AutoPayOutNormalJob::dispatch($bank, $item)->onQueue('topup');
                }

                $bar->advance();
            }

            $bar->finish();
        }

    }


}
