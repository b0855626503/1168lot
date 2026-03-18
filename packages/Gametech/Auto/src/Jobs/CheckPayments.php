<?php

namespace Gametech\Auto\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckPayments implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // ===== Queue options =====
    public $uniqueFor     = 30;  // กันซ้ำ 30 วิ ต่อ code
    public $timeout       = 60;
    public $tries         = 1;   // งานนี้ควร “ชี้ขาด” ถ้าพังจริงก็ให้ผู้อื่นจัดการ ไม่ต้อง retry ย้ำ
    public $maxExceptions = 0;
    public $retryAfter    = 0;

    // ===== Dependencies (raw) =====
    protected $bank;   // shortcode เช่น 'kbank'
    protected $item;   // object ที่มี ->code (อาจเป็น model/DTO)

    // ===== Cached config =====
    protected $config;

    // ===== Repositories =====
    protected $bankPaymentRepository;
    protected $memberRepository;
    protected $bankAccountRepository;

    public function __construct($bank, $item)
    {
        $this->bank   = $bank;
        $this->item   = $item;
        $this->config = core()->getConfigData();
    }

    public function tags()
    {
        return ['render', 'check:' . $this->item->code];
    }

    public function uniqueId()
    {
        return $this->item->code;
    }

    public function handle(): bool
    {
        $this->wire();

        // ใช้ทรานแซกชัน + FOR UPDATE กันชนกับงานอื่น/แอดมิน
        /** @var DatabaseManager $db */
        $db = app('db');

        return $db->transaction(function () {

            // 1) โหลด payment พร้อม lock
            $payment = $this->paymentForUpdate($this->item->code);
            if (!$payment) {
                // ไม่มีข้อมูลให้จบแบบนิ่ม ๆ
                return false;
            }

            // 2) ถ้าไม่อยู่ใน “สภาพพร้อมตรวจ” แล้ว ให้ยุติ (idempotent)
            //    นิยาม “พร้อมตรวจ” (ข้อเสนอ): status=0 (ยังไม่เติม), autocheck='N', member_topup=0
            if (!$this->isCheckable($payment)) {
                return false;
            }

            // 3) ตรวจขั้นต่ำการฝาก
            if (!$this->passesMinDeposit($payment)) {
                // mark ว่า “ไม่ผ่านขั้นต่ำ” → ถือว่าจบกระบวนการ “ตรวจหา member” (autocheck='Y')
                $this->markAutoChecked($payment, 'ยอดฝากไม่ถึงขั้นต่ำ (' . $this->minDepositUsed($payment) . ')');
                return false;
            }

            // 4) หาสมาชิกจากรายละเอียดบัญชี
            $members = $this->memberRepository->loadAccount($this->bank, $payment);
            $count   = $members->count();

            if ($count === 0) {
                $this->markAutoChecked($payment, 'ไม่พบหมายเลขบัญชี');
                return false;
            }

            if ($count > 1) {
                $users = collect($members)->pluck('user_name')->implode(' , ');
                $this->markAutoChecked($payment, "พบหมายเลขบัญชี {$count} บัญชี : {$users}");
                return false;
            }

            // 5) เจอสมาชิกเดี่ยว → set member_topup + autocheck='W' (รอระบบเติมอัตโนมัติ)
            $member = $members->first();
            $payment->member_topup = (int)$member->code;
            $payment->autocheck    = 'W';
            $payment->remark_admin = 'รอระบบเติมอัตโนมัติ';
            $payment->topup_by     = 'System Auto';
            $payment->save(); // อย่าบันทึกแบบ quietly — ต้องการให้ Observer เห็น member_topup เปลี่ยน

            return true;
        }, 3); // retry deadlock สูงสุด 3 ครั้ง
    }

    // ─────────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────────

    protected function wire(): void
    {
        $this->bankPaymentRepository = app('Gametech\Payment\Repositories\BankPaymentRepository');
        $this->memberRepository      = app('Gametech\Member\Repositories\MemberRepository');
        $this->bankAccountRepository = app('Gametech\Payment\Repositories\BankAccountRepository');
    }

    /**
     * lock แถวสำหรับตัดสินใจอย่าง atomic
     */
    protected function paymentForUpdate($code)
    {
        // ถ้าเป็น Eloquent model repo:
        return $this->bankPaymentRepository->newQuery()
            ->where('code', $code)
            ->lockForUpdate()
            ->first();
    }

    /**
     * เกณฑ์ “พร้อมตรวจ” เพื่อกันซ้ำและลด side-effects
     * - status = 0  (ยังไม่เติม)
     * - autocheck = 'N' (ยังไม่ถูกตรวจ)
     * - member_topup = 0 (ยังไม่รู้ว่าเป็นใคร)
     */
    protected function isCheckable($payment): bool
    {
        return (int)$payment->status === 0
            && (string)$payment->autocheck === 'N'
            && (int)$payment->member_topup === 0;
    }

    protected function passesMinDeposit($payment): bool
    {
        $bankAccount = $this->bankAccountRepository->findOneWhere(['code' => $payment->account_code]);

        $min = 0;
        if ($bankAccount && (int)$bankAccount->deposit_min > 0) {
            $min = (int)$bankAccount->deposit_min;
        } elseif ($this->config && (int)$this->config->deposit_min > 0) {
            $min = (int)$this->config->deposit_min;
        }

        return (int)$payment->value >= $min;
    }

    protected function minDepositUsed($payment): int
    {
        $bankAccount = $this->bankAccountRepository->findOneWhere(['code' => $payment->account_code]);

        if ($bankAccount && (int)$bankAccount->deposit_min > 0) {
            return (int)$bankAccount->deposit_min;
        }
        if ($this->config && (int)$this->config->deposit_min > 0) {
            return (int)$this->config->deposit_min;
        }
        return 0;
    }

    /**
     * สถานะ “ตรวจแล้ว” แต่ “ไม่เติมต่อ” (เช่น ไม่ถึงขั้นต่ำ / ไม่พบเลขบัญชี / พบหลายบัญชี)
     * - ตั้ง autocheck='Y'
     * - remark_admin อธิบายเหตุผล
     */
    protected function markAutoChecked($payment, string $reason): void
    {
        $payment->autocheck    = 'Y';
        $payment->remark_admin = $reason;
        $payment->topup_by     = 'System Auto';
        $payment->save(); // save ปกติให้ Observer นับ/บรอดแคสต์ได้ตามจริง
    }
}
