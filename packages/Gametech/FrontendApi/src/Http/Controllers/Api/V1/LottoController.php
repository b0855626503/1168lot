<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Gametech\Lotto\Http\Controllers\Api\BetController as LottoBetController;
use Gametech\Lotto\Http\Controllers\Api\DrawController as LottoDrawController;
use Gametech\Lotto\Http\Controllers\Api\PackageController as LottoPackageController;
use Gametech\Lotto\Http\Controllers\Api\TicketController as LottoTicketController;
use Gametech\Lotto\Models\LottoDraw;
use Gametech\Lotto\Models\LottoDrawBetSetting;
use Gametech\Lotto\Models\LottoMarketBetSetting;
use Gametech\Lotto\Models\LottoNumberBlock;
use Gametech\Lotto\Models\LottoNumberExposure;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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

            $latestDrawIds = LottoDraw::query()
                ->selectRaw('MAX(id) as id')
                ->groupBy('market_id')
                ->pluck('id')
                ->map(static fn ($id) => (int) $id)
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
                        $status = (string) ($draw?->status ?? 'draft');

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
                                'status_label' => $this->drawStatusLabel($status),
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
            $response = app(LottoDrawController::class)->index($request);

            return $this->localizeDrawsResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการงวดได้ในขณะนี้', 422);
        }
    }

    public function draw(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoDrawController::class)->show($id);

            return $this->localizeDrawResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดงวดได้ในขณะนี้', 422);
        }
    }

    public function bet(Request $request)
    {
        try {
            return app(LottoBetController::class)->store($request, app('Gametech\Lotto\Services\BetService'));
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถส่งโพยได้ในขณะนี้', 422);
        }
    }

    public function tickets(Request $request)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoTicketController::class)->index($request);

            return $this->localizeTicketsResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายการโพยได้ในขณะนี้', 422);
        }
    }

    public function packages(int $groupId): JsonResponse
    {
        try {
            return $this->normalizeJsonResponseImages(
                app(LottoPackageController::class)->available($groupId)
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึง package ได้ในขณะนี้', 422);
        }
    }

    public function selectPackage(Request $request, int $groupId): JsonResponse
    {
        try {
            return $this->normalizeJsonResponseImages(
                app(LottoPackageController::class)->select(
                    $groupId,
                    $request,
                    app('Gametech\Lotto\Services\LottoPackageSelectionService')
                )
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถเลือก package ได้ในขณะนี้', 422);
        }
    }

    public function selectedPackage(Request $request, int $groupId): JsonResponse
    {
        try {
            return $this->normalizeJsonResponseImages(
                app(LottoPackageController::class)->selected(
                    $groupId,
                    $request,
                    app('Gametech\Lotto\Services\LottoPackageSelectionService')
                )
            );
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงสถานะ package ที่เลือกได้ในขณะนี้', 422);
        }
    }

    public function ticket(Request $request, int $id)
    {
        try {
            $language = $this->requestLanguage($request);
            $response = app(LottoTicketController::class)->show($request, $id);

            return $this->localizeTicketResponse($response, $language);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงรายละเอียดโพยได้ในขณะนี้', 422);
        }
    }

    public function cancel(Request $request, int $id)
    {
        try {
            $response = app(LottoTicketController::class)->cancel($request, $id);
            if ($response instanceof JsonResponse) {
                $payload = json_decode((string) $response->getContent(), true);
                $message = (string) ($payload['message'] ?? '');
                if (($payload['success'] ?? false) === false && Str::contains($message, 'No query results for model')) {
                    return $this->sendError('ไม่พบโพยที่ระบุ', 404);
                }
            }

            return $this->normalizeJsonResponseImages($response);
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถยกเลิกโพยได้ในขณะนี้', 422);
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

            return $row;
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

        $payload['data'] = $data;
        $payload['language'] = $language;

        return $this->normalizeJsonResponseImages(
            response()->json($payload, $response->getStatusCode())
        );
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
}
