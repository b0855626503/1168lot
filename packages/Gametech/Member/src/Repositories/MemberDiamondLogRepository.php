<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberDiamondLogRepository extends Repository
{
    private $memberRepository;

    private $memberCreditLogRepository;

    public function __construct(
        MemberRepository $memberRepo,
        MemberCreditLogRepository $memberCreditLogRepo,
        App $app
    ) {
        $this->memberRepository = $memberRepo;
        $this->memberCreditLogRepository = $memberCreditLogRepo;
        parent::__construct($app);
    }

    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return \Gametech\Member\Models\MemberDiamondLog::class;
    }

    public function setDiamond(array $data): bool
    {
        // ตามเดิม: ใช้ ip จาก request เท่านั้น (ไม่รับส่งจาก caller)
        $ip = request()->ip();

        $member_code = $data['member_code'] ?? null;
        $amount      = isset($data['amount']) ? (int) $data['amount'] : 0;
        $method      = $data['method'] ?? null; // D/W
        $remark      = $data['remark'] ?? '';
        $emp_code    = $data['emp_code'] ?? 0;
        $emp_name    = $data['emp_name'] ?? '';

        // guard input (กันข้อมูลเพี้ยนเงียบ ๆ)
        if (! $member_code || $amount <= 0 || ! in_array($method, ['D', 'W'], true)) {
            return false;
        }

        DB::beginTransaction();

        try {
            // โหลด member (ใช้เพื่อ user_name และ table name)
            $member = $this->memberRepository->find($member_code);
            if (! $member) {
                DB::rollBack();
                return false;
            }

            $memberTable = method_exists($member, 'getTable') ? $member->getTable() : 'members';

            // ===== กันกดซ้อน / หลายแท็บ: lock แถว member ก่อนคำนวณ =====
            $row = DB::table($memberTable)
                ->where('code', $member_code)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::rollBack();
                return false;
            }

            // ✅ ยอดเพชร ก่อน-หลัง (ตามที่ต้องการ)
            $diamond_before = (int) ($row->diamond ?? 0);

            if ($method === 'D') {
                $diamond_after = $diamond_before + $amount;
            } else { // W
                $diamond_after = $diamond_before - $amount;
                if ($diamond_after < 0) {
                    DB::rollBack();
                    return false;
                }
            }

            // 1) สร้างบิล log เพชร
            $bill = $this->create([
                'diamond_type'    => $method,
                'diamond_amount'  => $amount,
                'diamond_before'  => $diamond_before,
                'diamond_balance' => $diamond_after,
                'member_code'     => $member_code,
                'remark'          => $remark,
                'emp_code'        => $emp_code,
                'ip'              => $ip,
                'user_create'     => $emp_name,
                'user_update'     => $emp_name,
            ]);

            // 2) อัปเดตยอดเพชรใน members แบบ atomic ภายใต้ lock
            DB::table($memberTable)
                ->where('code', $member_code)
                ->update(['diamond' => $diamond_after]);

            // 3) Ledger กลาง: บันทึกเป็น “ยอดเพชร ก่อน-หลัง”
            $this->memberCreditLogRepository->create([
                'refer_code'     => $bill->code,
                'refer_table'    => 'members_diamondlog',
                'credit_type'    => $method,
                'amount'         => $amount,

                // ✅ ใช้ยอดเพชร ก่อน-หลัง ตามที่ต้องการ
                'balance_before' => $diamond_before,
                'balance_after'  => $diamond_after,

                // ช่องเครดิตเงินจริงไม่เกี่ยว -> คงเดิมให้เป็น 0
                'bonus'          => 0,
                'total'          => $amount,
                'credit'         => 0,
                'credit_bonus'   => 0,
                'credit_total'   => 0,
                'credit_before'  => 0,
                'credit_after'   => 0,

                'member_code'    => $member_code,
                'user_name'      => $member->user_name,
                'kind'           => 'SETDIAMOND', // ✅ ชัดเจนว่าเป็นเล่มเพชร
                'auto'           => 'N',
                'remark'         => $remark,
                'emp_code'       => $emp_code,
                'ip'             => $ip,
                'user_create'    => $emp_name,
                'user_update'    => $emp_name,
            ]);

            DB::commit();
            return true;

        } catch (Throwable $e) {
            DB::rollBack();
            report($e);
            return false;
        }
    }
}
