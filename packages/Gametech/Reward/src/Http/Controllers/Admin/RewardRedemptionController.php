<?php

namespace Gametech\Reward\Http\Controllers\Admin;

use Gametech\Admin\Http\Controllers\AppBaseController;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Reward\DataTables\RewardRedemptionDataTable;
use Gametech\Reward\Models\RewardRedemption;
use Gametech\Reward\Repositories\RewardListRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RewardRedemptionController extends AppBaseController
{
    protected $_config;

    /**
     * เดิมของคุณใช้ RewardListRepository (ยังเก็บไว้เพื่อไม่กระทบหน้าอื่นที่อาจอ้าง)
     * แต่ loadData/process เราจะใช้ RewardRedemption model ตรง ๆ ให้ถูก
     */
    protected $repository;

    protected $memberRepository;

    // ✅ ระบบกลางเติมเครดิต + สร้าง log
    protected $memberCreditLogRepository;

    public function __construct(
        RewardListRepository $repository,
        MemberRepository $memberRepository
    ) {
        $this->_config = request('_config');

        $this->middleware('admin');

        $this->repository = $repository;
        $this->memberRepository = $memberRepository;

        // ✅ resolve repo กลางแบบ lazy (เหมือนที่คุณทำใน Wallet controller)
        $this->memberCreditLogRepository = app()->bound('memberCreditLogRepository')
            ? app('memberCreditLogRepository')
            : (class_exists(\Gametech\Member\Repositories\MemberCreditLogRepository::class)
                ? app(\Gametech\Member\Repositories\MemberCreditLogRepository::class)
                : null);
    }

    public function index(RewardRedemptionDataTable $rewardRedemptionDataTable)
    {
        return $rewardRedemptionDataTable->render($this->_config['view']);
    }

    /**
     * loadData สำหรับ modal “ดำเนินการ” (คืนข้อมูล redemption + member + snapshot)
     */
    public function loadData(Request $request)
    {
        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->sendError('กรุณาระบุ id', 200);
        }

        $row = RewardRedemption::query()
            ->from('reward_redemptions')
            ->leftJoin('members', 'members.code', '=', 'reward_redemptions.member_id')
            ->select([
                'reward_redemptions.*',

                // member fields (เพื่อให้ทีมงานเห็นว่าใครแลก)
                'members.code as member_code',
                'members.user_name as member_username',
                'members.name as member_name',
                'members.tel as member_tel',
            ])
            ->where('reward_redemptions.id', $id)
            ->first();

        if (! $row) {
            return $this->sendError('ไม่พบข้อมูลดังกล่าว', 200);
        }

        return $this->sendResponse($row, 'โหลดข้อมูลสำเร็จ');
    }

    /**
     * POST: process redemption (ทีมงานดำเนินการ / อนุมัติ / ปฏิเสธ)
     *
     * รองรับฟอร์มเดียวกัน:
     * - approval + wallet_credit: approved => auto เติมเครดิต => fulfilled
     * - manual/external: pending/fulfilled/cancelled/rejected + note_staff/ผลดำเนินการ
     */
    public function process(Request $request)
    {
        $admin = $this->user();
        $adminCode = (int) ($admin->code ?? 0);
        $adminName = trim((string) (($admin->name ?? '').' '.($admin->surname ?? '')));
        if ($adminName === '') $adminName = 'ADMIN';

        $id = (int) $request->input('id', 0);
        if ($id <= 0) {
            return $this->sendError('กรุณาระบุ id', 200);
        }

        $incomingStatus = trim((string) $request->input('status', ''));
        if ($incomingStatus === '') {
            return $this->sendError('กรุณาระบุ status', 200);
        }

        // whitelist status ที่อนุญาต
        $allowedStatuses = ['pending', 'approved', 'fulfilled', 'rejected', 'cancelled'];
        if (! in_array($incomingStatus, $allowedStatuses, true)) {
            return $this->sendError('สถานะไม่ถูกต้อง', 200);
        }

        // ฟิลด์ “ผลดำเนินการ” (เก็บลง note_staff/payload_snapshot ได้ตามที่คุณมีจริง)
        $noteStaff = trim((string) $request->input('note_staff', ''));
        $resultChannel = trim((string) $request->input('result_channel', ''));
        $resultRef = trim((string) $request->input('result_ref', ''));
        $resultNote = trim((string) $request->input('result_note', ''));

        $fulfilledAt = $request->input('fulfilled_at');
        $fulfilledAt = $fulfilledAt ? Carbon::parse($fulfilledAt, 'Asia/Bangkok') : null;

        $handledBy = $request->input('handled_by');
        $handledBy = ($handledBy === null || $handledBy === '') ? $adminCode : (int) $handledBy;

        try {
            $tz = 'Asia/Bangkok';
            $now = now($tz);

            $out = DB::transaction(function () use (
                $id,
                $incomingStatus,
                $noteStaff,
                $resultChannel,
                $resultRef,
                $resultNote,
                $fulfilledAt,
                $handledBy,
                $now,
                $tz,
                $adminCode,
                $adminName
            ) {
                /** @var RewardRedemption $red */
                $red = RewardRedemption::query()
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (! $red) {
                    return ['ok' => false, 'msg' => 'ไม่พบข้อมูลดังกล่าว'];
                }

                // กันเคสปิดงานแล้วโดนยิงซ้ำ (idempotent ฝั่งทีมงาน)
                if ($red->status === 'fulfilled' && $incomingStatus === 'fulfilled') {
                    return ['ok' => true, 'msg' => 'รายการนี้ดำเนินการเสร็จแล้ว'];
                }

                // เตรียม update base fields
                $upd = [
                    'status'     => $incomingStatus,
                    'handled_by' => $handledBy ?: null,
                    'updated_at' => $now,
                ];

                // note_staff: รวมข้อความให้อ่านง่าย + audit
                $chunks = [];
                if ($noteStaff !== '') $chunks[] = $noteStaff;

                // เก็บผลดำเนินการ “แบบไม่เพิ่มคอลัมน์ใหม่” โดยยัดลง note_staff ให้เลย (กัน migration)
                if ($resultChannel !== '' || $resultRef !== '' || $resultNote !== '') {
                    $chunks[] = '--- ผลดำเนินการ ---';
                    if ($resultChannel !== '') $chunks[] = "ช่องทาง: {$resultChannel}";
                    if ($resultRef !== '')     $chunks[] = "อ้างอิง: {$resultRef}";
                    if ($resultNote !== '')    $chunks[] = "หมายเหตุ: {$resultNote}";
                }

                // ใส่ audit ว่าใครกด
                $chunks[] = "ดำเนินการโดย: {$adminName} (#{$adminCode}) @ ".$now->format('Y-m-d H:i:s');

                $mergedNote = trim(implode("\n", array_filter($chunks)));
                if ($mergedNote !== '') {
                    // ถ้ามีของเดิมอยู่แล้ว ให้ต่อท้าย
                    $prev = (string) ($red->note_staff ?? '');
                    $upd['note_staff'] = $prev !== '' ? ($prev."\n\n".$mergedNote) : $mergedNote;
                }

                // ถ้าทีมงาน set fulfilled เอง
                if ($incomingStatus === 'fulfilled') {
                    $upd['fulfilled_at'] = $fulfilledAt ?: $now;
                }

                if ($incomingStatus === 'rejected') {
                    $upd['rejected_at'] = $now;
                }

                if ($incomingStatus === 'cancelled') {
                    $upd['cancelled_at'] = $now;
                }

                // ====== เคส “อนุมัติแล้วเติมเครดิตอัตโนมัติ” ======
                // เฉพาะ: reward_type_snapshot = wallet_credit และ fulfillment_mode_snapshot = approval
                $isApprovalCredit =
                    (string) ($red->reward_type_snapshot ?? '') === 'wallet_credit'
                    && (string) ($red->fulfillment_mode_snapshot ?? '') === 'approval';

                if ($incomingStatus === 'approved' && $isApprovalCredit) {
                    $amount = (float) ($red->credit_amount_snapshot ?? 0);
                    if ($amount <= 0) {
                        return ['ok' => false, 'msg' => 'จำนวนเครดิตไม่ถูกต้อง (<= 0)'];
                    }

                    if (! $this->memberCreditLogRepository) {
                        return ['ok' => false, 'msg' => 'memberCreditLogRepository ไม่พร้อมใช้งาน'];
                    }

                    // กันเบิ้ล: ถ้าเคย fulfilled ไปแล้วก็ไม่ต้องเติม
                    if ((string) $red->status === 'fulfilled') {
                        return ['ok' => true, 'msg' => 'รายการนี้ดำเนินการเสร็จแล้ว'];
                    }

                    // ใช้ config เดิมของระบบคุณ (seamless / multigame_open)
                    $config = core()->getConfigData();

                    $remark = "อนุมัติแลกรางวัลเติมเครดิต (#{$red->id})";
                    $method = 'D'; // ตามมาตรฐานเดิมของคุณ

                    $dataWallet = [
                        'refer_code'  => (int) $red->member_id,
                        'refer_table' => 'members',
                        'kind'        => 'SETWALLET',
                        'remark'      => $remark,
                        'amount'      => $amount,
                        'method'      => $method,
                        'member_code' => (int) $red->member_id,
                        'emp_code'    => $adminCode ?: 0,
                        'emp_name'    => $adminName ?: 'ADMIN',
                    ];

                    // เลือก method ให้เหมือน controller ฝั่ง wallet ของคุณ
                    if (($config->seamless ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWalletSeamless')) {
                        $resp = $this->memberCreditLogRepository->setWalletSeamless($dataWallet);
                    } else {
                        if (($config->multigame_open ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWallet')) {
                            $resp = $this->memberCreditLogRepository->setWallet($dataWallet);
                        } else {
                            if (! method_exists($this->memberCreditLogRepository, 'setWalletSingle')) {
                                return ['ok' => false, 'msg' => 'memberCreditLogRepository ไม่มีเมธอด setWalletSingle'];
                            }
                            $resp = $this->memberCreditLogRepository->setWalletSingle($dataWallet);
                        }
                    }

                    // ตรวจผลแบบยืดหยุ่น
                    if (! $this->repoOk($resp)) {
                        $msg = $this->repoMessage($resp, 'เติมเครดิตไม่สำเร็จ');
                        // ตรงนี้ “ไม่” เปลี่ยนเป็น fulfilled ให้ค้าง approved ไว้ เพื่อให้ทีมงานกด retry ได้
                        RewardRedemption::query()->where('id', $id)->update($upd);

                        return ['ok' => false, 'msg' => $msg];
                    }

                    // เติมสำเร็จ => ปิดงานเป็น fulfilled
                    $upd['status'] = 'fulfilled';
                    $upd['fulfilled_at'] = $fulfilledAt ?: $now;
                }

                RewardRedemption::query()->where('id', $id)->update($upd);

                return ['ok' => true, 'msg' => 'บันทึกการดำเนินการเรียบร้อย'];
            });

            if (! $out['ok']) {
                return $this->sendError($out['msg'] ?? 'ทำรายการไม่สำเร็จ', 200);
            }

            return $this->sendSuccess($out['msg'] ?? 'ดำเนินการเสร็จสิ้น');

        } catch (\Throwable $e) {
            Log::error('Reward redemption process failed', [
                'id' => $id,
                'exception' => $e,
            ]);

            return $this->sendError('ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง', 200);
        }
    }

    /* ===============================
     | Repo response helpers (เหมือนที่คุณใช้)
     =============================== */

    protected function repoOk($resp): bool
    {
        if ($resp === true) return true;

        if (! is_array($resp) && ! is_object($resp)) return false;

        $success = data_get($resp, 'success', null);
        $ok = data_get($resp, 'ok', null);
        $status = data_get($resp, 'status', null);

        if ($success === true || $ok === true) return true;
        if (is_string($success) && in_array(strtoupper($success), ['Y', 'YES', 'SUCCESS'], true)) return true;
        if (is_numeric($status) && (int) $status === 200) return true;

        return false;
    }

    protected function repoMessage($resp, string $fallback): string
    {
        $msg = data_get($resp, 'message') ?? data_get($resp, 'msg') ?? data_get($resp, 'error');
        $msg = is_string($msg) ? trim($msg) : '';
        return $msg !== '' ? $msg : $fallback;
    }
}
