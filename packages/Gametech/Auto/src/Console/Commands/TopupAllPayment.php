<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;
use Gametech\Auto\Jobs\TopupAllPayments as TopupAllPaymentsJob;

class TopupAllPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:topup-all {limit=10}';

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

        TopupAllPaymentsJob::dispatch($limit)->onQueue('topup');
        TopupAllPaymentsJob::dispatch($limit)->delay(now()->addSeconds(30))->onQueue('topup');
//        TopupAllPaymentsJob::dispatch($limit)->delay(now()->addSeconds(40))->onQueue('topup');
    }


}
