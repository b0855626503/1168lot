<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\FrontendApi\Http\Requests\RewardHistoryRequest;
use Gametech\FrontendApi\Http\Requests\RewardListRequest;
use Gametech\FrontendApi\Http\Requests\RewardRedeemRequest;
use Gametech\Member\Repositories\MemberCreditLogRepository;
use Gametech\Member\Repositories\MemberDiamondLogRepository;
use Gametech\Member\Repositories\MemberPointLogRepository;
use Gametech\Member\Repositories\MemberRepository;
use Gametech\Reward\Repositories\RewardListRepository;
use Gametech\Reward\Repositories\RewardRedemptionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RewardController extends BaseController
{
    private const TZ = 'Asia/Bangkok';

    private mixed $memberPointLogRepository;

    private mixed $memberDiamondLogRepository;

    private mixed $memberCreditLogRepository;

    public function __construct(
        private RewardListRepository $rewardListRepository,
        private MemberRepository $memberRepository,
        private RewardRedemptionRepository $rewardRedemptionRepository
    ) {
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

    public function list(RewardListRequest $request): JsonResponse
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $now = now(self::TZ);
            $points = (int) data_get($member, 'point_deposit', 0);

            $page = max((int) $request->input('page', 1), 1);
            $perPage = min(max((int) $request->input('per_page', 20), 1), 20);
            $rewardType = trim((string) $request->input('reward_type', ''));
            $queryKeyword = trim((string) $request->input('q', ''));
            $featuredOnly = $this->toBool($request->input('featured_only', false));

            $query = method_exists($this->rewardListRepository, 'newQuery')
                ? $this->rewardListRepository->newQuery()
                : (method_exists($this->rewardListRepository, 'getModel')
                    ? $this->rewardListRepository->getModel()->newQuery()
                    : DB::table('rewards_list'));

            $query->select('rewards_list.*')
                ->where('status', 'active')
                ->where('is_hidden', 0)
                ->where(function ($builder) use ($now): void {
                    $builder->whereNull('start_at')->orWhere('start_at', '<=', $now);
                })
                ->where(function ($builder) use ($now): void {
                    $builder->whereNull('end_at')->orWhere('end_at', '>=', $now);
                })
                ->where(function ($builder): void {
                    $builder->where('stock_unlimited', 1)
                        ->orWhere(function ($nested): void {
                            $nested->where('stock_unlimited', 0)
                                ->whereNotNull('stock')
                                ->whereRaw('(CAST(stock AS SIGNED) - CAST(COALESCE(reserved_stock,0) AS SIGNED)) > 0');
                        });
                });

            if ($featuredOnly) {
                $query->where('is_featured', 1);
            }

            if ($rewardType !== '') {
                $query->where('reward_type', $rewardType);
            }

            if ($queryKeyword !== '') {
                $query->where(function ($builder) use ($queryKeyword): void {
                    $builder->where('name', 'like', "%{$queryKeyword}%")
                        ->orWhere('description', 'like', "%{$queryKeyword}%")
                        ->orWhere('code', 'like', "%{$queryKeyword}%");
                });
            }

            $query->orderByDesc('is_featured')
                ->orderByDesc('priority')
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $items = collect($paginator->items())->map(function ($item): array {
                $stockUnlimited = (int) ($item->stock_unlimited ?? 1) === 1;
                $stock = $item->stock;
                $reservedStock = (int) ($item->reserved_stock ?? 0);

                return [
                    'id' => (int) $item->id,
                    'code' => (string) ($item->code ?? ''),
                    'name' => (string) ($item->name ?? ''),
                    'description' => (string) ($item->description ?? ''),
                    'status' => (string) ($item->status ?? ''),
                    'is_hidden' => (int) ($item->is_hidden ?? 0),
                    'is_featured' => (int) ($item->is_featured ?? 0),
                    'priority' => (int) ($item->priority ?? 0),
                    'image' => (string) ($item->image ?? ''),
                    'image_url' => $this->toPublicImageUrl((string) ($item->image ?? '')),
                    'images' => $this->safeJsonToArray($item->images ?? null),
                    'reward_type' => (string) ($item->reward_type ?? ''),
                    'fulfillment_mode' => (string) ($item->fulfillment_mode ?? ''),
                    'point_cost' => (int) ($item->point_cost ?? 0),
                    'credit_amount' => $item->credit_amount,
                    'gem_amount' => $item->gem_amount,
                    'stock_unlimited' => (int) ($item->stock_unlimited ?? 1),
                    'stock' => $stockUnlimited ? null : (int) ($stock ?? 0),
                    'reserved_stock' => $stockUnlimited ? null : $reservedStock,
                    'stock_remaining' => $stockUnlimited ? null : max(((int) ($stock ?? 0)) - $reservedStock, 0),
                    'limit_type' => (string) ($item->limit_type ?? 'unlimited'),
                    'limit_per_user' => $item->limit_per_user !== null ? (int) $item->limit_per_user : null,
                    'limit_period' => $item->limit_period !== null ? (string) $item->limit_period : null,
                    'limit_per_period' => $item->limit_per_period !== null ? (int) $item->limit_per_period : null,
                    'strict_limit' => (int) ($item->strict_limit ?? 0),
                    'limit_total' => $item->limit_total !== null ? (int) $item->limit_total : null,
                    'cooldown_minutes' => $item->cooldown_minutes !== null ? (int) $item->cooldown_minutes : null,
                    'start_at' => ! empty($item->start_at) ? Carbon::parse($item->start_at)->tz(self::TZ)->format('Y-m-d H:i:s') : null,
                    'end_at' => ! empty($item->end_at) ? Carbon::parse($item->end_at)->tz(self::TZ)->format('Y-m-d H:i:s') : null,
                ];
            })->values()->all();

            return $this->sendResponseNew([
                'point' => $points,
                'diamond' => $points,
                'system' => ['reward' => true],
                'rewards' => $items,
                'meta' => [
                    'current_page' => (int) $paginator->currentPage(),
                    'last_page' => (int) $paginator->lastPage(),
                    'per_page' => (int) $paginator->perPage(),
                    'total' => (int) $paginator->total(),
                    'from' => (int) ($paginator->firstItem() ?? 0),
                    'to' => (int) ($paginator->lastItem() ?? 0),
                ],
                'filters' => [
                    'featured_only' => $featuredOnly,
                    'reward_type' => $rewardType,
                    'q' => $queryKeyword,
                ],
            ], 'ดึงรายการรางวัลสำเร็จ');
        } catch (\Throwable $e) {
            report($e);

            return $this->sendError('ไม่สามารถดึงรายการรางวัลได้ในขณะนี้', 422);
        }
    }

    public function redeem(RewardRedeemRequest $request): JsonResponse
    {
        $member = $request->user() ?: $request->user('customer');
        if (! $member || ! isset($member->code)) {
            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
        }

        $rewardId = (int) $request->input('reward_id', 0);
        $memberCode = (int) data_get($member, 'code', 0);
        if ($memberCode <= 0) {
            return $this->sendError('ไม่พบรหัสสมาชิก', 500);
        }

        $idempotencyKey = trim((string) $request->header('X-Idempotency-Key', ''));

        if ($idempotencyKey !== '') {
            $existing = DB::table('reward_redemptions')
                ->where('member_id', $memberCode)
                ->where('idempotency_key', $idempotencyKey)
                ->orderByDesc('id')
                ->first();

            if ($existing) {
                $freshMember = $this->memberRepository->findOneWhere(['code' => $memberCode]);
                $points = (int) data_get($freshMember, 'point_deposit', (int) data_get($member, 'point_deposit', 0));

                return $this->sendResponseNew([
                    'point' => $points,
                    'mode' => (string) ($existing->fulfillment_mode_snapshot ?? ''),
                    'redemption_status' => (string) ($existing->status ?? 'pending'),
                    'format' => [
                        'title' => 'ทำรายการแล้ว',
                        'msg' => 'ระบบตรวจพบคำขอเดิมและยืนยันผลเดิมให้เรียบร้อย',
                        'img' => '',
                    ],
                    'redemption_id' => (int) ($existing->id ?? 0),
                ], 'ทำรายการแลกรางวัลเรียบร้อย');
            }
        }

        $now = now(self::TZ);

        try {
            $result = DB::transaction(function () use ($memberCode, $rewardId, $idempotencyKey, $now): array {
                $memberRow = DB::table('members')->where('code', $memberCode)->lockForUpdate()->first();
                if (! $memberRow) {
                    return ['ok' => false, 'status' => 404, 'message' => 'ไม่พบสมาชิก'];
                }

                $pointsBefore = (int) ($memberRow->point_deposit ?? 0);
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

                if (! empty($reward->start_at) && Carbon::parse($reward->start_at)->tz(self::TZ)->gt($now)) {
                    return ['ok' => false, 'status' => 422, 'message' => 'รางวัลนี้ยังไม่ถึงเวลาเริ่ม'];
                }

                if (! empty($reward->end_at) && Carbon::parse($reward->end_at)->tz(self::TZ)->lt($now)) {
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
                $reservedStock = (int) ($reward->reserved_stock ?? 0);
                if (! $stockUnlimited && ($stock <= 0 || ($stock - $reservedStock) <= 0)) {
                    return ['ok' => false, 'status' => 422, 'message' => 'สต๊อกรางวัลหมด'];
                }

                $limitCheck = $this->checkLimitsInTransaction($memberCode, $reward, $now);
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

                $rewardListTouch = $this->touchColumnsFor('rewards_list', $now);
                $redemptionInsertTouch = $this->touchColumnsFor('reward_redemptions', $now, true);
                $redemptionUpdateTouch = $this->touchColumnsFor('reward_redemptions', $now, false);

                $redemptionStatus = 'pending';
                $fulfilledAt = null;
                $messageTitle = 'รับเรื่องแล้ว';
                $messageText = 'ระบบรับรายการแล้ว กรุณารอการดำเนินการ';

                $willAutoClaimCredit = $mode === 'auto'
                    && $autoClaim
                    && $type === 'wallet_credit'
                    && $creditAmount !== null
                    && (float) $creditAmount > 0;

                if ($willAutoClaimCredit) {
                    $messageTitle = 'กำลังดำเนินการ';
                    $messageText = 'ระบบกำลังเติมเครดิตให้';
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
                    'redeemed_at' => $now,
                    'request_ip' => (string) request()->ip(),
                    'request_ua' => (string) request()->userAgent(),
                    'request_source' => 'frontend_api_v1',
                    'handled_by' => null,
                    'idempotency_key' => $idempotencyKey !== '' ? $idempotencyKey : null,
                ], $redemptionInsertTouch));

                $actor = $this->resolveActorForLog();
                if (! $this->memberPointLogRepository || ! method_exists($this->memberPointLogRepository, 'setPoint')) {
                    throw new \RuntimeException('memberPointLogRepository ไม่พร้อมใช้งาน');
                }

                $pointResult = $this->memberPointLogRepository->setPoint([
                    'remark' => 'ใช้พ้อยในการแลกรางวัล (#'.$redemptionId.')',
                    'amount' => $cost,
                    'method' => 'W',
                    'member_code' => $memberCode,
                    'emp_code' => (int) ($actor['code'] ?? 0),
                    'emp_name' => (string) ($actor['name'] ?? 'SYSTEM'),
                ]);

                if (! $this->repoOk($pointResult)) {
                    throw new \RuntimeException($this->repoMessage($pointResult, 'หักแต้มไม่สำเร็จ'));
                }

                $currentPoint = (int) DB::table('members')->where('code', $memberCode)->value('point_deposit');
                if ($currentPoint === $pointsBefore) {
                    $currentPoint = max($pointsBefore - $cost, 0);
                    DB::table('members')->where('code', $memberCode)->update([
                        'point_deposit' => $currentPoint,
                    ]);
                }

                if ($type === 'wallet_gem' && $gemAmount !== null && (float) $gemAmount > 0) {
                    if (! $this->memberDiamondLogRepository || ! method_exists($this->memberDiamondLogRepository, 'setDiamond')) {
                        throw new \RuntimeException('memberDiamondLogRepository ไม่พร้อมใช้งาน');
                    }

                    $gemResult = $this->memberDiamondLogRepository->setDiamond([
                        'remark' => 'รับเพชรจากการแลกรางวัล (#'.$redemptionId.')',
                        'amount' => (float) $gemAmount,
                        'method' => 'D',
                        'member_code' => $memberCode,
                        'emp_code' => (int) ($actor['code'] ?? 0),
                        'emp_name' => (string) ($actor['name'] ?? 'SYSTEM'),
                    ]);

                    if (! $this->repoOk($gemResult)) {
                        throw new \RuntimeException($this->repoMessage($gemResult, 'เพิ่มเพชรไม่สำเร็จ'));
                    }

                    $redemptionStatus = 'fulfilled';
                    $fulfilledAt = $now;
                    $messageTitle = 'สำเร็จ';
                    $messageText = 'แลกรางวัลเรียบร้อย ระบบเพิ่มเพชรให้แล้ว';
                }

                if ($willAutoClaimCredit) {
                    if (! $this->memberCreditLogRepository) {
                        throw new \RuntimeException('memberCreditLogRepository ไม่พร้อมใช้งาน');
                    }

                    $config = core()->getConfigData();

                    $walletPayload = [
                        'refer_code' => $memberCode,
                        'refer_table' => 'members',
                        'kind' => 'SETWALLET',
                        'remark' => 'รับเครดิตจากการแลกรางวัล (#'.$redemptionId.')',
                        'amount' => (float) $creditAmount,
                        'method' => 'D',
                        'member_code' => $memberCode,
                        'emp_code' => (int) ($actor['code'] ?? 0),
                        'emp_name' => (string) ($actor['name'] ?? 'SYSTEM'),
                    ];

                    if (($config->seamless ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWalletSeamless')) {
                        $walletResult = $this->memberCreditLogRepository->setWalletSeamless($walletPayload);
                    } elseif (($config->multigame_open ?? 'N') === 'Y' && method_exists($this->memberCreditLogRepository, 'setWallet')) {
                        $walletResult = $this->memberCreditLogRepository->setWallet($walletPayload);
                    } else {
                        if (! method_exists($this->memberCreditLogRepository, 'setWalletSingle')) {
                            throw new \RuntimeException('memberCreditLogRepository ไม่มีเมธอด setWalletSingle');
                        }
                        $walletResult = $this->memberCreditLogRepository->setWalletSingle($walletPayload);
                    }

                    if (! $this->repoOk($walletResult)) {
                        throw new \RuntimeException($this->repoMessage($walletResult, 'เติมเครดิตไม่สำเร็จ'));
                    }

                    $redemptionStatus = 'fulfilled';
                    $fulfilledAt = $now;
                    $messageTitle = 'สำเร็จ';
                    $messageText = 'แลกรางวัลเรียบร้อย ระบบเติมเครดิตให้แล้ว';
                }

                if (! $stockUnlimited) {
                    DB::table('rewards_list')
                        ->where('id', (int) $reward->id)
                        ->update(array_merge([
                            'stock' => DB::raw('stock - 1'),
                        ], $rewardListTouch));
                }

                DB::table('reward_redemptions')
                    ->where('id', $redemptionId)
                    ->update(array_merge([
                        'status' => $redemptionStatus,
                        'fulfilled_at' => $fulfilledAt,
                    ], $redemptionUpdateTouch));

                return [
                    'ok' => true,
                    'point' => $currentPoint,
                    'mode' => $mode,
                    'redemption_status' => $redemptionStatus,
                    'format' => [
                        'title' => $messageTitle,
                        'msg' => $messageText,
                        'img' => '',
                    ],
                    'redemption_id' => $redemptionId,
                ];
            });

            if (! ($result['ok'] ?? false)) {
                return $this->sendError((string) ($result['message'] ?? 'ทำรายการไม่สำเร็จ'), (int) ($result['status'] ?? 500));
            }

            return $this->sendResponseNew([
                'point' => (int) $result['point'],
                'mode' => (string) $result['mode'],
                'redemption_status' => (string) ($result['redemption_status'] ?? 'pending'),
                'format' => $result['format'] ?? ['title' => 'สำเร็จ', 'msg' => '', 'img' => ''],
                'redemption_id' => $result['redemption_id'] ?? null,
            ], 'ทำรายการแลกรางวัลเรียบร้อย');
        } catch (\Throwable $e) {
            Log::error('Frontend reward redeem failed', [
                'member_code' => $memberCode,
                'reward_id' => $rewardId,
                'idempotency_key' => $idempotencyKey,
                'exception' => $e,
            ]);

            return $this->sendError('ระบบขัดข้อง กรุณาลองใหม่อีกครั้ง', 500);
        }
    }

    public function history(RewardHistoryRequest $request): JsonResponse
    {
        try {
            $member = $request->user() ?: $request->user('customer');
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $memberCode = (int) $member->code;
            $page = max((int) $request->input('page', 1), 1);
            $perPage = min(max((int) $request->input('per_page', 20), 1), 50);
            $queryKeyword = trim((string) $request->input('q', ''));
            $status = trim((string) $request->input('status', ''));
            $rewardType = trim((string) $request->input('reward_type', ''));
            $mode = trim((string) $request->input('mode', ''));

            $query = DB::table('reward_redemptions')->where('member_id', $memberCode);

            if ($queryKeyword !== '') {
                $query->where(function ($builder) use ($queryKeyword): void {
                    $builder->where('reward_name_snapshot', 'like', "%{$queryKeyword}%")
                        ->orWhere('reward_code_snapshot', 'like', "%{$queryKeyword}%");
                });
            }

            if ($status !== '') {
                $query->where('status', $status);
            }

            if ($rewardType !== '') {
                $query->where('reward_type_snapshot', $rewardType);
            }

            if ($mode !== '') {
                $query->where('fulfillment_mode_snapshot', $mode);
            }

            $query->orderByRaw('COALESCE(redeemed_at, created_at, updated_at) DESC')
                ->orderByDesc('id');

            $paginator = $query->paginate($perPage, ['*'], 'page', $page);

            $items = collect($paginator->items())->map(function ($item): array {
                $formatDate = function ($value): ?string {
                    if (empty($value)) {
                        return null;
                    }

                    try {
                        return Carbon::parse($value)->tz(self::TZ)->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        return (string) $value;
                    }
                };

                $redeemedAt = $formatDate($item->redeemed_at ?? null);
                $createdAt = $formatDate($item->created_at ?? null);

                return [
                    'id' => (int) ($item->id ?? 0),
                    'reward_id' => (int) ($item->reward_id ?? 0),
                    'member_id' => (int) ($item->member_id ?? 0),
                    'reward_code_snapshot' => (string) ($item->reward_code_snapshot ?? ''),
                    'reward_name_snapshot' => (string) ($item->reward_name_snapshot ?? ''),
                    'point_cost_snapshot' => (int) ($item->point_cost_snapshot ?? 0),
                    'reward_type_snapshot' => (string) ($item->reward_type_snapshot ?? ''),
                    'fulfillment_mode_snapshot' => (string) ($item->fulfillment_mode_snapshot ?? ''),
                    'credit_amount_snapshot' => $item->credit_amount_snapshot ?? null,
                    'gem_amount_snapshot' => $item->gem_amount_snapshot ?? null,
                    'payload_snapshot' => $this->safeJsonToArray($item->payload_snapshot ?? null),
                    'status' => (string) ($item->status ?? 'pending'),
                    'note_user' => (string) ($item->note_user ?? ''),
                    'note_staff' => (string) ($item->note_staff ?? ''),
                    'contact_channel' => (string) ($item->contact_channel ?? ''),
                    'contact_value' => (string) ($item->contact_value ?? ''),
                    'redeemed_at' => $redeemedAt,
                    'fulfilled_at' => $formatDate($item->fulfilled_at ?? null),
                    'cancelled_at' => $formatDate($item->cancelled_at ?? null),
                    'rejected_at' => $formatDate($item->rejected_at ?? null),
                    'refunded_at' => $formatDate($item->refunded_at ?? null),
                    'created_at' => $createdAt,
                    'handled_by' => $item->handled_by !== null ? (int) $item->handled_by : null,
                    'refunded_by' => $item->refunded_by !== null ? (int) $item->refunded_by : null,
                    'point_debited' => $this->toBool($item->point_debited ?? false),
                    'request_ip' => (string) ($item->request_ip ?? ''),
                    'request_ua' => (string) ($item->request_ua ?? ''),
                    'request_source' => (string) ($item->request_source ?? ''),
                    'timeline_key' => substr((string) ($redeemedAt ?: $createdAt ?: ''), 0, 10),
                ];
            })->values();

            $timeline = $items
                ->groupBy('timeline_key')
                ->map(function (Collection $group, string $date): array {
                    return [
                        'date' => $date,
                        'count' => $group->count(),
                        'items' => $group->map(function (array $item): array {
                            unset($item['timeline_key']);

                            return $item;
                        })->values()->all(),
                    ];
                })
                ->values()
                ->all();

            $normalizedItems = $items->map(function (array $item): array {
                unset($item['timeline_key']);

                return $item;
            })->all();

            return $this->sendResponseNew([
                'items' => $normalizedItems,
                'timeline' => $timeline,
                'meta' => [
                    'current_page' => (int) $paginator->currentPage(),
                    'last_page' => (int) $paginator->lastPage(),
                    'per_page' => (int) $paginator->perPage(),
                    'total' => (int) $paginator->total(),
                    'from' => (int) ($paginator->firstItem() ?? 0),
                    'to' => (int) ($paginator->lastItem() ?? 0),
                ],
                'filters' => [
                    'q' => $queryKeyword,
                    'status' => $status,
                    'reward_type' => $rewardType,
                    'mode' => $mode,
                ],
            ], 'ดึงประวัติการแลกรางวัลสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงประวัติการแลกรางวัลได้ในขณะนี้', 422);
        }
    }

    private function checkLimitsInTransaction(int $memberCode, object $reward, Carbon $now): array
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
                    $lastAt = Carbon::parse($last->created_at)->tz(self::TZ);
                    $diffMinutes = $lastAt->diffInMinutes($now);
                    if ($diffMinutes < $cooldown) {
                        $remaining = max($cooldown - $diffMinutes, 1);

                        return ['ok' => false, 'message' => 'กรุณารออีก '.$remaining.' นาที แล้วค่อยแลกใหม่'];
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

            [$start, $end] = $this->periodRange($now, $period);

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

                return ['ok' => false, 'message' => 'คุณแลกรางวัลนี้ครบจำนวนสำหรับ'.$label.'แล้ว'];
            }
        }

        return ['ok' => true];
    }

    private function periodRange(Carbon $now, string $period): array
    {
        $value = $now->copy()->tz(self::TZ);

        if ($period === 'week') {
            return [$value->copy()->startOfWeek(Carbon::MONDAY), $value->copy()->endOfWeek(Carbon::SUNDAY)];
        }

        if ($period === 'month') {
            return [$value->copy()->startOfMonth(), $value->copy()->endOfMonth()];
        }

        return [$value->copy()->startOfDay(), $value->copy()->endOfDay()];
    }

    private function safeJsonToArray(mixed $value): array
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

    private function toPublicImageUrl(string $image): string
    {
        $value = trim($image);
        if ($value === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            return $value;
        }

        $value = ltrim($value, '/');
        if (str_starts_with($value, 'storage/')) {
            return url('/'.$value);
        }

        return url('/storage/'.$value);
    }

    private function toBool(mixed $value): bool
    {
        if ($value === true || $value === 1 || $value === '1' || $value === 'Y') {
            return true;
        }

        $normalized = trim(strtolower((string) ($value ?? '')));

        return in_array($normalized, ['true', 'yes', 'y', 'on'], true);
    }

    private function resolveActorForLog(): array
    {
        return ['code' => 0, 'name' => 'SYSTEM'];
    }

    private function repoOk(mixed $response): bool
    {
        if ($response === true) {
            return true;
        }

        if (! is_array($response) && ! is_object($response)) {
            return false;
        }

        $success = data_get($response, 'success', null);
        $ok = data_get($response, 'ok', null);
        $status = data_get($response, 'status', null);

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

    private function repoMessage(mixed $response, string $fallback): string
    {
        $message = data_get($response, 'message') ?? data_get($response, 'msg') ?? data_get($response, 'error');

        return is_string($message) && trim($message) !== '' ? trim($message) : $fallback;
    }

    private function touchColumnsFor(string $table, Carbon $now, bool $includeCreatedAt = false): array
    {
        $cacheKey = 'frontend_api:reward:touchcols:'.$table.':'.($includeCreatedAt ? '1' : '0');

        return Cache::remember($cacheKey, 600, function () use ($table, $now, $includeCreatedAt): array {
            $columns = [];

            try {
                if ($includeCreatedAt && Schema::hasColumn($table, 'created_at')) {
                    $columns['created_at'] = $now;
                }

                if (Schema::hasColumn($table, 'updated_at')) {
                    $columns['updated_at'] = $now;
                }
            } catch (\Throwable $e) {
                return [];
            }

            return $columns;
        });
    }
}
