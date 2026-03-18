<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateGameIdsForEvents extends Command
{
    protected $signature = 'event:generate-ids
                            {--force : บังคับสร้างแม้มีรายการค้างอยู่แล้ว}
                            {--account=20 : ใช้บัญชีธนาคาร (id) สำหรับลงรายการ}
                            {--sleep=1 : หน่วงเวลาต่อรายการ (วินาที)}';

    protected $description = 'เพิ่มรายการฝากเงินให้สมาชิกที่มียอดคงเหลือ (อัตโนมัติผ่านระบบ)';

    public function handle()
    {
        $force     = (bool) $this->option('force');
        $accountId = (int) $this->option('account');

        $sleepSec  = max(0, (int) $this->option('sleep')); // ดีฟอลต์ 5 วิ

        $this->info("เริ่มสร้างรายการ Event… (sleep {$sleepSec}s/รายการ)");

        // resolve repo แค่ครั้งเดียว
//        $bankAccountRepo = app('Gametech\Payment\Repositories\BankAccountRepository');
//        $bankRepo        = app('Gametech\Payment\Repositories\BankRepository');
        $gameUserEventRepo     = app('Gametech\Game\Repositories\GameUserEventRepository');

//        $bankAccount = $bankAccountRepo->find($accountId);
//        if (!$bankAccount) {
//            $this->error("ไม่พบบัญชีธนาคาร account_id={$accountId}");
//            return self::FAILURE;
//        }
//
//        $bank = $bankRepo->find($bankAccount->banks);
//        if (!$bank) {
//            $this->error("ไม่พบบัญชีธนาคารหลัก (banks={$bankAccount->banks})");
//            return self::FAILURE;
//        }

        $q = DB::table('members')
            ->select(['code', 'user_name', 'balance', 'enable', 'cashback'])
            ->where('cashback','>',0)
            ->where('enable', 'Y');


        // ถ้าไม่ force ก็ประมวลผลเฉพาะที่ยังไม่ refund
//        if (!$force) {
//            $q->where('refund', 'N');
//        }

        $created = 0;
        $skipped = 0;
        $failed  = 0;

        $q->orderBy('code')->chunk(500, function ($members) use (
            $force, $gameUserEventRepo, $sleepSec, &$created, &$skipped, &$failed
        ) {
            foreach ($members as $member) {
                $amount = (float) $member->cashback;

                // ข้ามถ้ามี pending อยู่แล้ว (force = false เท่านั้น)

                    $hasPending = DB::table('games_user_event')
                        ->where('member_code', $member->code)
                        ->where('method', 'CASHBACK')             // pending
                        ->exists();

                    if ($hasPending) {
                        $skipped++;
                        $this->line("ข้าม {$member->user_name} ({$member->code}) — มีรายการค้างอยู่แล้ว");
                        continue;
                    }

                $now  = now();


                $data = [

                    'game_code'  => 1,
                    'member_code'     => $member->code,
                    'user_name'     => $member->user_name,
                    'method'    => 'CASHBACK',
                    'pro_code'    => 7,
                ];

                try {
                    $response = $gameUserEventRepo->create($data);

                    // สมมติว่าถ้าสำเร็จจะได้ object/truthy (ปรับตามจริงได้)
                    if ($response && ($response->code ?? true)) {
                        // อัปเดต refund เฉพาะกรณีที่เดิมเป็น N
//                        if ($member->refund === 'N') {
//                            DB::table('members_cb')
//                                ->where('code', $member->code)
//                                ->update(['refund' => 'Y']);
//                        }

                        $created++;
                        $this->info("✔ สร้างอีเว้นแฃ้ง: {$member->user_name} ({$member->code}) = " . number_format($amount, 2));
                        if ($sleepSec > 0) { sleep($sleepSec); }
                    } else {
                        $failed++;
                        $this->warn("✖สร้างอีเว้นแฃ้ง  ไม่สำเร็จ: {$member->user_name} ({$member->code})");
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $this->error("⚠️  Error {$member->user_name} ({$member->code}): {$e->getMessage()}");
                    if ($sleepSec > 0) { sleep($sleepSec); }
                }
            }
        });

        $this->info("เสร็จแล้ว — สร้างใหม่ {$created} | ข้าม {$skipped} | ล้มเหลว {$failed}");

        return self::SUCCESS;
    }
}
