<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;

class BatchUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:topup';

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
        $this->info('Auto Topup Start');
        $payments = $this->bankPaymentRepository->loadPayment();

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        foreach ($payments as $i => $payment) {
            $alllog = $this->allLogRepository->where('bank_payment_id', $payment['code']);

            if ($alllog->count() == 0) {
                $rechk = $this->bankPaymentRepository->findOneWhere(['code' => $payment['code'], 'status' => 0, 'autocheck' => 'W']);

                if ($rechk->doesntExist()) {
                    $bar->advance();
                    continue;
                }

                $this->paymentPromotionRepository->checkFastStart($payment->amount,$payment->member_topup,$payment->code);

                $this->bankPaymentRepository->refillPayment($payment);
                $bar->advance();
            } else {
                $payment->autocheck = 'Y';
                $payment->remark_admin = 'ตรวจสอบ All Log พบว่า มีรายการเติมไปแล้ว';
                $payment->user_update = 'AUTO';
                $payment->save();
                $bar->advance();
            }
        }

        $bar->finish();
    }
}
