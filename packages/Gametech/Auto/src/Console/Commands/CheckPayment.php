<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\CheckPayments as CheckPaymentsJob;
use Illuminate\Console\Command;

class CheckPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check {bank} {limit=10}';

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
        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->memberRepository = app('Gametech\Member\Repositories\MemberRepository');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $id = $this->argument('bank');
        $limit = $this->argument('limit');

        $this->info('Auto Check Topup BANK : ' . $id);
        $payments = $this->bankPaymentRepository->checkPayment($limit, $id);

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();


        foreach ($payments as $i => $payment) {
            CheckPaymentsJob::dispatch($id, $payment)->onQueue('topup');
            $bar->advance();

        }

        $bar->finish();

//        $this->call("payment:emp-topup 50");
        return 0;
    }


}
