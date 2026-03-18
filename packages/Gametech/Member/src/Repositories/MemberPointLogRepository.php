<?php

namespace Gametech\Member\Repositories;

use Gametech\Core\Eloquent\Repository;
use Illuminate\Container\Container as App;
use Illuminate\Support\Facades\DB;
use Throwable;

class MemberPointLogRepository extends Repository
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
     *
     * @return string
     */
    public function model(): string
    {
        return \Gametech\Member\Models\MemberPointLog::class;
    }

    public function setPoint(array $data): bool
    {
        // ตามเดิม: ไม่รับ ip จาก caller
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
            /** @var \Gametech\Member\Models\Member|null $member */
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

            // ✅ ยอด POINT ก่อน-หลัง (point_deposit)
            $point_before = (int) ($row->point_deposit ?? 0);

            if ($method === 'D') {
                $point_after = $point_before + $amount;
            } else { // W
                $point_after = $point_before - $amount;
                if ($point_after < 0) {
                    DB::rollBack();
                    return false;
                }
            }

            // 1) สร้างบิล log point
            $bill = $this->create([
                'point_type'    => $method,
                'point_amount'  => $amount,
                'point_before'  => $point_before,
                'point_balance' => $point_after,
                'member_code'   => $member_code,
                'remark'        => $remark,
                'emp_code'      => $emp_code,
                'ip'            => $ip,
                'user_create'   => $emp_name,
                'user_update'   => $emp_name,
            ]);

            // 2) อัปเดต point_deposit ใน members แบบ atomic ภายใต้ lock
            DB::table($memberTable)
                ->where('code', $member_code)
                ->update(['point_deposit' => $point_after]);

            // 3) Ledger กลาง: บันทึกเป็น “ยอด POINT ก่อน-หลัง”
            $this->memberCreditLogRepository->create([
                'refer_code'     => $bill->code,
                'refer_table'    => 'members_point_log',
                'credit_type'    => $method,
                'amount'         => $amount,
                'bonus'          => 0,
                'total'          => $amount,

                // ✅ ใช้ยอด POINT ก่อน-หลัง ตามที่ต้องการ
                'balance_before' => $point_before,
                'balance_after'  => $point_after,

                // ช่องเครดิตเงินจริงไม่เกี่ยว -> คงเดิมให้เป็น 0
                'credit'         => 0,
                'credit_bonus'   => 0,
                'credit_total'   => 0,
                'credit_before'  => 0,
                'credit_after'   => 0,

                'member_code'    => $member_code,
                'user_name'      => $member->user_name,
                'kind'           => 'SETPOINT', // ✅ ชัดเจนว่าเป็นเล่มแต้ม (ปรับจาก SETPOINT ให้ตรงความหมาย)
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
