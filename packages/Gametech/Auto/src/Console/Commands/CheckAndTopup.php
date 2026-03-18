<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\CheckPayments as CheckPaymentsJob;
use Gametech\Auto\Jobs\TopupPayments as TopupPaymentsJob;
use Illuminate\Console\Command;


class CheckAndTopup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-topup {bank} {limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check Bank Payment prepare for refill to user';

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
        $limit = $this->argument('limit');
        $bank = $this->argument('bank');

        $this->info('Auto Check Topup BANK : ' . $bank);
        $payments = app('Gametech\Payment\Repositories\BankPaymentRepository')->checkPayment($limit, $bank);

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();


        foreach ($payments as $i => $payment) {

            $check = CheckPaymentsJob::dispatch($bank, $payment)->onQueue('topup');;
            if($check){
                TopupPaymentsJob::dispatch($payment->code)->onQueue('topup');;
            }

            $bar->advance();

        }

        $bar->finish();

    }


}
