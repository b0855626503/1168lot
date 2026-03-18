<?php

namespace Gametech\Auto\Console\Commands;


use Gametech\Auto\Jobs\TopupFastStart;
use Illuminate\Console\Command;


class CheckFastStart extends Command
{
    protected $bankPaymentRepository;

    protected $paymentPromotionRepository;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faststart:date {date}';

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
        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->paymentPromotionRepository = app('Gametech\Payment\Repositories\PaymentPromotionRepository');

        parent::__construct();

    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $date = $this->argument('date');

        $payments = $this->bankPaymentRepository->scopeQuery(function ($query) use ($date) {
            return $query->orderBy('code', 'asc')
                ->whereDate('date_topup',$date)
                ->where('bankstatus',1)
                ->where('status',1)
                ->where('member_topup','<>',0);
        })->all();

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        foreach ($payments as $i => $payment) {
            TopupFastStart::dispatch($payment->code)->delay(now()->addSeconds(10))->onQueue('topup');
            $bar->advance();
        }

        $bar->finish();

    }

}
