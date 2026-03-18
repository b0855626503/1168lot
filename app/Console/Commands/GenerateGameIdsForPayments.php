<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateGameIdsForPayments extends Command
{
    protected $signature = 'payment:generate-ids
                            {--force : บังคับสร้างแม้มีรายการค้างอยู่แล้ว}
                            {--account=20 : ใช้บัญชีธนาคาร (id) สำหรับลงรายการ}
                            {--sleep=1 : หน่วงเวลาต่อรายการ (วินาที)}';

    protected $description = 'เพิ่มรายการฝากเงินให้สมาชิกที่มียอดคงเหลือ (อัตโนมัติผ่านระบบ)';

    public function handle()
    {
        $force     = (bool) $this->option('force');
        $accountId = (int) $this->option('account');

        $sleepSec  = max(0, (int) $this->option('sleep')); // ดีฟอลต์ 5 วิ

        $this->info("เริ่มสร้างรายการฝากเงิน… (sleep {$sleepSec}s/รายการ)");

        // resolve repo แค่ครั้งเดียว
        $bankAccountRepo = app('Gametech\Payment\Repositories\BankAccountRepository');
        $bankRepo        = app('Gametech\Payment\Repositories\BankRepository');
        $paymentRepo     = app('Gametech\Payment\Repositories\BankPaymentRepository');

        $bankAccount = $bankAccountRepo->find($accountId);
        if (!$bankAccount) {
            $this->error("ไม่พบบัญชีธนาคาร account_id={$accountId}");
            return self::FAILURE;
        }

        $bank = $bankRepo->find($bankAccount->banks);
        if (!$bank) {
            $this->error("ไม่พบบัญชีธนาคารหลัก (banks={$bankAccount->banks})");
            return self::FAILURE;
        }

        $q = DB::table('members_real')
            ->select(['code', 'user_name', 'balance', 'enable', 'refund'])
            ->where('enable', 'Y')

            ->where('balance', '>', 1);


        // ถ้าไม่ force ก็ประมวลผลเฉพาะที่ยังไม่ refund
        if (!$force) {
            $q->where('refund', 'N');
        }

        $created = 0;
        $skipped = 0;
        $failed  = 0;

        $q->orderBy('code')->chunk(500, function ($members) use (
            $force, $bank, $bankAccount, $paymentRepo, $sleepSec, &$created, &$skipped, &$failed
        ) {
            foreach ($members as $member) {
                $amount = (float) $member->balance;

                // ข้ามถ้ามี pending อยู่แล้ว (force = false เท่านั้น)
                if (!$force) {
                    $hasPending = DB::table('bank_payment')
                        ->where('member_topup', $member->code)
                        ->where('status', 0)             // pending
                        ->where('create_by', 'SYSAUTO')   // เราสร้างเอง
                        ->exists();

                    if ($hasPending) {
                        $skipped++;
                        $this->line("ข้าม {$member->user_name} ({$member->code}) — มีรายการค้างอยู่แล้ว");
                        continue;
                    }
                }

                $now  = now();
                $hash = md5(implode('|', [
                    $bankAccount->code, $now->toIso8601String(), $amount, $member->code
                ]));

                $detail = 'เงินพิเศษให้สมาชิก ' . $member->user_name . ' — เพิ่มโดย System';

                $data = [
                    'bank'          => strtolower($bank->shortcode . '_' . $bankAccount->acc_no),
                    'detail'        => $detail,
                    'account_code'  => $bankAccount->code,
                    'autocheck'     => 'W',
                    'bankstatus'    => 1,
                    'bank_name'     => $bank->shortcode,
                    'bank_time'     => $now->toDateTimeString(),
                    'channel'       => 'MANUAL',
                    'value'         => $amount,
                    'tx_hash'       => $hash,
                    'status'        => 0,
                    'ip_admin'      => '127.0.0.1', // console
                    'member_topup'  => $member->code,
                    'remark_admin'  => 'คืนเงินให้ลูกค้า',
                    'emp_topup'     => 0,
                    'user_create'   => 'สร้างโดย System (CLI)',
                    'create_by'     => 'SYSAUTO',
                ];

                try {
                    $response = $paymentRepo->create($data);

                    // สมมติว่าถ้าสำเร็จจะได้ object/truthy (ปรับตามจริงได้)
                    if ($response && ($response->code ?? true)) {
                        // อัปเดต refund เฉพาะกรณีที่เดิมเป็น N
                        if ($member->refund === 'N') {
                            DB::table('members_real')
                                ->where('code', $member->code)
                                ->update(['refund' => 'Y']);
                        }

                        $created++;
                        $this->info("✔ ฝากเงินให้: {$member->user_name} ({$member->code}) = " . number_format($amount, 2));
                        if ($sleepSec > 0) { sleep($sleepSec); }
                    } else {
                        $failed++;
                        $this->warn("✖ ไม่สำเร็จ: {$member->user_name} ({$member->code})");
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
