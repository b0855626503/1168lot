<?php

namespace Gametech\Reward\Http\Controllers\Wallet;

use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberDiamondLogRepository;
use Gametech\Member\Repositories\MemberPointLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Reward\Repositories\RewardListRepository;
use Gametech\Reward\Repositories\RewardRedemptionRepository;
use Gametech\Wallet\Http\Controllers\AppBaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RewardListController extends AppBaseController
{
    protected $_config;

    protected $repository;

    protected $memberRepository;

    protected $rewardRedeemRepository;

    // ✅ ระบบกลางของคุณ (หักแต้ม/เพิ่มเพชร/เพิ่มเครดิต + สร้าง log)
    protected $memberPointLogRepository;

    protected $memberDiamondLogRepository;

    protected $memberCreditLogRepository;

    public function __construct(
        RewardListRepository $repository,
        MemberRepository $memberRepository,
        RewardRedemptionRepository $rewardRedeemRepository
    ) {
        $this->_config = request('_config');

        $this->repository = $repository;
        $this->memberRepository = $memberRepository;
        $this->rewardRedeemRepository = $rewardRedeemRepository;

        // ✅ Resolve repo กลางแบบ lazy ผ่าน container (ลดแรงกระแทก ไม่ต้องเปลี่ยน signature constructor)
        // ปรับ namespace/class ให้ตรงโปรเจกต์คุณได้ ถ้าชื่อจริงต่างกัน
        $this->memberPointLogRepository = app()->bound('memberPointLogRepository')
            ? app('memberPointLogRepository')
            : (class_exists(MemberPointLogRepository::class)
                ? app(MemberPointLogRepository::class)
                : null);

        $this->memberDiamondLogRepository = app()->bound('memberDiamondLogRepository')
            ? app('memberDiamondLogRepository')
            : (class_exists(MemberDiamondLogRepository::class)
                ? app(MemberDiamondLogRepository::class)
                : null);

        $this->memberCreditLogRepository = app()->bound('memberCreditLogRepository')
            ? app('memberCreditLogRepository')
            : (class_exists(MemberCreditLogRepository::class)
                ? app(MemberCreditLogRepository::class)
                : null);
    }

    /**
     * POST /member/reward/list
     * request:
     * - page (int) default 1
     * - per_page (int) default 2
     * - reward_type (string) optional
     * - q (string) optional
     * - featured_only (bool|int|string) optional: 1/true/Y => เอาเฉพาะรายการแนะนำ (server-side)
     */
    public function rewardList(Request $request)
    {
        $member = $this->resolveMember($request);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $tz = 'Asia/Bangkok';
        $now = now($tz);

        $points = (int) data_get($member, 'point_deposit', 0);

        $page = max((int) $request->input('page', 1), 1);
        $perPageReq = (int) $request->input('per_page', 2);
        $perPage = min(max($perPageReq, 1), 20);

        $rewardType = trim((string) $request->input('reward_type', ''));
        $qKeyword = trim((string) $request->input('q', ''));
        $featuredOnly = $this->toBool($request->input('featured_only', false));

        $q = method_exists($this->repository, 'newQuery')
            ? $this->repository->newQuery()
            : (method_exists($this->repository, 'getModel')
                ? $this->repository->getModel()->newQuery()
                : DB::table('rewards_list'));

        $q->select('rewards_list.*')
            ->where('status', 'active')
            ->where('is_hidden', 0)
            ->where(function ($w) use ($now) {
                $w->whereNull('start_at')->orWhere('start_at', '<=', $now);
            })
            ->where(function ($w) use ($now) {
                $w->whereNull('end_at')->orWhere('end_at', '>=', $now);
            })
            ->where(function ($w) {
                $w->where('stock_unlimited', 1)
                    ->orWhere(function ($x) {
                        $x->where('stock_unlimited', 0)
                            ->whereNotNull('stock')
                            ->whereRaw('(CAST(stock AS SIGNED) - CAST(COALESCE(reserved_stock,0) AS SIGNED)) > 0');
                    });
            });

        // ✅ สำคัญ: แนะนำแบบ server-side เพื่อให้ pagination ถูกต้องจริง (แก้ปัญหา 3 รายการแต่ 5 หน้า)
        if ($featuredOnly) {
            $q->where('is_featured', 1);
        }

        if ($rewardType !== '') {
            $q->where('reward_type', $rewardType);
        }

        if ($qKeyword !== '') {
            $q->where(function ($w) use ($qKeyword) {
                $w->where('name', 'like', "%{$qKeyword}%")
                    ->orWhere('description', 'like', "%{$qKeyword}%")
                    ->orWhere('code', 'like', "%{$qKeyword}%");
            });
        }

        $q->orderByDesc('is_featured')
            ->orderByDesc('priority')
            ->orderByDesc('id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        $rewards = collect($paginator->items())->map(function ($r) use ($tz) {
            $stockUnlimited = (int) ($r->stock_unlimited ?? 1) === 1;

            $stock = $r->stock;
            $reserved = (int) ($r->reserved_stock ?? 0);

            $remaining = $stockUnlimited ? null : max(((int) ($stock ?? 0)) - $reserved, 0);

            $image = (string) ($r->image ?? '');

            return [
                'id' => (int) $r->id,
                'code' => (string) ($r->code ?? ''),
                'name' => (string) ($r->name ?? ''),
                'description' => (string) ($r->description ?? ''),
                'status' => (string) ($r->status ?? ''),

                'is_hidden' => (int) ($r->is_hidden ?? 0),
                'is_featured' => (int) ($r->is_featured ?? 0),
                'priority' => (int) ($r->priority ?? 0),

                'image' => $image,
                'image_url' => $this->toPublicImageUrl($image),
                'images' => $this->safeJsonToArray($r->images ?? null),

                'reward_type' => (string) ($r->reward_type ?? ''),
                'fulfillment_mode' => (string) ($r->fulfillment_mode ?? ''),

                'point_cost' => (int) ($r->point_cost ?? 0),
                'credit_amount' => $r->credit_amount,
                'gem_amount' => $r->gem_amount,

                'stock_unlimited' => (int) ($r->stock_unlimited ?? 1),
                'stock' => $stockUnlimited ? null : (int) ($stock ?? 0),
                'reserved_stock' => $stockUnlimited ? null : $reserved,
                'stock_remaining' => $remaining,

                'limit_type' => (string) ($r->limit_type ?? 'unlimited'),
                'limit_per_user' => $r->limit_per_user !== null ? (int) $r->limit_per_user : null,
                'limit_period' => $r->limit_period !== null ? (string) $r->limit_period : null,
                'limit_per_period' => $r->limit_per_period !== null ? (int) $r->limit_per_period : null,
                'strict_limit' => (int) ($r->strict_limit ?? 0),

                'limit_total' => $r->limit_total !== null ? (int) $r->limit_total : null,
                'cooldown_minutes' => $r->cooldown_minutes !== null ? (int) $r->cooldown_minutes : null,

                'start_at' => ! empty($r->start_at) ? Carbon::parse($r->start_at)->tz($tz)->format('Y-m-d H:i:s') : null,
                'end_at' => ! empty($r->end_at) ? Carbon::parse($r->end_at)->tz($tz)->format('Y-m-d H:i:s') : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'diamond' => $points,
            'system' => ['reward' => true],
            'rewards' => $rewards,
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'from' => (int) ($paginator->firstItem() ?? 0),
                'to' => (int) ($paginator->lastItem() ?? 0),
            ],
            // ช่วย debug/QA ว่าหน้าไหนกำลังขอ “แนะนำ” อยู่
            'filters' => [
                'featured_only' => (bool) $featuredOnly,
                'reward_type' => (string) $rewardType,
                'q' => (string) $qKeyword,
            ],
        ]);
    }

    /**
     * POST /member/reward/history
     * request:
     * - page (int) default 1
     * - per_page (int) default 10
     * - q (string) optional (ค้นหา: code/name snapshot)
     * - status (string) optional (pending|fulfilled|rejected|cancelled)
     * - reward_type (string) optional (wallet_credit|wallet_gem|external)
     * - mode (string) optional (auto|manual|approval)
     */
    public function rewardHistory(Request $request)
    {
        $member = $this->resolveMember($request);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $memberCode = (int) data_get($member, 'code', 0);
        if ($memberCode <= 0) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรหัสสมาชิก'], 500);
        }

        $tz = 'Asia/Bangkok';

        $page = max((int) $request->input('page', 1), 1);
        $perPageReq = (int) $request->input('per_page', 10);
        $perPage = min(max($perPageReq, 1), 50);

        $qKeyword = trim((string) $request->input('q', ''));
        $status = trim((string) $request->input('status', ''));
        $rewardType = trim((string) $request->input('reward_type', ''));
        $mode = trim((string) $request->input('mode', ''));

        $q = DB::table('reward_redemptions')
            ->where('member_id', $memberCode);

        if ($qKeyword !== '') {
            $q->where(function ($w) use ($qKeyword) {
                $w->where('reward_name_snapshot', 'like', "%{$qKeyword}%")
                    ->orWhere('reward_code_snapshot', 'like', "%{$qKeyword}%");
            });
        }

        if ($status !== '') {
            $q->where('status', $status);
        }

        if ($rewardType !== '') {
            $q->where('reward_type_snapshot', $rewardType);
        }

        if ($mode !== '') {
            $q->where('fulfillment_mode_snapshot', $mode);
        }

        // เรียงล่าสุดก่อน: redeemed_at > created_at > id (กัน schema เก่าไม่มี timestamp)
        $orderExpr = 'COALESCE(redeemed_at, created_at, updated_at)';
        $q->orderByRaw($orderExpr.' DESC')
            ->orderByDesc('id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($r) use ($tz) {
            // รองรับทั้ง Carbon/string/null แบบไม่พัง
            $fmt = function ($v) use ($tz) {
                if (empty($v)) {
                    return null;
                }
                try {
                    return Carbon::parse($v)->tz($tz)->format('Y-m-d H:i:s');
                } catch (\Throwable $e) {
                    return (string) $v;
                }
            };

            return [
                'id' => (int) ($r->id ?? 0),
                'reward_id' => (int) ($r->reward_id ?? 0),
                'member_id' => (int) ($r->member_id ?? 0),

                // snapshot (source of truth)
                'reward_code_snapshot' => (string) ($r->reward_code_snapshot ?? ''),
                'reward_name_snapshot' => (string) ($r->reward_name_snapshot ?? ''),
                'point_cost_snapshot' => (int) ($r->point_cost_snapshot ?? 0),
                'reward_type_snapshot' => (string) ($r->reward_type_snapshot ?? ''),
                'fulfillment_mode_snapshot' => (string) ($r->fulfillment_mode_snapshot ?? ''),

                'credit_amount_snapshot' => $r->credit_amount_snapshot ?? null,
                'gem_amount_snapshot' => $r->gem_amount_snapshot ?? null,
                'payload_snapshot' => $this->safeJsonToArray($r->payload_snapshot ?? null),

                // status lifecycle
                'status' => (string) ($r->status ?? 'pending'),

                // notes
                'note_user' => (string) ($r->note_user ?? ''),
                'note_staff' => (string) ($r->note_staff ?? ''),

                // contact
                'contact_channel' => (string) ($r->contact_channel ?? ''),
                'contact_value' => (string) ($r->contact_value ?? ''),

                // timestamps
                'redeemed_at' => $fmt($r->redeemed_at ?? null),
                'fulfilled_at' => $fmt($r->fulfilled_at ?? null),
                'cancelled_at' => $fmt($r->cancelled_at ?? null),
                'rejected_at' => $fmt($r->rejected_at ?? null),
                'refunded_at' => $fmt($r->refunded_at ?? null),

                // staff
                'handled_by' => $r->handled_by !== null ? (int) $r->handled_by : null,
                'refunded_by' => $r->refunded_by !== null ? (int) $r->refunded_by : null,

                // flags
                'point_debited' => $this->toBool($r->point_debited ?? false),

                // audit (ถ้ามี column)
                'request_ip' => (string) ($r->request_ip ?? ''),
                'request_ua' => (string) ($r->request_ua ?? ''),
                'request_source' => (string) ($r->request_source ?? ''),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'items' => $items,
            'meta' => [
                'current_page' => (int) $paginator->currentPage(),
                'last_page' => (int) $paginator->lastPage(),
                'per_page' => (int) $paginator->perPage(),
                'total' => (int) $paginator->total(),
                'from' => (int) ($paginator->firstItem() ?? 0),
                'to' => (int) ($paginator->lastItem() ?? 0),
            ],
            'filters' => [
                'q' => (string) $qKeyword,
                'status' => (string) $status,
                'reward_type' => (string) $rewardType,
                'mode' => (string) $mode,
            ],
        ]);
    }

    /**
     * POST /member/reward/redeem
     */
    public function redeem(Request $request)
    {
        $member = $this->resolveMember($request);
        if (! $member) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $rewardId = (int) $request->input('reward_id', 0);
        if ($rewardId <= 0) {
            return response()->json(['success' => false, 'message' => 'กรุณาระบุ reward_id'], 422);
        }

        $memberCode = (int) data_get($member, 'code', 0);
        if ($memberCode <= 0) {
            return response()->json(['success' => false, 'message' => 'ไม่พบรหัสสมาชิก'], 500);
        }

        $idemKey = trim((string) $request->header('X-Idempotency-Key', ''));

        // idempotency (DB ตรง ๆ กัน proxy พัง)
        if ($idemKey !== '') {
            $existing = DB::table('reward_redemptions')
                ->where('member_id', $memberCode)
                ->where('idempotency_key', $idemKey)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                $freshMember = $this->memberRepository->findOneWhere(['code' => $memberCode]);
                $points = (int) data_get($freshMember, 'point_deposit', (int) data_get($member, 'point_deposit', 0));

                return response()->json([
                    'success' => true,
                    'diamond' => $points,
                    'mode' => (string) ($existing->fulfillment_mode_snapshot ?? ''),
                    'redemption_status' => (string) ($existing->status ?? 'pending'),
                    'format' => [
                        'title' => 'ทำรายการแล้ว',
                        'msg' => 'ระบบตรวจพบคำขอเดิมและยืนยันผลเดิมให้เรียบร้อย',
                        'img' => '',
                    ],
                    'redemption_id' => (int) ($existing->id ?? 0),
                ]);
            }
        }

        $tz = 'Asia/Bangkok';
        $now = now($tz);

        try {
            $result = DB::transaction(function () use ($rewardId, $idemKey, $memberCode, $now, $tz) {

                $m = DB::table('members')->where('code', $memberCode)->lockForUpdate()->first();
                if (! $m) {
                    return ['ok' => false, 'status' => 404, 'message' => 'ไม่พบสมาชิก'];
                }

                $pointsBefore = (int) ($m->point_deposit ?? 0);

                $reward = DB::table('rewards_list')->where('id', $rewardId)->lockForUpdate()->first();
                if (! $reward) {
                    return ['ok' => false, 'status' => 404, 'message' => 'ไม่พบรางวัล'];
                }

                if (strtolower((string) ($reward->status ?? '')) !== 'active') {
                    return ['ok' => false, 'status' => 422, 'message' => 'รางวัลนี้ยังไม่เปิดใช้งาน'];
                }
                if ((int) ($reward->is_hidden ?? 0) === 1) {
                    return ['ok' => false, 'status' => 422, 'message' => 'รางวัลนี้ถูกซ่อนไว้'];
                }

                if (! empty($reward->start_at) && Carbon::parse($reward->start_at)->tz($tz)->gt($now)) {
                    return ['ok' => false, 'status' => 422, 'message' => 'รางวัลนี้ยังไม่ถึงเวลาเริ่ม'];
                }
                if (! empty($reward->end_at) && Carbon::parse($reward->end_at)->tz($tz)->lt($now)) {
                    return ['ok' => false, 'status' => 422, 'message' => 'รางวัลนี้หมดเวลาแล้ว'];
                }

                $cost = (int) ($reward->point_cost ?? 0);
                if ($cost <= 0) {
                    return ['ok' => false, 'status' => 422, 'message' => 'แต้มของรางวัลไม่ถูกต้อง'];
                }
                if ($pointsBefore < $cost) {
                    return ['ok' => false, 'status' => 422, 'message' => 'พ้อยท์ไม่พอ'];
                }

                $stockUnlimited = (int) ($reward->stock_unlimited ?? 1) === 1;
                $stock = (int) ($reward->stock ?? 0);
                $reserved = (int) ($reward->reserved_stock ?? 0);

                if (! $stockUnlimited) {
                    if ($stock <= 0 || ($stock - $reserved) <= 0) {
                        return ['ok' => false, 'status' => 422, 'message' => 'สต๊อกรางวัลหมด'];
                    }
                }

                $limitCheck = $this->checkLimitsInTx($memberCode, $reward, $now, $tz);
                if (! $limitCheck['ok']) {
                    return ['ok' => false, 'status' => 422, 'message' => $limitCheck['message'] ?? 'ไม่สามารถแลกรางวัลได้'];
                }

                $mode = (string) ($reward->fulfillment_mode ?? 'auto');
                $type = (string) ($reward->reward_type ?? 'wallet_credit');
                $autoClaim = $this->toBool($reward->auto_claim ?? true);

                $creditAmount = $reward->credit_amount ?? null;
                $gemAmount = $reward->gem_amount ?? null;

                if ($type === 'wallet_credit') {
                    $gemAmount = null;
                } elseif ($type === 'wallet_gem') {
                    $creditAmount = null;
                } else {
                    $creditAmount = null;
                    $gemAmount = null;
                }

                // ===== touch columns (กันตารางไม่มี timestamps) =====
                $rewardListTouch = $this->touchColumnsFor('rewards_list', $now);
                $redemptionTouchInsert = $this->touchColumnsFor('reward_redemptions', $now, true);
                $redemptionTouchUpdate = $this->touchColumnsFor('reward_redemptions', $now, false);

                // ===== สร้าง redemption ก่อน (หลักฐาน idempotency / audit) =====
                $redemptionStatus = 'pending';
                $fulfilledAt = null;

                $formatTitle = 'รับเรื่องแล้ว';
                $formatMsg = 'ระบบรับรายการแล้ว กรุณารอการดำเนินการ';
                $img = '';

                $willAutoClaimCredit = ($mode === 'auto'
                    && $autoClaim
                    && $type === 'wallet_credit'
                    && $creditAmount !== null
                    && (float) $creditAmount > 0);

                if ($willAutoClaimCredit) {
                    $formatTitle = 'กำลังดำเนินการ';
                    $formatMsg = 'ระบบกำลังเติมเครดิตให้';
                } else {
                    if ($mode === 'approval') {
                        $formatMsg = 'ระบบรับรายการแล้ว รออนุมัติก่อนดำเนินการ';
                    } elseif ($mode === 'manual' || $type === 'external') {
                        $formatMsg = 'ระบบรับรายการแล้ว ทีมงานจะติดต่อ/ดำเนินการให้ภายหลัง';
                    } elseif ($mode === 'auto' && ! $autoClaim) {
                        $formatMsg = 'รางวัลนี้ตั้งค่าเป็นไม่รับทันที ระบบจึงรับเรื่องไว้ก่อน';
                    } elseif ($type === 'wallet_gem') {
                        $formatMsg = 'ระบบรับรายการแล้ว';
                    }
                }

                $redemptionId = DB::table('reward_redemptions')->insertGetId(array_merge([
                    'reward_id' => (int) $reward->id,
                    'member_id' => $memberCode,

                    'reward_code_snapshot' => (string) ($reward->code ?? ''),
                    'reward_name_snapshot' => (string) ($reward->name ?? ''),

                    'point_cost_snapshot' => $cost,
                    'reward_type_snapshot' => $type,
                    'fulfillment_mode_snapshot' => $mode,

                    'credit_amount_snapshot' => $creditAmount,
                    'gem_amount_snapshot' => $gemAmount,

                    'payload_snapshot' => $reward->payload ?? null,

                    'status' => $redemptionStatus,
                    'fulfilled_at' => $fulfilledAt,

                    'handled_by' => null,
                    'idempotency_key' => $idemKey !== '' ? $idemKey : null,
                ], $redemptionTouchInsert));

                // ===== 1) หักแต้มผ่านระบบกลาง (สร้าง log ด้วย) =====
                $emp = $this->resolveActorForLog();

                if (! $this->memberPointLogRepository || ! method_exists($this->memberPointLogRepository, 'setPoint')) {
                    throw new \RuntimeException('memberPointLogRepository ไม่พร้อมใช้งาน');
                }

                $rewardCode = (string) ($reward->code ?? '');
                $rewardName = (string) ($reward->name ?? '');
                $remarkPoint = 'ใช้พ้อยในการแลกรางวัล';
                $remarkPoint .= " (#{$redemptionId}";
                $remarkPoint .= $rewardCode !== '' ? " / {$rewardCode}" : '';
                $remarkPoint .= $rewardName !== '' ? " / {$rewardName}" : '';
                $remarkPoint .= ')';

                $respPoint = $this->memberPointLogRepository->setPoint([
                    'remark' => $remarkPoint,
                    'amount' => $cost,
                    'method' => 'W',
                    'member_code' => $memberCode,
                    'emp_code' => (int) ($emp['code'] ?? 0),
                    'emp_name' => (string) ($emp['name'] ?? 'SYSTEM'),
                ]);

                if (! $this->repoOk($respPoint)) {
                    $msg = $this->repoMessage($respPoint, 'หักแต้มไม่สำเร็จ');
                    throw new \RuntimeException($msg);
                }

                // ===== 2) ดำเนินการรางวัล =====
                if ($type === 'wallet_gem' && $gemAmount !== null && (float) $gemAmount > 0) {
                    if (! $this->memberDiamondLogRepository || ! method_exists($this->memberDiamondLogRepository, 'setDiamond')) {
                        throw new \RuntimeException('memberDiamondLogRepository ไม่พร้อมใช้งาน');
                    }

                    $remarkGem = "รับเพชรจากการแลกรางวัล (#{$redemptionId})";
                    $respGem = $this->memberDiamondLogRepository->setDiamond([
                        'remark' => $remarkGem,
                        'amount' => (float) $gemAmount,
                        'method' => 'D',
                        'member_code' => $memberCode,
                        'emp_code' => (int) ($emp['code'] ?? 0),
                        'emp_name' => (string) ($emp['name'] ?? 'SYSTEM'),
                    ]);

                    if (! $this->repoOk($respGem)) {
                        $msg = $this->repoMessage($respGem, 'เพิ่มเพชรไม่สำเร็จ');
                        throw new \RuntimeException($msg);
                    }

                    $redemptionStatus = 'fulfilled';
                    $fulfilledAt = $now;

                    $formatTitle = 'สำเร็จ';
                    $formatMsg = 'แลกรางวัลเรียบร้อย ระบบเพิ่มเพชรให้แล้ว';
                }

                if ($willAutoClaimCredit) {
                    if (! $this->memberCreditLogRepository) {
                        throw new \RuntimeException('memberCreditLogRepository ไม่พร้อมใช้งาน');
                    }

                    $config = core()->getConfigData();

                    $remarkCredit = "รับเครดิตจากการแลกรางวัล (#{$redemptionId})";
                    $method = 'D'; // ปรับได้ตามมาตรฐาน method ของระบบคุณ

                    $dataWallet = [
                        'refer_code' => $memberCode,
                        'refer_table' => 'members',
                        'kind' => 'SETWALLET',
                        'remark' => $remarkCredit,
                        'amount' => (float) $creditAmount,
                        'method' => $method,
                        'member_code' => $memberCode,
                        'emp_code' => (int) ($emp['code'] ?? 0),
                        'emp_name' => (string) ($emp['name'] ?? 'SYSTEM'),
                    ];

                    if (($config->seamless ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWalletSeamless')) {
                        $respWallet = $this->memberCreditLogRepository->setWalletSeamless($dataWallet);
                    } else {
                        if (($config->multigame_open ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWallet')) {
                            $respWallet = $this->memberCreditLogRepository->setWallet($dataWallet);
                        } else {
                            if (! method_exists($this->memberCreditLogRepository, 'setWalletSingle')) {
                                throw new \RuntimeException('memberCreditLogRepository ไม่มีเมธอด setWalletSingle');
                            }
                            $respWallet = $this->memberCreditLogRepository->setWalletSingle($dataWallet);
                        }
                    }

                    if (! $this->repoOk($respWallet)) {
                        $msg = $this->repoMessage($respWallet, 'เติมเครดิตไม่สำเร็จ');
                        throw new \RuntimeException($msg);
                    }

                    $redemptionStatus = 'fulfilled';
                    $fulfilledAt = $now;

                    $formatTitle = 'สำเร็จ';
                    $formatMsg = 'แลกรางวัลเรียบร้อย ระบบเติมเครดิตให้แล้ว';
                }

                // ===== 3) ลดสต๊อก (หลังหักแต้มสำเร็จเท่านั้น) =====
                if (! $stockUnlimited) {
                    DB::table('rewards_list')
                        ->where('id', (int) $reward->id)
                        ->update(array_merge([
                            'stock' => DB::raw('stock - 1'),
                        ], $rewardListTouch));
                }

                // ===== 4) อัปเดต redemption status =====
                DB::table('reward_redemptions')
                    ->where('id', $redemptionId)
                    ->update(array_merge([
                        'status' => $redemptionStatus,
                        'fulfilled_at' => $fulfilledAt,
                    ], $redemptionTouchUpdate));

                // ✅ แต้มหลังหัก: อ่านจาก DB อีกที (กัน repo กลางมี logic อื่น)
                $pointsAfter = (int) DB::table('members')->where('code', $memberCode)->value('point_deposit');
                if ($pointsAfter <= 0 && $pointsBefore > 0) {
                    // fallback เผื่อ DB schema/field แปลก
                    $pointsAfter = max($pointsBefore - $cost, 0);
                }

                return [
                    'ok' => true,
                    'status' => 200,
                    'diamond' => $pointsAfter,
                    'mode' => $mode,
                    'redemption_status' => $redemptionStatus,
                    'format' => [
                        'title' => $formatTitle,
                        'msg' => $formatMsg,
                        'img' => $img,
                    ],
                    'redemption_id' => $redemptionId,
                ];
            });

            if (! $result['ok']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?? 'ทำรายการไม่สำเร็จ',
                ], (int) ($result['status'] ?? 500));
            }

            return response()->json([
                'success' => true,
                'diamond' => (int) $result['diamond'],
                'mode' => (string) $result['mode'],
                'redemption_status' => (string) ($result['redemption_status'] ?? 'pending'),
                'format' => $result['format'] ?? ['title' => 'สำเร็จ', 'msg' => '', 'img' => ''],
                'redemption_id' => $result['redemption_id'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('Reward redeem failed', [
                'member_code' => $memberCode,
                'reward_id' => $rewardId,
                'idempotency_key' => $idemKey,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง',
                'error' => app()->isProduction() ? null : $e->getMessage(),
            ], 500);
        }
    }

    protected function checkLimitsInTx(int $memberCode, object $reward, Carbon $now, string $tz): array
    {
        $rewardId = (int) ($reward->id ?? 0);
        if ($rewardId <= 0) {
            return ['ok' => false, 'message' => 'รางวัลไม่ถูกต้อง'];
        }

        $baseStatus = ['pending', 'fulfilled'];

        if ($reward->limit_total !== null && $reward->limit_total !== '') {
            $limitTotal = (int) $reward->limit_total;
            if ($limitTotal > 0) {
                if ((int) ($reward->strict_limit ?? 0) === 1) {
                    DB::table('reward_redemptions')
                        ->select('id')
                        ->where('reward_id', $rewardId)
                        ->whereIn('status', $baseStatus)
                        ->lockForUpdate()
                        ->limit(1)
                        ->get();
                }

                $usedTotal = (int) DB::table('reward_redemptions')
                    ->where('reward_id', $rewardId)
                    ->whereIn('status', $baseStatus)
                    ->count();

                if ($usedTotal >= $limitTotal) {
                    return ['ok' => false, 'message' => 'รางวัลนี้ถูกแลกครบจำนวนรวมแล้ว'];
                }
            }
        }

        if ($reward->cooldown_minutes !== null && $reward->cooldown_minutes !== '') {
            $cooldown = (int) $reward->cooldown_minutes;
            if ($cooldown > 0) {
                $last = DB::table('reward_redemptions')
                    ->where('reward_id', $rewardId)
                    ->where('member_id', $memberCode)
                    ->whereIn('status', $baseStatus)
                    ->orderByDesc('id')
                    ->first();

                if ($last && ! empty($last->created_at)) {
                    $lastAt = Carbon::parse($last->created_at)->tz($tz);
                    $diffMin = $lastAt->diffInMinutes($now);
                    if ($diffMin < $cooldown) {
                        $remain = max($cooldown - $diffMin, 1);

                        return ['ok' => false, 'message' => "กรุณารออีก {$remain} นาที แล้วค่อยแลกใหม่"];
                    }
                }
            }
        }

        $limitType = trim((string) ($reward->limit_type ?? 'unlimited'));
        if ($limitType === '' || $limitType === 'unlimited') {
            return ['ok' => true];
        }

        if ($limitType === 'per_reward') {
            $limitPerUser = (int) ($reward->limit_per_user ?? 0);
            if ($limitPerUser <= 0) {
                $limitPerUser = 1;
            }

            if ((int) ($reward->strict_limit ?? 0) === 1) {
                DB::table('reward_redemptions')
                    ->select('id')
                    ->where('reward_id', $rewardId)
                    ->where('member_id', $memberCode)
                    ->whereIn('status', $baseStatus)
                    ->lockForUpdate()
                    ->limit(1)
                    ->get();
            }

            $used = (int) DB::table('reward_redemptions')
                ->where('reward_id', $rewardId)
                ->where('member_id', $memberCode)
                ->whereIn('status', $baseStatus)
                ->count();

            if ($used >= $limitPerUser) {
                return ['ok' => false, 'message' => 'คุณแลกรางวัลนี้ครบจำนวนที่กำหนดแล้ว'];
            }

            return ['ok' => true];
        }

        if ($limitType === 'per_period') {
            $period = trim((string) ($reward->limit_period ?? 'day'));
            $limitPerPeriod = (int) ($reward->limit_per_period ?? 0);
            if ($limitPerPeriod <= 0) {
                $limitPerPeriod = 1;
            }

            [$start, $end] = $this->periodRange($now, $period, $tz);

            if ((int) ($reward->strict_limit ?? 0) === 1) {
                DB::table('reward_redemptions')
                    ->select('id')
                    ->where('reward_id', $rewardId)
                    ->where('member_id', $memberCode)
                    ->whereIn('status', $baseStatus)
                    ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                    ->lockForUpdate()
                    ->limit(1)
                    ->get();
            }

            $used = (int) DB::table('reward_redemptions')
                ->where('reward_id', $rewardId)
                ->where('member_id', $memberCode)
                ->whereIn('status', $baseStatus)
                ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
                ->count();

            if ($used >= $limitPerPeriod) {
                $label = match ($period) {
                    'week' => 'สัปดาห์นี้',
                    'month' => 'เดือนนี้',
                    default => 'วันนี้',
                };

                return ['ok' => false, 'message' => "คุณแลกรางวัลนี้ครบจำนวนสำหรับ{$label}แล้ว"];
            }

            return ['ok' => true];
        }

        return ['ok' => true];
    }

    protected function periodRange(Carbon $now, string $period, string $tz): array
    {
        $n = $now->copy()->tz($tz);

        if ($period === 'week') {
            return [$n->copy()->startOfWeek(Carbon::MONDAY), $n->copy()->endOfWeek(Carbon::SUNDAY)];
        }

        if ($period === 'month') {
            return [$n->copy()->startOfMonth(), $n->copy()->endOfMonth()];
        }

        return [$n->copy()->startOfDay(), $n->copy()->endOfDay()];
    }

    protected function safeJsonToArray($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }

        try {
            $decoded = json_decode((string) $value, true);

            return is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function toPublicImageUrl($image): string
    {
        $img = trim((string) ($image ?? ''));
        if ($img === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $img)) {
            return $img;
        }

        $img = ltrim($img, '/');

        if (str_starts_with($img, 'storage/')) {
            return url('/'.$img);
        }

        return url('/storage/'.$img);
    }

    protected function toBool($v): bool
    {
        if ($v === true || $v === 1 || $v === '1' || $v === 'Y') {
            return true;
        }
        $s = trim(strtolower((string) ($v ?? '')));

        return in_array($s, ['true', 'yes', 'y', 'on'], true);
    }

    protected function resolveMember(Request $request)
    {
        $u = $request->user();
        if ($u) {
            return $u;
        }

        foreach (['customer', 'member', 'web'] as $g) {
            try {
                $u = Auth::guard($g)->user();
                if ($u) {
                    return $u;
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        try {
            return Auth::user();
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * ชุดข้อมูล actor สำหรับ log (กันกรณี wallet route ไม่มี employee)
     */
    protected function resolveActorForLog(): array
    {
        try {
            $code = 0;
            $name = 'SYSTEM';

            if (method_exists($this, 'id')) {
                $tmp = $this->id();
                if ($tmp !== null && $tmp !== '') {
                    $code = (int) $tmp;
                }
            }

            if (method_exists($this, 'user')) {
                $u = $this->user();
                if ($u) {
                    $full = trim((string) (($u->name ?? '').' '.($u->surname ?? '')));
                    if ($full !== '') {
                        $name = $full;
                    }
                }
            }

            return ['code' => $code, 'name' => $name];
        } catch (\Throwable $e) {
            return ['code' => 0, 'name' => 'SYSTEM'];
        }
    }

    /**
     * ตรวจผลลัพธ์จาก repo กลางแบบยืดหยุ่น (เพราะ legacy มักคืน format ไม่เหมือนกัน)
     */
    protected function repoOk($resp): bool
    {
        if ($resp === true) {
            return true;
        }
        if (! is_array($resp) && ! is_object($resp)) {
            return false;
        }

        $success = data_get($resp, 'success', null);
        $ok = data_get($resp, 'ok', null);
        $status = data_get($resp, 'status', null);

        if ($success === true || $ok === true) {
            return true;
        }
        if (is_string($success) && in_array(strtoupper($success), ['Y', 'YES', 'SUCCESS'], true)) {
            return true;
        }
        if (is_numeric($status) && (int) $status === 200) {
            return true;
        }

        return false;
    }

    protected function repoMessage($resp, string $fallback): string
    {
        $msg = data_get($resp, 'message') ?? data_get($resp, 'msg') ?? data_get($resp, 'error');
        $msg = is_string($msg) ? trim($msg) : '';

        return $msg !== '' ? $msg : $fallback;
    }

    /**
     * สร้าง array ของ column timestamps ที่ "มีจริง" ใน table นั้น ๆ
     */
    protected function touchColumnsFor(string $table, Carbon $now, bool $includeCreatedAt = false): array
    {
        $key = "reward:touchcols:{$table}:".($includeCreatedAt ? '1' : '0');

        return Cache::remember($key, 600, function () use ($now, $includeCreatedAt) {
            $cols = [];

            try {
                if ($includeCreatedAt) {
                    $cols['created_at'] = $now;
                }
                $cols['updated_at'] = $now;
            } catch (\Throwable $e) {
                return [];
            }

            return $cols;
        });
    }
}
