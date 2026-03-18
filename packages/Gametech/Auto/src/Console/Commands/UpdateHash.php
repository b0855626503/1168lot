<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;

class UpdateHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'true:hash';

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
        $payments = app('Gametech\Payment\Repositories\BankPaymentRepository')->scopeQuery(function($query){
            return $query->orderBy('code','desc')->where('bankname','BAY')->whereDate('date_create','2021-11-17')->limit(500);
        })->all();

        $bar = $this->output->createProgressBar($payments->count());
        $bar->start();

        foreach ($payments as $i => $payment) {
            $hash = md5($payment->bank_time . $payment->value . $payment->detail . $payment->atranferer);
            $payment->tx_hash = $hash;
            $payment->save();
            $bar->advance();
        }
        $bar->finish();

        return true;

    }
}
