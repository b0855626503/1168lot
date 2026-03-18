<?php

namespace Gametech\Auto\Console\Commands;

use Gametech\Auto\Jobs\TopupPayments as TopupPaymentsJob;
use Illuminate\Console\Command;

class TopupPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:emp-topup {limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto Topup From Payment To Member';

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
        $this->allLogRepository = app('Gametech\Core\Repositories\AllLogRepository');
        $this->paymentPromotionRepository = app('Gametech\Payment\Repositories\PaymentPromotionRepository');
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $limit = $this->argument('limit');

        $this->info('Auto Topup Start');
        $payments = $this->bankPaymentRepository->loadPayment($limit);

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();


        foreach ($payments as $i => $payment) {
            TopupPaymentsJob::dispatch($payment->code)->delay(now()->addSeconds(5))->onQueue('topup');
            $bar->advance();
        }

        $bar->finish();
    }

}
