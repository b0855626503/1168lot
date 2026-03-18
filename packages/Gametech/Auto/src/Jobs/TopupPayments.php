<?php

namespace Gametech\Auto\Jobs;

use Gametech\Core\Models\AllLogProxy;
use Gametech\Payment\Repositories\BankAccountRepository;
use Gametech\Payment\Repositories\BankPaymentRepository;
use Gametech\Payment\Repositories\PaymentPromotionRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class TopupPayments implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** ระยะเวลาที่ job นี้ถือสิทธิ์ uniqueness (วินาที) */
    public int $uniqueFor = 30;

    /** timeout ระดับ job (วินาที) */
    public int $timeout = 180;

    /** จำนวน retry ทั้งหมด (กำหนดให้ชัดเจน เลี่ยง default ไม่สิ้นสุด) */
    public int $tries = 1;

    /** จำนวน exception สูงสุดก่อนถือว่าล้มเหลว */
    public int $maxExceptions = 3;

    /** payment primary key (code) */
    protected string|int $paymentId;

    /** ค่าคอนฟิกระบบ (snapshot ตอน dispatch) */
    protected object $config;

    public function __construct(string|int $payment)
    {
        $this->paymentId = $payment;
        $this->config = core()->getConfigData();
    }

    public function tags(): array
    {
        return ['render', 'topup:'.$this->paymentId];
    }

    public function uniqueId(): string
    {
        return (string) $this->paymentId;
    }

    /**
     * กันซ้อนกันจริงระดับ queue worker
     * - expireAfter ควรมากกว่า timeout เล็กน้อย
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping($this->uniqueId()))
                ->expireAfter($this->timeout + 30),
        ];
    }

    public function handle(): bool
    {
        /** @var BankPaymentRepository $bankPaymentRepo */
        $bankPaymentRepo = app(BankPaymentRepository::class);
        /** @var PaymentPromotionRepository $paymentPromoRepo */
        $paymentPromoRepo = app(PaymentPromotionRepository::class);
        /** @var BankAccountRepository $bankAccountRepo */
        $bankAccountRepo = app(BankAccountRepository::class);

        $payment = $bankPaymentRepo->findOneByField('code', $this->paymentId);
        if (! $payment) {
            // ไม่พบรายการ → จบแบบเงียบ ๆ
            return false;
        }

        // ── 1) Idempotent guard: ถ้าสถานะไม่ใช่ "รอเติม" หรือยังไม่ผ่าน autocheck → ไม่ต้องทำต่อ
        // status: 0=ยังไม่เติม, 1=เติมแล้ว, 2=ปฏิเสธ
        if ((int) $payment->status !== 0 || (string) $payment->autocheck !== 'W') {
            return true; // ถือว่าผ่าน (ไม่มีอะไรต้องทำ)
        }

        // ── 1.1) deposit_status guard: ให้ repo เป็นคนตัดสิน (มี stale window + assume logic)
        // หมายเหตุ: ห้ามตัดทิ้งที่ job เพราะเคส DEPOSITING stale ต้องให้ repo เข้าไป finalize-only ได้
        $ds = strtoupper((string) ($payment->deposit_status ?? 'NEW'));

        // ── 2) ตรวจขั้นต่ำการฝาก (เฉพาะกรณียังไม่มี emp_topup)
        if ((int) $payment->emp_topup === 0) {
            $bankAccount = $bankAccountRepo->findOneByField('code', $payment->account_code);

            // ถ้าหาบัญชีไม่เจอ → กันพังและถือว่าเคสไม่พร้อมประมวลผล
            if (! $bankAccount) {
                // ไม่แก้สถานะ เพื่อให้แก้ข้อมูลแล้วรันใหม่ได้
                return false;
            }

            $min = (float) ($bankAccount->deposit_min > 0
                ? $bankAccount->deposit_min
                : ($this->config->deposit_min ?? 0));

            if ($min > 0 && (float) $payment->value < $min) {
                // ปิดการประมวลผลอัตโนมัติของรายการนี้ เนื่องจากต่ำกว่าขั้นต่ำ
                $payment->autocheck = 'Y';
                $payment->remark_admin = 'ยอดฝากไม่ถึงขั้นต่ำ ('.$min.')';
                $payment->topup_by = 'System Auto';
                $payment->saveQuietly();

                return false;
            }
        }

        /**
         * ── 3) เช็ค AllLogProxy แบบ "ฉลาดขึ้น"
         * เดิม: เจอ allLog => mark payment สำเร็จทันที (เสี่ยงปิดเคส orphan)
         * ใหม่: เจอ allLog => ต้องมีหลักฐานความสมบูรณ์ (member_credit_logs หรือ bills) ก่อน
         * - ถ้าสมบูรณ์ => mark สำเร็จ
         * - ถ้าไม่สมบูรณ์ => ลบ allLog orphan แล้วปล่อยให้ refill ทำต่อ
         */
        $hasLog = AllLogProxy::where('bank_payment_id', $payment->code)->exists();
        $ds = strtoupper((string) ($payment->deposit_status ?? 'NEW'));

        if ($hasLog) {
            $hasCreditLog = DB::table('member_credit_logs')
                ->where('refer_code', $payment->code)
                ->where('refer_table', 'bank_payment')
                ->where('kind', 'TOPUP')
                ->exists();

            $hasBill = DB::table('bills')
                ->where('refer_code', $payment->code)
                ->where('refer_table', 'bank_payment')
                ->exists();

            if ($hasCreditLog || $hasBill) {
                // สมบูรณ์จริง ค่อยปิดเคส
                $payment->autocheck = 'Y';
                $payment->status = 1;
                $payment->saveQuietly();

                return true;
            }

            // orphan: มี allLog แต่ไม่พบหลักฐานว่าเติมจบจริง
            // - ถ้าค่ายฝากสำเร็จแล้ว (DEPOSITED/FINALIZING) ห้ามลบ ให้ปล่อยให้ refill finalize ต่อ
            if (in_array($ds, ['DEPOSITED', 'FINALIZING', 'FINALIZED'], true)) {
                return true;
            }

            // - ถ้ายังไม่ฝากสำเร็จจริง ค่อยลบเพื่อให้ทำต่อได้
            try {
                AllLogProxy::where('bank_payment_id', $payment->code)->delete();
            } catch (Throwable $e) {
                report($e);

                // ถ้าลบไม่ได้ อย่าไปปิด status=1 เด็ดขาด
                return false;
            }
        }

        // ── 4) ทางแยกเติมเครดิตตามโหมดระบบ
        // หมายเหตุ: repository เหล่านี้ควรมี idempotency ภายใน (กันเติมซ้ำในระดับ DB)
        if ((string) $this->config->seamless === 'Y') {
            $paymentPromoRepo->checkFastStartSeamless(
                (float) $payment->value,
                (int) $payment->member_topup,
                (string) $payment->code
            );
            $bankPaymentRepo->refillPaymentSeamless(collect($payment)->toArray());

            return true;
        }

        if ((string) $this->config->multigame_open === 'Y') {
            $paymentPromoRepo->checkFastStart(
                (float) $payment->value,
                (int) $payment->member_topup,
                (string) $payment->code
            );
            $bankPaymentRepo->refillPayment(collect($payment)->toArray());

            return true;
        }

        // single
        $paymentPromoRepo->checkFastStartSingle(
            (float) $payment->value,
            (int) $payment->member_topup,
            (string) $payment->code
        );
        $bankPaymentRepo->refillPaymentSingle(collect($payment)->toArray());

        return true;
    }

    public function failed(Throwable $e): void
    {
        // ส่งต่อให้ระบบ exception handler ของแอป
        report($e);
    }
}
