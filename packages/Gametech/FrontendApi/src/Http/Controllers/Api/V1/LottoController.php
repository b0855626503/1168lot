<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Lotto\Enums\BetType;
use Gametech\Lotto\Exceptions\LottoPackageException;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoGroupPackage;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LottoTicket;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Services\BetService;
use Gametech\Lotto\Services\LottoPackageSelectionService;
use Gametech\Lotto\Services\WalletTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LottoController extends BaseController
{
    public function marketsLatestByGroup(Request $request): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);
            $groupId = (int) $request->query('group_id', 0);
            $groupCode = trim((string) $request->query('group_code', $request->query('code', '')));
            $groupName = trim((string) ($request->query('group_name', $request->query('group', ''))));

            $groupsQuery = LotteryGroup::query()
                ->where('is_enabled', true)
                ->orderBy('sort')
                ->orderBy('name');

            if ($groupId > 0) {
                $groupsQuery->where('id', $groupId);
            }

            if ($groupCode !== '') {
                $groupsQuery->where('code', $groupCode);
            }

            if ($groupName !== '') {
                $groupsQuery->where(function ($query) use ($groupName): void {
                    $query->where('name', 'like', '%' . $groupName . '%')
                        ->orWhere('name_en', 'like', '%' . $groupName . '%')
                        ->orWhere('name_kh', 'like', '%' . $groupName . '%')
                        ->orWhere('name_laos', 'like', '%' . $groupName . '%')
                        ->orWhere('code', 'like', '%' . $groupName . '%');
                });
            }

            $groups = $groupsQuery->get(['id', 'name', 'name_en', 'name_kh', 'name_laos', 'description', 'code', 'logo', 'icon']);

            $marketsQuery = LotteryMarket::query()
                ->where('is_enabled', true)
                ->orderBy('group_id')
                ->orderBy('name');

            if ($groups->isNotEmpty()) {
                $marketsQuery->whereIn('group_id', $groups->pluck('id')->all());
            } else {
                $marketsQuery->whereRaw('1 = 0');
            }

            $markets = $marketsQuery->get([
                'id',
                'group_id',
                'name',
                'name_en',
                'name_kh',
                'name_laos',
                'logo',
                'icon',
                'is_enabled',
            ]);

            $latestNonDraftByMarket = LottoDraw::query()
                ->where('status', '!=', 'draft')
                ->selectRaw('market_id, MAX(id) as id')
                ->groupBy('market_id')
                ->get()
                ->mapWithKeys(static fn ($row): array => [(int) $row->market_id => (int) $row->id])
                ->all();

            $latestOpenByMarket = LottoDraw::query()
                ->where('status', 'open')
                ->selectRaw('market_id, MAX(id) as id')
                ->groupBy('market_id')
                ->get()
                ->mapWithKeys(static fn ($row): array => [(int) $row->market_id => (int) $row->id])
                ->all();

            $latestDrawIds = collect($latestNonDraftByMarket)
                ->map(static function (int $drawId, int $marketId) use ($latestOpenByMarket): int {
                    return (int) ($latestOpenByMarket[$marketId] ?? $drawId);
                })
                ->filter(static fn (int $id) => $id > 0)
                ->values()
                ->all();

            $latestDrawMap = LottoDraw::query()
                ->whereIn('id', $latestDrawIds)
                ->get(['id', 'market_id', 'draw_date', 'open_at', 'close_at', 'result_at', 'status', 'result_number'])
                ->keyBy(static fn (LottoDraw $draw): int => (int) $draw->market_id);

            $marketRowsByGroup = $markets
                ->groupBy(static fn (LotteryMarket $market): int => (int) $market->group_id);

            $rows = $groups->map(function (LotteryGroup $group) use ($marketRowsByGroup, $latestDrawMap, $language): array {
                $groupMarkets = $marketRowsByGroup->get((int) $group->id, collect());
                $groupDescription = $this->localizedDescriptionByLanguage((string) ($group->description ?? ''), $language);

                return [
                    'group_id' => (int) $group->id,
                    'group_code' => (string) ($group->code ?? ''),
                    'group_name' => $this->localizedNameByLanguage([
                        'name' => (string) $group->name,
                        'name_en' => (string) ($group->name_en ?? ''),
                        'name_kh' => (string) ($group->name_kh ?? ''),
                        'name_laos' => (string) ($group->name_laos ?? ''),
                    ], $language, 'name'),
                    'description' => $groupDescription,
                    'group_logo' => (string) ($group->logo ?? ''),
                    'group_icon' => (string) ($group->icon ?? ''),
                    'group_image' => (string) (($group->logo ?: $group->icon) ?? ''),
                    'markets' => $groupMarkets->map(function (LotteryMarket $market) use ($latestDrawMap, $language): array {
                        $draw = $latestDrawMap->get((int) $market->id);
                        $resultNumber = is_array($draw?->result_number) ? $draw->result_number : [];
                        $status = $draw ? $this->latestDrawStatus($draw) : 'draft';

                        return [
                            'market_id' => (int) $market->id,
                            'market_name' => $this->localizedNameByLanguage([
                                'name' => (string) $market->name,
                                'name_en' => (string) ($market->name_en ?? ''),
                                'name_kh' => (string) ($market->name_kh ?? ''),
                                'name_laos' => (string) ($market->name_laos ?? ''),
                            ], $language, 'name'),
                            'market_logo' => (string) ($market->logo ?? ''),
                            'market_icon' => (string) ($market->icon ?? ''),
                            'is_enabled' => (bool) $market->is_enabled,
                            'latest_draw' => [
                                'draw_id' => (int) ($draw?->id ?? 0),
                                'draw_date' => $draw?->draw_date ? $draw->draw_date->format('Y-m-d') : null,
                                'open_at' => $draw?->open_at ? $draw->open_at->format('Y-m-d H:i:s') : null,
                                'close_at' => $draw?->close_at ? $draw->close_at->format('Y-m-d H:i:s') : null,
                                'result_at' => $draw?->result_at ? $draw->result_at->format('Y-m-d H:i:s') : null,
                                'status' => $status,
                                'status_label' => $this->latestDrawStatusLabel($status),
                                'is_open_bet' => $status === 'open',
                                'result_top_3' => (string) ($resultNumber['top_3'] ?? ''),
                                'result_bottom_2' => (string) ($resultNumber['bottom_2'] ?? ($resultNumber['last_2_digits'] ?? '')),
                            ],
                        ];
                    })->values()->all(),
                ];
            })->values()->all();

            return $this->sendResponse([
                'language' => $language,
                'filters' => [
                    'group_id' => $groupId > 0 ? $groupId : null,
                    'group_code' => $groupCode !== '' ? $groupCode : null,
                    'group_name' => $groupName !== '' ? $groupName : null,
                ],
                'groups' => $rows,
            ], 'ดึงรายการหวยพร้อมงวดล่าสุดสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการหวยพร้อมงวดล่าสุดได้ในขณะนี้', 422);
        }
    }

    public function draws(Request $request)
    {
        try {
            $language = $this->requestLanguage($request);
            $limit = max(1, min((int) $request->input('limit', 20), 100));

            $latestDrawIds = LottoDraw::query()
                ->where('status', '!=', 'draft')
                ->selectRaw('MAX(id) as id')
                ->groupBy('market_id')
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->values()
                ->all();

            $rows = LottoDraw::query()
                ->with('market:id,name')
                ->whereIn('id', $latestDrawIds)
                ->orderByDesc('draw_date')
                ->orderByDesc('id')
                ->limit($limit)
                ->get()
                ->map(static function (LottoDraw $draw): array {
                    return [
                        'id' => (int) $draw->id,
                        'market_id' => (int) $draw->market_id,
                        'market_name' => $draw->market?->name,
                        'draw_date' => optional($draw->draw_date)->toDateString(),
                        'open_at' => optional($draw->open_at)->toDateTimeString(),
                        'close_at' => optional($draw->close_at)->toDateTimeString(),
                        'status' => (string) $draw->status,
                    ];
                })
                ->values()
                ->all();

            return $this->localizeDrawsResponse(
                $this->sendResponse($rows, 'ดึงรายการงวดสำเร็จ'),
                $language
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการงวดได้ในขณะนี้', 422);
        }
    }

    public function draw(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $draw = LottoDraw::query()
                ->with(['market:id,name,group_id', 'betSettings'])
                ->find($id);

            if (! $draw) {
                return $this->sendError('ไม่พบงวดที่ระบุ', 404);
            }

            $response = $this->sendResponse([
                'id' => (int) $draw->id,
                'market' => [
                    'id' => (int) $draw->market_id,
                    'name' => $draw->market?->name,
                    'group_id' => (int) ($draw->market?->group_id ?? 0),
                ],
                'draw_date' => optional($draw->draw_date)->toDateString(),
                'open_at' => optional($draw->open_at)->toDateTimeString(),
                'close_at' => optional($draw->close_at)->toDateTimeString(),
                'status' => (string) $draw->status,
                'result_number' => $draw->result_number,
                'bet_settings' => $draw->betSettings->map(fn ($setting): array => [
                    'bet_type' => (string) $setting->bet_type,
                    'bet_type_label' => BetType::label((string) $setting->bet_type),
                    'is_enabled' => (bool) $setting->is_enabled,
                    'min_bet' => (float) $setting->min_bet,
                    'max_bet' => (float) $setting->max_bet,
                    'max_per_number' => (float) $setting->max_per_number,
                ])->values()->all(),
            ], 'ดึงรายละเอียดงวดสำเร็จ');

            return $this->localizeDrawResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดงวดได้ในขณะนี้', 422);
        }
    }

    public function bet(Request $request)
    {
        try {
            $member = $this->resolveCustomerMember($request);
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $validated = validator($request->all(), [
                'draw_id' => ['required', 'integer', 'exists:lotto_draws,id'],
                'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.bet_type' => ['required', 'string'],
                'items.*.number' => ['required', 'string'],
                'items.*.amount' => ['required', 'numeric', 'min:1'],
            ])->validate();

            $ticket = app(BetService::class)->placeBet(
                (int) $member->code,
                (int) $validated['draw_id'],
                (int) $validated['package_id'],
                (array) $validated['items']
            );

            return $this->sendResponse([
                'ticket_id' => (int) $ticket->id,
                'total_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
                'total_bet_amount' => (float) ($ticket->total_bet_amount ?? 0),
                'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
                'total_net_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
                'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
                'status' => (string) $ticket->status,
                'item_count' => $ticket->items->count(),
            ], 'แทงหวยสำเร็จ');
        } catch (LottoPackageException $exception) {
            return $this->sendResponseFail([
                'error_code' => $exception->errorCode(),
            ], $exception->getMessage(), $exception->httpStatus());
        } catch (\Throwable $e) {
            return $this->sendError($e->getMessage(), 422);
        }
    }

    public function tickets(Request $request)
    {
        try {
            $language = $this->requestLanguage($request);
            $memberId = $this->resolveMemberId($request);
            if (! $memberId) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $tickets = $this->ticketQuery($memberId)
                ->orderByDesc('id')
                ->limit($this->resolveTicketLimit($request))
                ->get();

            $response = $this->sendResponse(
                $tickets->map(fn (LottoTicket $ticket): array => $this->mapTicketSummary($ticket))->values()->all(),
                'ดึงประวัติโพยสำเร็จ'
            );

            return $this->localizeTicketsResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการโพยได้ในขณะนี้', 422);
        }
    }

    public function packages(int $groupId): JsonResponse
    {
        try {
            $packages = LottoGroupPackage::query()
                ->with(['betSettings' => static function ($query) {
                    $query->where('is_enabled', true)->orderBy('bet_type');
                }])
                ->where('group_id', $groupId)
                ->where('is_active', true)
                ->orderBy('id')
                ->get();

            return $this->sendResponse(
                $packages->map(static function (LottoGroupPackage $package): array {
                    return [
                        'id' => (int) $package->id,
                        'group_id' => (int) $package->group_id,
                        'name' => (string) $package->name,
                        'image' => (string) ($package->image ?? ''),
                        'is_active' => (bool) $package->is_active,
                        'bet_settings' => $package->betSettings->map(static function ($setting): array {
                            return [
                                'bet_type' => (string) $setting->bet_type,
                                'payout' => (float) $setting->payout,
                                'discount_percent' => (float) $setting->discount_percent,
                            ];
                        })->values()->all(),
                    ];
                })->values()->all(),
                'ดึง package สำเร็จ'
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึง package ได้ในขณะนี้', 422);
        }
    }

    public function selectPackage(Request $request, int $groupId): JsonResponse
    {
        try {
            $member = $this->resolveCustomerMember($request);
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $validated = validator($request->all(), [
                'package_id' => ['required', 'integer', 'exists:lotto_group_packages,id'],
            ])->validate();

            $package = LottoGroupPackage::query()->find((int) $validated['package_id']);
            if (! $package || (int) $package->group_id !== $groupId) {
                return $this->sendResponseFail(['error_code' => 'PACKAGE_NOT_IN_GROUP'], 'package ไม่อยู่ใน group เดียวกัน', 400);
            }

            if (! (bool) $package->is_active) {
                return $this->sendResponseFail(['error_code' => 'PACKAGE_INACTIVE'], 'package ถูกปิดใช้งาน', 409);
            }

            app(LottoPackageSelectionService::class)->select((int) $member->code, $groupId, (int) $package->id);

            return $this->sendResponse([
                'group_id' => $groupId,
                'package_id' => (int) $package->id,
                'selected' => true,
            ], 'เลือก package สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเลือก package ได้ในขณะนี้', 422);
        }
    }

    public function selectedPackage(Request $request, int $groupId): JsonResponse
    {
        try {
            $member = $this->resolveCustomerMember($request);
            if (! $member || ! isset($member->code)) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $selectionService = app(LottoPackageSelectionService::class);
            $selectedPackageId = $selectionService->getSelectedPackageId((int) $member->code, $groupId);

            if ($selectedPackageId === null) {
                return $this->sendResponseNew([
                    'data' => null,
                    'selected' => false,
                ], 'ยังไม่ได้เลือก package');
            }

            $package = LottoGroupPackage::query()
                ->with(['betSettings' => static function ($query) {
                    $query->where('is_enabled', true)->orderBy('bet_type');
                }])
                ->find($selectedPackageId);

            if (! $package || (int) $package->group_id !== $groupId || ! (bool) $package->is_active) {
                return $this->sendResponseNew([
                    'data' => null,
                    'selected' => false,
                ], 'ยังไม่ได้เลือก package');
            }

            return $this->sendResponseNew([
                'data' => [
                    'group_id' => $groupId,
                    'package_id' => (int) $package->id,
                    'name' => (string) $package->name,
                    'image' => (string) ($package->image ?? ''),
                    'bet_settings' => $package->betSettings->map(static function ($setting): array {
                        return [
                            'bet_type' => (string) $setting->bet_type,
                            'payout' => (float) $setting->payout,
                            'discount_percent' => (float) $setting->discount_percent,
                        ];
                    })->values()->all(),
                ],
                'selected' => true,
            ], 'ดึงสถานะ package ที่เลือกสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงสถานะ package ที่เลือกได้ในขณะนี้', 422);
        }
    }

    public function ticket(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $memberId = $this->resolveMemberId($request);
            if (! $memberId) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            $ticket = $this->ticketQuery($memberId)->find($id);
            if (! $ticket) {
                return $this->sendError('ไม่พบโพยที่ระบุ', 404);
            }

            $payload = $this->mapTicketSummary($ticket);
            $payload['items'] = $ticket->items->map(static function ($item): array {
                $rawStatus = $item->result_status;
                $itemResultStatus = in_array($rawStatus, ['win', 'lose'], true)
                    ? (string) $rawStatus
                    : 'pending';

                return [
                    'bet_type' => (string) $item->bet_type,
                    'bet_type_label' => BetType::label((string) $item->bet_type),
                    'number' => (string) $item->number,
                    'amount' => (float) $item->amount,
                    'payout_at_time' => (float) $item->payout_at_time,
                    'discount_percent_at_time' => (float) ($item->discount_percent_at_time ?? 0),
                    'discount_amount_at_time' => (float) ($item->discount_amount_at_time ?? 0),
                    'payable_amount_at_time' => (float) ($item->payable_amount_at_time ?? 0),
                    'potential_win_amount_at_time' => (float) ($item->potential_win_amount_at_time ?? 0),
                    'result_status' => $itemResultStatus,
                    'raw_result_status' => $rawStatus,
                    'is_winner' => $itemResultStatus === 'win',
                    'win_amount' => (float) ($item->win_amount ?? 0),
                ];
            })->values()->all();

            $response = $this->sendResponse($payload, 'ดึงรายละเอียดโพยสำเร็จ');

            return $this->localizeTicketResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดโพยได้ในขณะนี้', 422);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $memberId = $this->resolveMemberId($request);
            if (! $memberId) {
                return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
            }

            DB::transaction(function () use ($id, $memberId): void {
                $ticket = $this->ticketQuery($memberId)
                    ->with(['draw', 'items'])
                    ->lockForUpdate()
                    ->find($id);

                if (! $ticket) {
                    throw new \InvalidArgumentException('ticket_not_found');
                }

                if ((string) $ticket->status !== 'active') {
                    throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้');
                }

                if ((string) ($ticket->draw->status ?? '') !== 'open') {
                    throw new \RuntimeException('ยกเลิกได้เฉพาะโพยที่อยู่ในงวดเปิดรับเท่านั้น');
                }

                $this->guardCancelWindow($ticket);
                $this->guardDailyCancelLimit($memberId);

                foreach ($ticket->items as $item) {
                    $exposure = LottoNumberExposure::query()
                        ->where('draw_id', (int) $ticket->draw_id)
                        ->where('bet_type', (string) $item->bet_type)
                        ->where('number', (string) $item->number)
                        ->lockForUpdate()
                        ->first();

                    if (! $exposure) {
                        continue;
                    }

                    $nextAmount = max(0, (float) $exposure->sold_amount - (float) $item->amount);
                    $exposure->update(['sold_amount' => $nextAmount]);
                }

                $debitTxn = Schema::hasTable('wallet_transactions')
                    ? DB::table('wallet_transactions')
                        ->where('member_id', $memberId)
                        ->where('ref_type', 'LOTTO_BET')
                        ->where('ref_id', (int) $ticket->id)
                        ->orderByDesc('id')
                        ->first(['id'])
                    : null;

                $refundAmount = (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0);
                app(WalletTransactionService::class)->creditMemberBalance(
                    memberId: $memberId,
                    amount: $refundAmount,
                    refType: 'LOTTO_CANCEL',
                    refId: (int) $ticket->id,
                    refCode: (string) $ticket->id,
                    groupCode: 'LOTTO_CANCEL_' . $ticket->id . '_' . now()->format('YmdHis'),
                    relatedTxnId: isset($debitTxn->id) ? (int) $debitTxn->id : null,
                    meta: [
                        'draw_id' => (int) $ticket->draw_id,
                        'ticket_id' => (int) $ticket->id,
                    ],
                    createdByType: 'member',
                    createdById: $memberId,
                    description: 'คืนเงินจากการยกเลิกโพยหวย'
                );

                $updatePayload = [
                    'status' => 'cancelled',
                    'cancelled_at' => now(),
                    'refund_amount' => $refundAmount,
                ];

                if (Schema::hasColumn('lotto_tickets', 'cancelled_by')) {
                    $updatePayload['cancelled_by'] = $memberId;
                }

                $ticket->update($updatePayload);
            });

            return $this->sendSuccess('ยกเลิกโพยสำเร็จ');
        } catch (\InvalidArgumentException $e) {
            return $this->sendError('ไม่พบโพยที่ระบุ', 404);
        } catch (\RuntimeException $e) {
            return $this->sendError($e->getMessage(), 422);
        } catch (\Throwable $e) {
            return $this->sendError('ยกเลิกโพยไม่สำเร็จ', 500);
        }
    }

    public function bettingContext(Request $request, int $marketId): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);
            $marketMap = $this->marketMapByIds([$marketId]);
            $market = $marketMap[$marketId] ?? null;
            if (! is_array($market)) {
                return $this->sendError('ไม่พบหวยที่ระบุ', 404);
            }

            $draw = LottoDraw::query()
                ->select(['id', 'market_id', 'draw_date', 'open_at', 'close_at', 'result_at', 'status', 'updated_at'])
                ->where('market_id', $marketId)
                ->orderByRaw("
                    CASE status
                        WHEN 'open' THEN 0
                        WHEN 'draft' THEN 1
                        WHEN 'closed' THEN 2
                        WHEN 'resulted' THEN 3
                        ELSE 4
                    END
                ")
                ->orderByDesc('draw_date')
                ->orderByDesc('id')
                ->first();

            if (! $draw) {
                return $this->sendError('ยังไม่มีงวดสำหรับหวยที่ระบุ', 404);
            }

            $betSettings = LottoDrawBetSetting::query()
                ->select(['draw_id', 'bet_type', 'min_bet', 'max_bet', 'max_per_number', 'payout', 'discount_percent', 'is_enabled'])
                ->where('draw_id', (int) $draw->id)
                ->where('is_enabled', true)
                ->orderBy('bet_type')
                ->get();

            if ($betSettings->isEmpty()) {
                $betSettings = LottoMarketBetSetting::query()
                    ->select(['market_id', 'bet_type', 'min_bet', 'max_bet', 'max_per_number', 'payout', 'discount_percent', 'is_enabled'])
                    ->where('market_id', $marketId)
                    ->where('is_enabled', true)
                    ->orderBy('bet_type')
                    ->get()
                    ->map(static function (LottoMarketBetSetting $setting) use ($draw) {
                        return new LottoDrawBetSetting([
                            'draw_id' => (int) $draw->id,
                            'bet_type' => (string) $setting->bet_type,
                            'min_bet' => $setting->min_bet,
                            'max_bet' => $setting->max_bet,
                            'max_per_number' => $setting->max_per_number,
                            'payout' => $setting->payout,
                            'discount_percent' => $setting->discount_percent,
                            'is_enabled' => true,
                        ]);
                    });
            }

            $blockedNumbers = LottoNumberBlock::query()
                ->select(['draw_id', 'bet_type', 'number', 'mode', 'reason', 'blocked_at'])
                ->where('draw_id', (int) $draw->id)
                ->orderBy('bet_type')
                ->orderBy('number')
                ->get();

            $exposureScope = strtolower((string) $request->query('exposure_scope', 'blocked'));
            $exposureQuery = LottoNumberExposure::query()
                ->select(['draw_id', 'bet_type', 'number', 'sold_amount'])
                ->where('draw_id', (int) $draw->id);

            if ($exposureScope !== 'all') {
                $blockedPairs = $blockedNumbers
                    ->map(static fn (LottoNumberBlock $row): string => (string) $row->bet_type . ':' . (string) $row->number)
                    ->unique()
                    ->values()
                    ->all();

                if (empty($blockedPairs)) {
                    $exposures = collect();
                } else {
                    $exposureQuery->whereIn(DB::raw("CONCAT(bet_type, ':', number)"), $blockedPairs);
                    $exposures = $exposureQuery
                        ->orderBy('bet_type')
                        ->orderBy('number')
                        ->get();
                }
            } else {
                $exposures = $exposureQuery
                    ->orderByDesc('sold_amount')
                    ->orderBy('bet_type')
                    ->orderBy('number')
                    ->get();
            }

            $limitRows = $betSettings->map(static fn (LottoDrawBetSetting $setting): array => [
                'bet_type' => (string) $setting->bet_type,
                'min_bet' => (float) $setting->min_bet,
                'max_bet' => (float) $setting->max_bet,
                'max_per_number' => (float) $setting->max_per_number,
                'payout' => (float) $setting->payout,
                'discount_percent' => (float) ($setting->discount_percent ?? 0),
            ])->values();

            $minBet = $limitRows->isNotEmpty() ? (float) $limitRows->min('min_bet') : 0.0;
            $maxBet = $limitRows->isNotEmpty() ? (float) $limitRows->max('max_bet') : 0.0;
            $maxPerNumber = $limitRows->isNotEmpty() ? (float) $limitRows->max('max_per_number') : 0.0;

            $blockedRows = $blockedNumbers->map(static fn (LottoNumberBlock $row): array => [
                'bet_type' => (string) $row->bet_type,
                'number' => (string) $row->number,
                'mode' => (string) $row->mode,
                'reason' => (string) ($row->reason ?? ''),
                'blocked_at' => $row->blocked_at ? $row->blocked_at->format('Y-m-d H:i:s') : null,
            ])->values();

            $exposureRows = $exposures->map(static fn (LottoNumberExposure $row): array => [
                'bet_type' => (string) $row->bet_type,
                'number' => (string) $row->number,
                'sold_amount' => (float) $row->sold_amount,
            ])->values();

            $status = (string) $draw->status;
            $version = sha1(implode('|', [
                (string) $marketId,
                (string) $draw->id,
                $status,
                (string) optional($draw->updated_at)->timestamp,
                (string) $blockedRows->count(),
                (string) $exposureRows->count(),
                (string) $exposureRows->sum('sold_amount'),
            ]));

            return $this->sendResponse([
                'market' => [
                    'id' => (int) $market['id'],
                    'name' => $this->localizedMarketName($market, $language),
                    'group_id' => (int) $market['group_id'],
                    'group_name' => $this->localizedGroupName($market, $language),
                    'logo' => (string) ($market['logo'] ?? ''),
                    'icon' => (string) ($market['icon'] ?? ''),
                ],
                'draw' => [
                    'id' => (int) $draw->id,
                    'draw_date' => optional($draw->draw_date)->format('Y-m-d'),
                    'open_at' => optional($draw->open_at)->format('Y-m-d H:i:s'),
                    'close_at' => optional($draw->close_at)->format('Y-m-d H:i:s'),
                    'result_at' => optional($draw->result_at)->format('Y-m-d H:i:s'),
                    'status' => $status,
                    'status_label' => $this->drawStatusLabel($status),
                    'is_open_bet' => $status === 'open',
                ],
                'limits' => [
                    'min_bet' => $minBet,
                    'max_bet' => $maxBet,
                    'max_per_number' => $maxPerNumber,
                    'bet_types' => $limitRows->all(),
                ],
                'blocked_numbers' => [
                    'count' => $blockedRows->count(),
                    'items' => $blockedRows->all(),
                ],
                'number_exposure' => [
                    'scope' => $exposureScope === 'all' ? 'all' : 'blocked',
                    'count' => $exposureRows->count(),
                    'items' => $exposureRows->all(),
                ],
                'version' => $version,
                'server_time' => now()->format('Y-m-d H:i:s'),
                'language' => $language,
            ], 'ดึง betting context สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึง betting context ได้ในขณะนี้', 422);
        }
    }

    public function marketResults(Request $request, int $marketId): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);
            $marketMap = $this->marketMapByIds([$marketId]);
            $market = $marketMap[$marketId] ?? null;
            if (! is_array($market)) {
                return $this->sendError('ไม่พบหวยที่ระบุ', 404);
            }

            $limit = $this->resolveResultsLimit($request);
            $page = max(1, (int) $request->query('page', 1));

            $query = LottoDraw::query()
                ->select(['id', 'market_id', 'draw_date', 'result_at', 'status', 'result_number'])
                ->where('market_id', $marketId)
                ->where('status', 'resulted')
                ->orderByDesc('draw_date')
                ->orderByDesc('id');

            $total = (clone $query)->count();
            $rows = $query->forPage($page, $limit)->get();
            $history = $rows->map(fn (LottoDraw $draw): array => $this->mapResultDraw($draw))->values();
            $latest = $history->first();

            return $this->sendResponse([
                'market' => [
                    'id' => (int) $market['id'],
                    'name' => $this->localizedMarketName($market, $language),
                    'group_id' => (int) $market['group_id'],
                    'group_name' => $this->localizedGroupName($market, $language),
                    'logo' => (string) ($market['logo'] ?? ''),
                    'icon' => (string) ($market['icon'] ?? ''),
                ],
                'latest_result' => is_array($latest) ? $latest : null,
                'history' => $history->all(),
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'count' => $history->count(),
                    'total' => $total,
                    'has_more' => ($page * $limit) < $total,
                ],
                'language' => $language,
            ], 'ดึงผลย้อนหลังสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงผลย้อนหลังได้ในขณะนี้', 422);
        }
    }

    public function drawResult(Request $request, int $marketId, int $drawId): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);
            $marketMap = $this->marketMapByIds([$marketId]);
            $market = $marketMap[$marketId] ?? null;
            if (! is_array($market)) {
                return $this->sendError('ไม่พบหวยที่ระบุ', 404);
            }

            $draw = LottoDraw::query()
                ->select(['id', 'market_id', 'draw_date', 'result_at', 'status', 'result_number'])
                ->where('id', $drawId)
                ->where('market_id', $marketId)
                ->first();

            if (! $draw) {
                return $this->sendError('ไม่พบงวดที่ระบุ', 404);
            }

            if ((string) $draw->status !== 'resulted') {
                return $this->sendError('งวดยังไม่ออกผล', 422);
            }

            return $this->sendResponse([
                'market' => [
                    'id' => (int) $market['id'],
                    'name' => $this->localizedMarketName($market, $language),
                    'group_id' => (int) $market['group_id'],
                    'group_name' => $this->localizedGroupName($market, $language),
                    'logo' => (string) ($market['logo'] ?? ''),
                    'icon' => (string) ($market['icon'] ?? ''),
                ],
                'result' => $this->mapResultDraw($draw),
                'language' => $language,
            ], 'ดึงผลรางวัลงวดสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงผลรางวัลงวดได้ในขณะนี้', 422);
        }
    }

    public function resultsByDate(Request $request): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);
            $drawDate = trim((string) $request->query('draw_date', $request->query('date', '')));
            if ($drawDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $drawDate)) {
                return $this->sendError('กรุณาระบุ draw_date รูปแบบ YYYY-MM-DD', 422);
            }

            $resultedRows = LottoDraw::query()
                ->select(['id', 'market_id', 'draw_date', 'result_at', 'status', 'result_number'])
                ->whereDate('draw_date', $drawDate)
                ->where('status', 'resulted')
                ->orderByDesc('id')
                ->get();

            if ($resultedRows->isEmpty()) {
                return $this->sendResponse([
                    'draw_date' => $drawDate,
                    'groups' => [],
                    'summary' => [
                        'group_count' => 0,
                        'market_count' => 0,
                        'result_count' => 0,
                    ],
                    'language' => $language,
                ], 'ดึงผลรางวัลตามวันที่สำเร็จ');
            }

            $latestByMarket = $resultedRows
                ->groupBy('market_id')
                ->map(static fn (Collection $rows): LottoDraw => $rows->sortByDesc('id')->first());

            $marketIds = $latestByMarket->keys()
                ->map(static fn ($id): int => (int) $id)
                ->filter(static fn (int $id): bool => $id > 0)
                ->values()
                ->all();

            $marketMap = $this->marketMapByIds($marketIds);

            $groups = LotteryGroup::query()
                ->select(['id', 'code', 'sort', 'name', 'name_en', 'name_kh', 'name_laos'])
                ->whereIn('id', collect($marketMap)->pluck('group_id')->all())
                ->orderBy('sort')
                ->orderBy('name')
                ->get();

            $groupRows = $groups->map(function (LotteryGroup $group) use ($latestByMarket, $marketMap, $language): array {
                $markets = collect($marketMap)
                    ->filter(static fn (array $market): bool => (int) $market['group_id'] === (int) $group->id)
                    ->sortBy(static fn (array $market): string => (string) ($market['name'] ?? ''))
                    ->values()
                    ->map(function (array $market) use ($latestByMarket, $language): ?array {
                        $draw = $latestByMarket->get((int) $market['id']);
                        if (! $draw instanceof LottoDraw) {
                            return null;
                        }

                        return [
                            'market_id' => (int) $market['id'],
                            'market_name' => $this->localizedMarketName($market, $language),
                            'market_logo' => (string) ($market['logo'] ?? ''),
                            'market_icon' => (string) ($market['icon'] ?? ''),
                            'result' => $this->mapResultDraw($draw),
                        ];
                    })
                    ->filter()
                    ->values()
                    ->all();

                return [
                    'group_id' => (int) $group->id,
                    'group_code' => (string) ($group->code ?? ''),
                    'group_name' => $this->localizedNameByLanguage([
                        'name' => (string) $group->name,
                        'name_en' => (string) ($group->name_en ?? ''),
                        'name_kh' => (string) ($group->name_kh ?? ''),
                        'name_laos' => (string) ($group->name_laos ?? ''),
                    ], $language, 'name'),
                    'markets' => $markets,
                ];
            })->filter(static fn (array $group): bool => ! empty($group['markets']))
                ->values();

            return $this->sendResponse([
                'draw_date' => $drawDate,
                'groups' => $groupRows->all(),
                'summary' => [
                    'group_count' => $groupRows->count(),
                    'market_count' => $groupRows->sum(static fn (array $group): int => count($group['markets'])),
                    'result_count' => $latestByMarket->count(),
                ],
                'language' => $language,
            ], 'ดึงผลรางวัลตามวันที่สำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงผลรางวัลตามวันที่ได้ในขณะนี้', 422);
        }
    }

    private function localizeDrawsResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $rows = $payload['data'] ?? null;
        if (! is_array($rows)) {
            return $response;
        }

        $marketIds = collect($rows)
            ->pluck('market_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $marketMap = $this->marketMapByIds($marketIds);

        $payload['data'] = collect($rows)->map(function ($row) use ($language, $marketMap) {
            if (! is_array($row)) {
                return $row;
            }

            $marketId = (int) ($row['market_id'] ?? 0);
            if ($marketId > 0 && isset($marketMap[$marketId])) {
                $row['market_name'] = $this->localizedMarketName($marketMap[$marketId], $language);
                $row['market_logo'] = $marketMap[$marketId]['logo'];
                $row['market_icon'] = $marketMap[$marketId]['icon'];
                $row['group_name'] = $this->localizedGroupName($marketMap[$marketId], $language);
            }

            return $row;
        })->values()->all();

        $payload['language'] = $language;
        return $this->normalizeJsonResponseImages(
            response()->json($payload, $response->getStatusCode())
        );
    }

    private function localizeDrawResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return $response;
        }

        $marketId = (int) ($data['market']['id'] ?? 0);
        if ($marketId > 0) {
            $marketMap = $this->marketMapByIds([$marketId]);
            if (isset($marketMap[$marketId])) {
                $market = &$data['market'];
                $market['name'] = $this->localizedMarketName($marketMap[$marketId], $language);
                $market['logo'] = $marketMap[$marketId]['logo'];
                $market['icon'] = $marketMap[$marketId]['icon'];
                $market['group_name'] = $this->localizedGroupName($marketMap[$marketId], $language);
            }
        }

        $payload['data'] = $data;
        $payload['language'] = $language;

        return $this->normalizeJsonResponseImages(
            response()->json($payload, $response->getStatusCode())
        );
    }

    private function localizeTicketsResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $rows = $payload['data'] ?? null;
        if (! is_array($rows)) {
            return $response;
        }

        $drawIds = collect($rows)
            ->pluck('draw_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $marketMapByDraw = $this->marketMapByDrawIds($drawIds);

        $payload['data'] = collect($rows)->map(function ($row) use ($language, $marketMapByDraw) {
            if (! is_array($row)) {
                return $row;
            }

            $drawId = (int) ($row['draw_id'] ?? 0);
            if ($drawId > 0 && isset($marketMapByDraw[$drawId])) {
                $market = $marketMapByDraw[$drawId];
                $row['market_name'] = $this->localizedMarketName($market, $language);
                $row['market_logo'] = $market['logo'];
                $row['market_icon'] = $market['icon'];
                $row['group_name'] = $this->localizedGroupName($market, $language);
            }

            return $this->localizeTicketDataRow($row, $language);
        })->values()->all();

        $payload['language'] = $language;

        return $this->normalizeJsonResponseImages(
            response()->json($payload, $response->getStatusCode())
        );
    }

    private function localizeTicketResponse(JsonResponse $response, string $language): JsonResponse
    {
        $payload = $this->responsePayload($response);
        $data = $payload['data'] ?? null;
        if (! is_array($data)) {
            return $response;
        }

        $drawId = (int) ($data['draw_id'] ?? 0);
        if ($drawId > 0) {
            $marketMapByDraw = $this->marketMapByDrawIds([$drawId]);
            if (isset($marketMapByDraw[$drawId])) {
                $market = $marketMapByDraw[$drawId];
                $data['market_name'] = $this->localizedMarketName($market, $language);
                $data['market_logo'] = $market['logo'];
                $data['market_icon'] = $market['icon'];
                $data['group_name'] = $this->localizedGroupName($market, $language);
            }
        }

        $payload['data'] = $this->localizeTicketDataRow($data, $language, true);
        $payload['language'] = $language;

        return $this->normalizeJsonResponseImages(
            response()->json($payload, $response->getStatusCode())
        );
    }

    private function resolveCustomerMember(Request $request): mixed
    {
        return $request->user('customer') ?: $request->user();
    }

    private function resolveMemberId(Request $request): ?int
    {
        $member = $this->resolveCustomerMember($request);

        if (! $member || ! isset($member->code)) {
            return null;
        }

        return (int) $member->code;
    }

    private function resolveTicketLimit(Request $request): int
    {
        return max(1, min((int) $request->input('limit', 20), 100));
    }

    private function ticketQuery(int $memberId)
    {
        return LottoTicket::query()
            ->with(['draw.market', 'items'])
            ->where('member_id', $memberId);
    }

    private function mapTicketSummary(LottoTicket $ticket): array
    {
        $resultContext = $this->ticketResultContext($ticket);
        $itemSummary = $this->ticketItemSummary($ticket);
        $cancellationInfo = $this->resolveCancellationInfo($ticket);

        return [
            'id' => (int) $ticket->id,
            'draw_id' => (int) $ticket->draw_id,
            'draw_date' => optional($ticket->draw?->draw_date)->toDateString(),
            'market_name' => $ticket->draw?->market?->name,
            'status' => $this->ticketDisplayStatus((string) $ticket->status, $resultContext),
            'draw_status' => (string) ($ticket->draw?->status ?? ''),
            'draw_result_at' => optional($ticket->draw?->result_at)->toDateTimeString(),
            'total_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
            'total_bet_amount' => (float) ($ticket->total_bet_amount ?? 0),
            'total_discount_amount' => (float) ($ticket->total_discount_amount ?? 0),
            'total_net_amount' => (float) ($ticket->total_net_amount ?? $ticket->total_amount ?? 0),
            'total_win_amount' => (float) ($ticket->total_win_amount ?? 0),
            'refund_amount' => (float) ($ticket->refund_amount ?? 0),
            'cancelled_at' => optional($ticket->cancelled_at)->toDateTimeString(),
            'cancelled_by_name' => $cancellationInfo['name'],
            'cancelled_by_type' => $cancellationInfo['type'],
            'cancel_reason' => $this->resolveTicketReason($ticket),
            'result_outcome' => $resultContext['result_outcome'],
            'is_final' => $resultContext['is_final'],
            'is_winner' => $resultContext['is_winner'],
            'item_count' => $itemSummary['item_count'],
            'winning_item_count' => $itemSummary['winning_item_count'],
            'losing_item_count' => $itemSummary['losing_item_count'],
            'pending_item_count' => $itemSummary['pending_item_count'],
            'created_at' => optional($ticket->created_at)->toDateTimeString(),
        ];
    }

    /**
     * @return array{result_outcome:string,is_final:bool,is_winner:bool}
     */
    private function ticketResultContext(LottoTicket $ticket): array
    {
        $draw = $ticket->draw;
        $drawStatus = (string) ($draw?->status ?? '');
        $resultNumber = is_array($draw?->result_number) ? $draw->result_number : [];
        $ticketStatus = (string) $ticket->status;
        $isNoResult = (bool) ($resultNumber['no_result'] ?? false)
            || (string) ($resultNumber['status'] ?? '') === 'no_result';
        $isRefunded = (bool) ($resultNumber['manual_cancelled_all_tickets'] ?? false);
        $isWinner = (float) ($ticket->total_win_amount ?? 0) > 0;

        $resultOutcome = match (true) {
            $ticketStatus === 'cancelled' => 'cancelled',
            $isRefunded => 'refunded',
            $isNoResult => 'no_result',
            $ticketStatus === 'resulted' || $drawStatus === 'resulted' => $isWinner ? 'won' : 'lose',
            $drawStatus === 'open' => 'betting_open',
            default => 'pending_result',
        };

        return [
            'result_outcome' => $resultOutcome,
            'is_final' => in_array($resultOutcome, ['won', 'lose', 'cancelled', 'no_result', 'refunded'], true),
            'is_winner' => $resultOutcome === 'won',
        ];
    }

    /**
     * @param array{result_outcome:string,is_final:bool,is_winner:bool} $resultContext
     */
    private function ticketDisplayStatus(string $ticketStatus, array $resultContext): string
    {
        return match (true) {
            $resultContext['result_outcome'] === 'won' && $resultContext['is_winner'] === true => 'won',
            $resultContext['result_outcome'] === 'lose' && $resultContext['is_winner'] === false => 'lost',
            default => $ticketStatus,
        };
    }

    /**
     * @return array{item_count:int,winning_item_count:int,losing_item_count:int,pending_item_count:int}
     */
    private function ticketItemSummary(LottoTicket $ticket): array
    {
        $items = $ticket->items ?? collect();
        $winningCount = (int) $items->where('result_status', 'win')->count();
        $losingCount = (int) $items->where('result_status', 'lose')->count();
        $itemCount = (int) $items->count();

        return [
            'item_count' => $itemCount,
            'winning_item_count' => $winningCount,
            'losing_item_count' => $losingCount,
            'pending_item_count' => max(0, $itemCount - $winningCount - $losingCount),
        ];
    }

    private function guardCancelWindow(LottoTicket $ticket): void
    {
        $closeAt = $ticket->draw?->close_at;

        if (! $closeAt instanceof Carbon) {
            throw new \RuntimeException('โพยนี้ไม่สามารถยกเลิกได้ เพราะไม่พบเวลาปิดรับ');
        }

        if (! now()->lt($closeAt->copy()->subMinutes(10))) {
            throw new \RuntimeException('ยกเลิกโพยได้ก่อนเวลาปิดรับอย่างน้อย 10 นาทีเท่านั้น');
        }
    }

    private function guardDailyCancelLimit(int $memberId): void
    {
        if (! Schema::hasTable('wallet_transactions')) {
            return;
        }

        $dailyCancelledCount = DB::table('wallet_transactions')
            ->where('member_id', $memberId)
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('created_by_type', 'member')
            ->where('created_by_id', $memberId)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        if ($dailyCancelledCount >= 4) {
            throw new \RuntimeException('สมาชิกยกเลิกโพยได้ไม่เกินวันละ 4 ครั้ง');
        }
    }

    /**
     * @return array{name:string,type:string}
     */
    private function resolveCancellationInfo(LottoTicket $ticket): array
    {
        if (Schema::hasTable('wallet_transactions')) {
            $cancelTxn = DB::table('wallet_transactions')
                ->where('ref_type', 'LOTTO_CANCEL')
                ->where('ref_id', (int) $ticket->id)
                ->orderByDesc('id')
                ->first(['created_by_type', 'created_by_id']);

            if ($cancelTxn && ! empty($cancelTxn->created_by_type) && ! empty($cancelTxn->created_by_id)) {
                $name = $this->resolveActorName((string) $cancelTxn->created_by_type, (int) $cancelTxn->created_by_id);
                if ($name !== '') {
                    return [
                        'name' => $name,
                        'type' => (string) $cancelTxn->created_by_type,
                    ];
                }
            }
        }

        if (! empty($ticket->cancelled_by)) {
            $memberName = $this->resolveActorName('member', (int) $ticket->cancelled_by);
            if ($memberName !== '') {
                return ['name' => $memberName, 'type' => 'member'];
            }

            $adminName = $this->resolveActorName('admin', (int) $ticket->cancelled_by);
            if ($adminName !== '') {
                return ['name' => $adminName, 'type' => 'admin'];
            }
        }

        return ['name' => '', 'type' => ''];
    }

    private function resolveActorName(string $actorType, int $actorId): string
    {
        if ($actorId <= 0) {
            return '';
        }

        return match ($actorType) {
            'admin' => Schema::hasTable('employees')
                ? trim((string) DB::table('employees')->where('code', $actorId)->value('user_name'))
                : '',
            'member' => Schema::hasTable('members')
                ? trim((string) (DB::table('members')->where('code', $actorId)->value('user_name')
                    ?: DB::table('members')->where('code', $actorId)->value('name')))
                : '',
            default => '',
        };
    }

    private function resolveTicketReason(LottoTicket $ticket): string
    {
        if ($this->hasTicketReasonColumn()) {
            return trim((string) ($ticket->reason ?? ''));
        }

        if (! Schema::hasTable('wallet_transactions')) {
            return '';
        }

        $cancelTxnMeta = DB::table('wallet_transactions')
            ->where('ref_type', 'LOTTO_CANCEL')
            ->where('ref_id', (int) $ticket->id)
            ->orderByDesc('id')
            ->value('meta');

        if (! is_string($cancelTxnMeta) || trim($cancelTxnMeta) === '') {
            return '';
        }

        $decoded = json_decode($cancelTxnMeta, true);

        return is_array($decoded) ? trim((string) ($decoded['reason'] ?? '')) : '';
    }

    private function hasTicketReasonColumn(): bool
    {
        return Schema::hasColumn('lotto_tickets', 'reason');
    }

    private function responsePayload(JsonResponse $response): array
    {
        $decoded = json_decode((string) $response->getContent(), true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<int> $marketIds
     * @return array<int, array<string, mixed>>
     */
    private function marketMapByIds(array $marketIds): array
    {
        if (empty($marketIds)) {
            return [];
        }

        /** @var Collection<int, LotteryMarket> $markets */
        $markets = LotteryMarket::query()
            ->select([
                'id',
                'group_id',
                'name',
                'name_en',
                'name_kh',
                'name_laos',
                'logo',
                'icon',
            ])
            ->whereIn('id', $marketIds)
            ->get();

        $groupIds = $markets->pluck('group_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $groupMap = LotteryGroup::query()
            ->select(['id', 'name', 'name_en', 'name_kh', 'name_laos'])
            ->whereIn('id', $groupIds)
            ->get()
            ->mapWithKeys(static function (LotteryGroup $group): array {
                return [(int) $group->id => [
                    'name' => (string) $group->name,
                    'name_en' => (string) ($group->name_en ?? ''),
                    'name_kh' => (string) ($group->name_kh ?? ''),
                    'name_laos' => (string) ($group->name_laos ?? ''),
                ]];
            })
            ->all();

        return $markets->mapWithKeys(function (LotteryMarket $market) use ($groupMap): array {
            $group = $groupMap[(int) $market->group_id] ?? null;

            return [(int) $market->id => [
                'id' => (int) $market->id,
                'group_id' => (int) $market->group_id,
                'name' => (string) $market->name,
                'name_en' => (string) ($market->name_en ?? ''),
                'name_kh' => (string) ($market->name_kh ?? ''),
                'name_laos' => (string) ($market->name_laos ?? ''),
                'logo' => (string) ($market->logo ?? ''),
                'icon' => (string) ($market->icon ?? ''),
                'group_name' => (string) ($group['name'] ?? ''),
                'group_name_en' => (string) ($group['name_en'] ?? ''),
                'group_name_kh' => (string) ($group['name_kh'] ?? ''),
                'group_name_laos' => (string) ($group['name_laos'] ?? ''),
            ]];
        })->all();
    }

    /**
     * @param array<int> $drawIds
     * @return array<int, array<string, mixed>>
     */
    private function marketMapByDrawIds(array $drawIds): array
    {
        if (empty($drawIds)) {
            return [];
        }

        $draws = LottoDraw::query()
            ->select(['id', 'market_id'])
            ->whereIn('id', $drawIds)
            ->get();

        $marketMap = $this->marketMapByIds(
            $draws->pluck('market_id')
                ->map(static fn ($id) => (int) $id)
                ->filter(static fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all()
        );

        return $draws->mapWithKeys(function (LottoDraw $draw) use ($marketMap): array {
            $market = $marketMap[(int) $draw->market_id] ?? null;
            if (! is_array($market)) {
                return [];
            }

            return [(int) $draw->id => $market];
        })->all();
    }

    /**
     * @param array<string, mixed> $market
     */
    private function localizedMarketName(array $market, string $language): string
    {
        return $this->localizedNameByLanguage($market, $language, 'name');
    }

    /**
     * @param array<string, mixed> $market
     */
    private function localizedGroupName(array $market, string $language): string
    {
        return $this->localizedNameByLanguage($market, $language, 'group_name');
    }

    /**
     * @param array<string, mixed> $entity
     */
    private function localizedNameByLanguage(array $entity, string $language, string $baseField): string
    {
        $suffix = $this->languageSuffix($language);
        $preferredField = $suffix === '' ? $baseField : $baseField . '_' . $suffix;

        $preferred = trim((string) ($entity[$preferredField] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) ($entity[$baseField] ?? ''));
    }

    private function languageSuffix(string $language): string
    {
        return match ($language) {
            'en' => 'en',
            'kh' => 'kh',
            'la' => 'laos',
            default => '',
        };
    }

    private function localizedDescriptionByLanguage(?string $rawDescription, string $language): string
    {
        $description = trim((string) $rawDescription);
        if ($description === '') {
            return '';
        }

        $decoded = json_decode($description, true);
        if (! is_array($decoded)) {
            return $description;
        }

        $requestedKeys = match ($language) {
            'en' => ['en', 'english'],
            'kh' => ['kh', 'km', 'kmer', 'cambodia'],
            'la' => ['la', 'laos', 'lo'],
            default => ['th', 'thai'],
        };

        foreach ($requestedKeys as $key) {
            $value = trim((string) ($decoded[$key] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        foreach (['th', 'en', 'kh', 'la', 'laos'] as $fallbackKey) {
            $value = trim((string) ($decoded[$fallbackKey] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function drawStatusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'เปิดรับแทง',
            'closed' => 'รอออกผล',
            'resulted' => 'ออกผลแล้ว',
            default => 'ร่าง',
        };
    }

    private function latestDrawStatus(LottoDraw $draw): string
    {
        $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];

        if ((bool) ($resultNumber['manual_cancelled_all_tickets'] ?? false)) {
            return 'refunded';
        }

        if ((bool) ($resultNumber['no_result'] ?? false) || (string) ($resultNumber['status'] ?? '') === 'no_result') {
            return 'no_result';
        }

        return (string) $draw->status;
    }

    private function latestDrawStatusLabel(string $status): string
    {
        return match ($status) {
            'open' => 'แทงหวย',
            'closed' => 'รอผล',
            'resulted' => 'ออกผล',
            'no_result' => 'ยกเลิก',
            'refunded' => 'ยกเลิก',
            default => 'ร่าง',
        };
    }

    private function resolveResultsLimit(Request $request): int
    {
        $limit = (int) $request->query('limit', 20);

        return max(1, min($limit, 100));
    }

    private function mapResultDraw(LottoDraw $draw): array
    {
        $resultNumber = is_array($draw->result_number) ? $draw->result_number : [];

        return [
            'draw_id' => (int) $draw->id,
            'draw_date' => optional($draw->draw_date)->format('Y-m-d'),
            'result_at' => optional($draw->result_at)->format('Y-m-d H:i:s'),
            'status' => (string) $draw->status,
            'result_number' => $resultNumber,
            'result_top_3' => (string) ($resultNumber['top_3'] ?? ''),
            'result_top_2' => (string) ($resultNumber['top_2'] ?? ''),
            'result_bottom_2' => (string) ($resultNumber['bottom_2'] ?? ($resultNumber['last_2_digits'] ?? '')),
            'first_prize' => (string) ($resultNumber['first_prize'] ?? ''),
            'last_2_digits' => (string) ($resultNumber['last_2_digits'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function localizeTicketDataRow(array $row, string $language, bool $includeItems = false): array
    {
        $ticketStatus = (string) ($row['status'] ?? '');
        $drawStatus = (string) ($row['draw_status'] ?? '');
        $resultOutcome = (string) ($row['result_outcome'] ?? '');
        $totalWinAmount = (float) ($row['total_win_amount'] ?? 0);
        $refundAmount = (float) ($row['refund_amount'] ?? 0);

        $row['status_label'] = $this->ticketStatusLabel($ticketStatus, $language);
        $row['draw_status_label'] = $this->ticketDrawStatusLabel($drawStatus, $language);
        $row['result_outcome_label'] = $this->ticketResultOutcomeLabel($resultOutcome, $language);
        $row['result_message'] = $this->ticketResultMessage($resultOutcome, $totalWinAmount, $refundAmount, $language);

        if ($includeItems) {
            $items = $row['items'] ?? null;
            if (is_array($items)) {
                $row['items'] = collect($items)->map(function ($item) use ($language) {
                    if (! is_array($item)) {
                        return $item;
                    }

                    $resultStatus = (string) ($item['result_status'] ?? 'pending');
                    $item['result_status_label'] = $this->ticketItemResultStatusLabel($resultStatus, $language);
                    $item['result_message'] = $this->ticketItemResultMessage($resultStatus, (float) ($item['win_amount'] ?? 0), $language);

                    return $item;
                })->values()->all();
            }
        }

        return $row;
    }

    private function ticketStatusLabel(string $status, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($status) {
                'active' => 'Active',
                'cancelled' => 'Cancelled',
                'resulted' => 'Settled',
                'won' => 'Won',
                'lost' => 'Lost',
                default => 'Unknown',
            },
            default => match ($status) {
                'active' => 'ใช้งานอยู่',
                'cancelled' => 'ยกเลิกแล้ว',
                'resulted' => 'ตัดสินผลแล้ว',
                'won' => 'ถูกรางวัล',
                'lost' => 'ไม่ถูกรางวัล',
                default => 'ไม่ทราบสถานะ',
            },
        };
    }

    private function ticketDrawStatusLabel(string $status, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($status) {
                'open' => 'Open for betting',
                'closed' => 'Awaiting result',
                'resulted' => 'Resulted',
                'draft' => 'Draft',
                default => 'Pending',
            },
            default => match ($status) {
                'open' => 'เปิดรับแทง',
                'closed' => 'รอผล',
                'resulted' => 'ออกผลแล้ว',
                'draft' => 'ร่าง',
                default => 'รอผล',
            },
        };
    }

    private function ticketResultOutcomeLabel(string $outcome, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($outcome) {
                'betting_open' => 'Betting open',
                'pending_result' => 'Awaiting result',
                'won' => 'Won',
                'lose' => 'Did not win',
                'cancelled' => 'Ticket cancelled',
                'no_result' => 'No result',
                'refunded' => 'Refunded',
                default => 'Awaiting result',
            },
            default => match ($outcome) {
                'betting_open' => 'เปิดรับแทง',
                'pending_result' => 'รอผล',
                'won' => 'ถูกรางวัล',
                'lose' => 'ไม่ถูกรางวัล',
                'cancelled' => 'ยกเลิกโพย',
                'no_result' => 'งดออกผล',
                'refunded' => 'คืนเงินแล้ว',
                default => 'รอผล',
            },
        };
    }

    private function ticketResultMessage(string $outcome, float $totalWinAmount, float $refundAmount, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($outcome) {
                'betting_open' => 'This ticket is still open for betting.',
                'pending_result' => 'This ticket is awaiting result.',
                'won' => 'This ticket won ' . number_format($totalWinAmount, 2) . ' baht.',
                'lose' => 'This ticket did not win.',
                'cancelled' => 'This ticket was cancelled.',
                'no_result' => 'This draw had no result.',
                'refunded' => 'This ticket was refunded' . ($refundAmount > 0 ? ' ' . number_format($refundAmount, 2) . ' baht.' : '.'),
                default => 'This ticket is awaiting result.',
            },
            default => match ($outcome) {
                'betting_open' => 'โพยนี้ยังอยู่ในงวดเปิดรับแทง',
                'pending_result' => 'โพยนี้กำลังรอผล',
                'won' => 'โพยนี้ถูกรางวัล ' . number_format($totalWinAmount, 2) . ' บาท',
                'lose' => 'โพยนี้ไม่ถูกรางวัล',
                'cancelled' => 'โพยนี้ถูกยกเลิกแล้ว',
                'no_result' => 'งวดนี้งดออกผล',
                'refunded' => 'โพยนี้ถูกคืนเงินแล้ว' . ($refundAmount > 0 ? ' ' . number_format($refundAmount, 2) . ' บาท' : ''),
                default => 'โพยนี้กำลังรอผล',
            },
        };
    }

    private function ticketItemResultStatusLabel(string $status, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($status) {
                'win' => 'Won',
                'lose' => 'Did not win',
                default => 'Awaiting result',
            },
            default => match ($status) {
                'win' => 'ถูกรางวัล',
                'lose' => 'ไม่ถูกรางวัล',
                default => 'รอผล',
            },
        };
    }

    private function ticketItemResultMessage(string $status, float $winAmount, string $language): string
    {
        return match ($this->normalizeLanguageForLabels($language)) {
            'en' => match ($status) {
                'win' => 'Won ' . number_format($winAmount, 2) . ' baht.',
                'lose' => 'Did not win.',
                default => 'Awaiting result.',
            },
            default => match ($status) {
                'win' => 'รายการนี้ถูกรางวัล ' . number_format($winAmount, 2) . ' บาท',
                'lose' => 'รายการนี้ไม่ถูกรางวัล',
                default => 'รายการนี้กำลังรอผล',
            },
        };
    }

    private function normalizeLanguageForLabels(string $language): string
    {
        return $language === 'en' ? 'en' : 'th';
    }
}
