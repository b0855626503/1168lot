<?php

namespace Gametech\Auto\Console\Commands;

use Illuminate\Console\Command;
use Gametech\Auto\Jobs\CheckTopups as CheckTopupJob;

class CheckTopup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment:check-all {bank} {limit=10}';

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
        $this->bankAccountRepository = app('Gametech\Payment\Repositories\BankAccountRepository');
        $this->memberRepository = app('Gametech\Member\Repositories\MemberRepository');
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

        CheckTopupJob::dispatch($bank,$limit)->onQueue('check-topup');
        CheckTopupJob::dispatch($bank,$limit)->delay(now()->addSeconds(30))->onQueue('check-topup');
//        CheckTopupJob::dispatch($bank,$limit)->delay(now()->addSeconds(60))->onQueue('check-topup');

    }


}
