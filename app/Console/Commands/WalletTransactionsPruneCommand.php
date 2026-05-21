<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletTransactionsPruneCommand extends Command
{
    protected $signature = 'wallet:prune-transactions
                            {--days=7 : เก็บข้อมูลย้อนหลังกี่วัน}
                            {--dry-run : แสดงจำนวนที่จะลบโดยไม่ลบจริง}';

    protected $description = 'ลบ wallet_transactions ที่เก่ากว่า N วัน';

    public function handle(): int
    {
        if (! Schema::hasTable('wallet_transactions')) {
            $this->warn('ไม่พบตาราง wallet_transactions');

            return 0;
        }

        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days);

        $count = DB::table('wallet_transactions')
            ->where('created_at', '<', $cutoff)
            ->count();

        $this->info("wallet_transactions เก่ากว่า {$days} วัน (ก่อน {$cutoff->toDateString()}): {$count} รายการ");

        if ($count === 0) {
            $this->info('ไม่มีรายการที่ต้องลบ');

            return 0;
        }

        if ($this->option('dry-run')) {
            $this->info('[dry-run] จะลบทั้งหมด '.$count.' รายการ แต่ไม่ได้ลบจริง');

            return 0;
        }

        $deleted = DB::table('wallet_transactions')
            ->where('created_at', '<', $cutoff)
            ->delete();

        $this->info("ลบแล้ว {$deleted} รายการ");

        return 0;
    }
}
