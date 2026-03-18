<?php

namespace App\Console\Commands;

use Gametech\Auto\Jobs\UpdateBalanceAPay;
use Gametech\Auto\Jobs\UpdateBalanceAutoTransfer;
use Gametech\Auto\Jobs\UpdateBalanceKingPay;
use Gametech\Auto\Jobs\UpdateBalanceMaxPay;
use Gametech\Auto\Jobs\UpdateBalancePayoneX;
use Gametech\Auto\Jobs\UpdateBalanceWellPay;
use Gametech\Auto\Jobs\UpdateBalanceWildPay;
use Illuminate\Console\Command;

class UpdatePaymentId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:balance {payment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $payment = $this->argument('payment');

        switch ($payment) {
            case 'kingpay':
                UpdateBalanceKingPay::dispatch()->onQueue('topup');
                break;

            case 'wellpay':
                UpdateBalanceWellpay::dispatch()->onQueue('topup');
                break;

            case 'wildpay':
                UpdateBalanceWildpay::dispatch()->onQueue('topup');
                break;

            case 'payonex':
                UpdateBalancePayoneX::dispatch()->onQueue('topup');
                break;
            case 'apay':
                UpdateBalanceAPay::dispatch()->onQueue('topup');
                break;
            case 'maxpay':
                UpdateBalanceMaxPay::dispatch()->onQueue('topup');
                break;
            case 'auto':
                UpdateBalanceAutoTransfer::dispatch()->onQueue('topup');
                break;
        }
        return 0;
    }
}
