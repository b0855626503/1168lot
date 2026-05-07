<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\PaymentBay;
use Gametech\Auto\Jobs\PaymentGsb;
use Gametech\Auto\Jobs\PaymentKbank;
use Gametech\Auto\Jobs\PaymentKtb;
use Gametech\Auto\Jobs\PaymentScb;
use Gametech\Auto\Jobs\PaymentTrue;
use Gametech\Auto\Jobs\PaymentWing;
use Illuminate\Console\Command;

class GetPaymentAcc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:get-account {bank} {account}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Transaction and insert to Bank Payment By Bank Account';

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
        $id = $this->argument('bank');
        $account = $this->argument('account');

        $this->info('Start get Transaction by : '.$id.' , Account No : '.$account);
        switch ($id) {
            case 'tw':
                PaymentTrue::dispatch($account)->onQueue($id);
                break;
            case 'kbank':
                PaymentKbank::dispatch($account)->onQueue($id);
                break;
            case 'bay':
                PaymentBay::dispatch($account)->onQueue($id);
                break;
            case 'ktb':
                PaymentKtb::dispatch($account)->onQueue('bank');
                break;
            case 'scb':
                PaymentScb::dispatch($account)->onQueue('bank');
                break;
            case 'gsb':
                PaymentGsb::dispatch($account)->onQueue('bank');
                break;
            case 'wing':
                PaymentWing::dispatch($account)->onQueue('scb');
                break;
        }

        //        $this->call("payment:check $id 50");
        return 0;

    }
}
