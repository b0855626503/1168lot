<?php

namespace Gametech\FrontendApi\Http\Controllers\Api\V1;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Services\LegacyArchiveResultService;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class LottoResultArchiveLegacyController extends BaseController
{
    protected LegacyArchiveResultService $legacyService;

    protected LotteryRelayTypeRegistry $typeRegistry;

    public function __construct(
        ?LegacyArchiveResultService $legacyService = null,
        ?LotteryRelayTypeRegistry $typeRegistry = null,
    ) {
        $this->legacyService = $legacyService ?? new LegacyArchiveResultService;
        $this->typeRegistry = $typeRegistry ?? new LotteryRelayTypeRegistry;
    }

    /**
     * GET /api/v1/lotto/result-archive-legacy/by-date
     *
     * Returns snapshot results grouped by lottery_group → market, matching the
     * exact response shape of GET /api/v1/lotto/results/by-date.
     *
     * Primary data source: lotto_result_archive_legacy_results (snapshot).
     * Market/group info supplemented from lotto_markets and lottery_groups.
     */
    public function byDate(Request $request): JsonResponse
    {
        $drawDate = trim((string) $request->query('draw_date', $request->query('date', '')));
        if ($drawDate === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $drawDate)) {
            return $this->sendError('กรุณาระบุ draw_date รูปแบบ YYYY-MM-DD', 422);
        }

        $language = $this->requestLanguage($request);

        $payload = Cache::remember(
            "lotto:archive:legacy:by_date:{$drawDate}:{$language}",
            120,
            function () use ($drawDate, $language): array {
                return $this->buildByDatePayload($drawDate, $language);
            }
        );

        $message = empty($payload['groups'])
            ? 'Archive result not found.'
            : 'ดึงผลรางวัลตามวันที่สำเร็จ';

        return $this->sendResponse($this->sanitizeUtf8($payload), $message);
    }

    /**
     * GET /api/v1/lotto/result-archive-legacy/markets/{marketId}/results
     *
     * Returns historical results for a market, reading from lotto_result_archive_legacy_results
     * instead of lotto_draws. Response shape mirrors LottoController::marketResults().
     */
    public function marketResults(Request $request, int $marketId): JsonResponse
    {
        try {
            $language = $this->requestLanguage($request);

            $market = LotteryMarket::query()
                ->select(['id', 'group_id', 'code', 'name', 'name_en', 'name_kh', 'name_laos', 'logo', 'icon'])
                ->find($marketId);

            if (! $market) {
                return $this->sendError('ไม่พบหวยที่ระบุ', 404);
            }

            $canonicalType = $this->typeRegistry->canonicalTypeForMarketCode((string) $market->code)
                ?? (string) $market->code;

            $group = LotteryGroup::query()
                ->select(['id', 'code', 'name', 'name_en', 'name_kh', 'name_laos'])
                ->find($market->group_id);

            $limit = max(1, min((int) $request->query('limit', 20), 100));
            $page = max(1, (int) $request->query('page', 1));

            $latestIds = LottoResultArchiveLegacyResult::query()
                ->selectRaw('MAX(id)')
                ->where('type', $canonicalType)
                ->where('fetch_status', 'success')
                ->whereNotNull('lottos_number')
                ->groupBy('request_date');

            $query = LottoResultArchiveLegacyResult::query()
                ->whereIn('id', $latestIds)
                ->orderByDesc('request_date');

            $total = (clone $query)->count();
            $rows = $query->forPage($page, $limit)->get();
            $history = $rows->map(fn (LottoResultArchiveLegacyResult $row): array => $this->mapSnapshotToDrawResult($row))->values();
            $latest = $history->first();

            return $this->sendResponse($this->sanitizeUtf8([
                'market' => [
                    'id' => (int) $market->id,
                    'name' => $this->localizedMarketName([
                        'name' => (string) $market->name,
                        'name_en' => (string) ($market->name_en ?? ''),
                        'name_kh' => (string) ($market->name_kh ?? ''),
                        'name_laos' => (string) ($market->name_laos ?? ''),
                    ], $language),
                    'group_id' => (int) $market->group_id,
                    'group_name' => $this->localizedGroupName($group, $language),
                    'logo' => (string) ($market->logo ?? ''),
                    'icon' => (string) ($market->icon ?? ''),
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
            ]), 'ดึงผลย้อนหลังสำเร็จ');
        } catch (\Throwable $e) {
            return $this->sendError('ไม่สามารถดึงผลย้อนหลังได้ในขณะนี้', 422);
        }
    }

    /**
     * GET /api/v1/lotto/result-archive-legacy
     *
     * Legacy flat-list archive endpoint for date-range queries.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->query(), [
            'type' => 'nullable|string|max:100',
            'date' => 'nullable|date_format:Y-m-d|prohibits:from_date,to_date',
            'from_date' => 'nullable|date_format:Y-m-d|required_with:to_date',
            'to_date' => 'nullable|date_format:Y-m-d|required_with:from_date',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:500',
            'language' => 'nullable|string|in:th,en,kh,la|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $rawType = $request->query('type');

        if ($rawType !== null) {
            $canonical = $this->typeRegistry->canonicalTypeForMarketCode($rawType);
            $type = $canonical ?? $rawType;
        } else {
            $type = null;
        }

        $singleDate = $request->query('date');
        $rawFrom = $request->query('from_date');
        $rawTo = $request->query('to_date');
        $language = (string) $request->query('language', 'th');

        if (! $singleDate && ! $rawFrom && ! $rawTo) {
            return response()->json([
                'message' => 'Provide either date or from_date+to_date',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($singleDate) {
            return $this->singleDateResponse($singleDate, $type, $language);
        }

        return $this->dateRangeResponse($rawFrom, $rawTo, $type, $request);
    }

    /**
     * Build the by-date payload from snapshot data, matching resultsByDate shape.
     *
     * @return array{draw_date: string, groups: array, summary: array, language: string}
     */
    protected function buildByDatePayload(string $drawDate, string $language): array
    {
        $yeekeeCodes = Cache::remember('lotto:legacy:yeekee_codes', 3600, function () {
            return LotteryMarket::where('result_mode', LotteryMarket::RESULT_MODE_YEEKEE)
                ->pluck('code')
                ->all();
        });

        $allRows = LottoResultArchiveLegacyResult::query()
            ->where('fetch_status', 'success')
            ->where('request_date', $drawDate)
            ->whereNotNull('lottos_number')
            ->whereNotIn('type', $yeekeeCodes)
            ->orderBy('id', 'desc')
            ->get();

        if ($allRows->isEmpty()) {
            return [
                'draw_date' => $drawDate,
                'groups' => [],
                'summary' => [
                    'group_count' => 0,
                    'market_count' => 0,
                    'result_count' => 0,
                ],
                'language' => $language,
            ];
        }

        $latestByType = $allRows
            ->groupBy('type')
            ->map(fn (Collection $rows): LottoResultArchiveLegacyResult => $rows->first());

        $typeToMarket = $this->buildTypeToMarketMap($latestByType->keys()->all());

        $marketsById = [];
        foreach ($typeToMarket as $market) {
            $marketsById[$market->id] = $market;
        }

        $groupMap = $this->buildGroupMap($marketsById);

        $grouped = [];
        foreach ($latestByType as $snapshotType => $row) {
            $market = $typeToMarket[$snapshotType] ?? null;

            if ($market === null) {
                continue;
            }

            $groupId = (int) $market->group_id;
            $group = $groupMap[$groupId] ?? null;

            if ($group === null) {
                continue;
            }

            $groupKey = (string) $groupId;

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'group_id' => $groupId,
                    'group_code' => (string) ($group->code ?? ''),
                    'group_name' => $this->localizedMarketName([
                        'name' => (string) $group->name,
                        'name_en' => (string) ($group->name_en ?? ''),
                        'name_kh' => (string) ($group->name_kh ?? ''),
                        'name_laos' => (string) ($group->name_laos ?? ''),
                    ], $language),
                    'markets' => [],
                ];
            }

            $marketKey = (string) $market->id;

            if (! isset($grouped[$groupKey]['markets'][$marketKey])) {
                $grouped[$groupKey]['markets'][$marketKey] = [
                    'market_id' => (int) $market->id,
                    'market_name' => $this->localizedMarketName([
                        'name' => (string) $market->name,
                        'name_en' => (string) ($market->name_en ?? ''),
                        'name_kh' => (string) ($market->name_kh ?? ''),
                        'name_laos' => (string) ($market->name_laos ?? ''),
                    ], $language),
                    'market_logo' => (string) ($market->logo ?? ''),
                    'market_icon' => (string) ($market->icon ?? ''),
                    'result' => $this->mapSnapshotToDrawResult($row),
                ];
            }
        }

        $groups = [];
        foreach ($grouped as $g) {
            $g['markets'] = array_values($g['markets']);
            $groups[] = $g;
        }

        usort($groups, static fn (array $a, array $b): int => $a['group_id'] <=> $b['group_id']);

        $marketCount = array_sum(array_map(static fn (array $g): int => count($g['markets']), $groups));
        $resultCount = $latestByType->count();

        return [
            'draw_date' => $drawDate,
            'groups' => $groups,
            'summary' => [
                'group_count' => count($groups),
                'market_count' => $marketCount,
                'result_count' => $resultCount,
            ],
            'language' => $language,
        ];
    }

    /**
     * Map a snapshot row to the draw result shape matching LottoController::mapResultDraw().
     *
     * @return array{draw_id: int, draw_date: string, result_at: string, status: string, result_number: array, result_top_3: string, result_top_2: string, result_bottom_2: string, first_prize: string, last_2_digits: string}
     */
    protected function mapSnapshotToDrawResult(LottoResultArchiveLegacyResult $row): array
    {
        $number = (string) $row->lottos_number;
        $under = (string) ($row->lottos_under ?? '');
        $len = strlen($number);

        $top3 = $len >= 3 ? substr($number, -3) : '';
        $top2 = $len >= 2 ? substr($number, -2) : '';
        $bottom2 = $under !== '' ? $under : ($len >= 2 ? substr($number, -2) : '');
        $last2 = $bottom2;

        $resultNumber = [];
        if ($number !== '') {
            $resultNumber['first_prize'] = $number;
        }
        if ($last2 !== '') {
            $resultNumber['last_2_digits'] = $last2;
        }
        if ($top3 !== '') {
            $resultNumber['top_3'] = $top3;
        }
        if ($top2 !== '') {
            $resultNumber['top_2'] = $top2;
        }
        if ($bottom2 !== '') {
            $resultNumber['bottom_2'] = $bottom2;
        }

        return [
            'draw_id' => $row->source_result_id !== null
                ? (int) $row->source_result_id
                : (int) $row->id,
            'draw_date' => $row->request_date instanceof \DateTimeInterface
                ? $row->request_date->format('Y-m-d')
                : (string) ($row->request_date ?? ''),
            'result_at' => $this->resolveResultAt($row),
            'status' => 'resulted',
            'result_number' => $resultNumber,
            'result_top_3' => $top3,
            'result_top_2' => $top2,
            'result_bottom_2' => $bottom2,
            'first_prize' => $number,
            'last_2_digits' => $last2,
        ];
    }

    protected function resolveResultAt(LottoResultArchiveLegacyResult $row): string
    {
        $timezone = (string) config('app.timezone', 'Asia/Bangkok');

        // Use request_date + lottos_time as primary source
        $dateStr = $row->request_date instanceof \DateTimeInterface
            ? $row->request_date->format('Y-m-d')
            : (string) ($row->request_date ?? '');
        $timeStr = trim((string) ($row->lottos_time ?? ''));

        if ($dateStr !== '' && $timeStr !== '' && preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
            return $dateStr.' '.$timeStr.':00';
        }

        // Fallback: lottos_date (from source API)
        if ($row->lottos_date instanceof \DateTimeInterface) {
            $localDate = $row->lottos_date->copy()->timezone($timezone);

            if ($timeStr !== '' && preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
                return $localDate->format('Y-m-d').' '.$timeStr.':00';
            }

            return $localDate->format('Y-m-d H:i:s');
        }

        // Last resort
        if ($row->fetched_at instanceof \DateTimeInterface) {
            return $row->fetched_at->copy()->timezone($timezone)->format('Y-m-d H:i:s');
        }

        return (string) ($row->fetched_at ?? '');
    }

    /**
     * Build a map from snapshot type (e.g. "xsthm") to LotteryMarket model.
     *
     * @param  string[]  $types
     * @return array<string, LotteryMarket>
     */
    protected function buildTypeToMarketMap(array $types): array
    {
        $registry = $this->typeRegistry;

        $markets = LotteryMarket::query()
            ->where('result_mode', '!=', LotteryMarket::RESULT_MODE_YEEKEE)
            ->select(['id', 'group_id', 'code', 'name', 'name_en', 'name_kh', 'name_laos', 'logo', 'icon'])
            ->get();

        $map = [];
        foreach ($markets as $market) {
            $canonical = $registry->canonicalTypeForMarketCode((string) $market->code) ?? (string) $market->code;
            if (! isset($map[$canonical])) {
                $map[$canonical] = $market;
            }
        }

        foreach ($types as $t) {
            if (isset($map[$t])) {
                continue;
            }

            $direct = $markets->firstWhere('code', $t);
            if ($direct) {
                $map[$t] = $direct;
            }
        }

        return $map;
    }

    /**
     * Build a map from group_id to LotteryGroup.
     *
     * @param  array<int, LotteryMarket>  $marketsById
     * @return array<int, LotteryGroup>
     */
    protected function buildGroupMap(array $marketsById): array
    {
        $groupIds = array_unique(array_map(
            static fn (LotteryMarket $m): int => (int) $m->group_id,
            array_values($marketsById)
        ));

        $groupIds = array_filter($groupIds, static fn (int $id): bool => $id > 0);

        if (empty($groupIds)) {
            return [];
        }

        return LotteryGroup::query()
            ->select(['id', 'code', 'sort', 'name', 'name_en', 'name_kh', 'name_laos'])
            ->whereIn('id', $groupIds)
            ->orderBy('sort')
            ->orderBy('name')
            ->get()
            ->keyBy('id')
            ->all();
    }

    protected function localizedMarketName(array $entity, string $language): string
    {
        $suffix = match ($language) {
            'en' => 'en',
            'kh' => 'kh',
            'la' => 'laos',
            default => '',
        };

        $preferredField = $suffix === '' ? 'name' : 'name_'.$suffix;
        $preferred = trim((string) ($entity[$preferredField] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) ($entity['name'] ?? ''));
    }

    protected function localizedGroupName(?LotteryGroup $group, string $language): string
    {
        if (! $group) {
            return '';
        }

        return $this->localizedMarketName([
            'name' => (string) $group->name,
            'name_en' => (string) ($group->name_en ?? ''),
            'name_kh' => (string) ($group->name_kh ?? ''),
            'name_laos' => (string) ($group->name_laos ?? ''),
        ], $language);
    }

    /**
     * Grouped response for a single date (existing behavior kept for backward compat).
     */
    protected function singleDateResponse(string $date, ?string $type, string $language): JsonResponse
    {
        if (! $this->validDrawDate($date)) {
            return response()->json(['message' => 'date must be valid YYYY-MM-DD'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $payload = Cache::remember(
            "lotto:archive:legacy:grouped:{$date}:".($type ?: 'all').":{$language}",
            120,
            function () use ($date, $type, $language): array {
                return $this->legacyService->queryGrouped($date, $type, $language);
            }
        );

        $message = empty($payload['groups'])
            ? 'Archive result not found.'
            : 'ดึงผลรางวัลตามวันที่สำเร็จ';

        return response()->json($this->sanitizeUtf8($payload + ['message' => $message]));
    }

    /**
     * Flat-list response for date ranges (legacy format).
     */
    protected function dateRangeResponse(?string $rawFrom, ?string $rawTo, ?string $type, Request $request): JsonResponse
    {
        $fromDate = Carbon::createFromFormat('!Y-m-d', (string) $rawFrom);
        $toDate = Carbon::createFromFormat('!Y-m-d', (string) $rawTo);

        if ($fromDate->gt($toDate)) {
            return response()->json([
                'message' => 'from_date must be before or equal to to_date',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($fromDate->diffInDays($toDate) > 366) {
            return response()->json([
                'message' => 'Date range must not exceed 366 days',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $perPage = (int) $request->query('per_page', 100);
        $page = (int) $request->query('page', 1);
        $dateLabel = $rawFrom.'..'.$rawTo;

        $nameTH = 'ทั้งหมด';
        $marketNameTH = null;

        if ($type) {
            $market = LotteryMarket::where('code', $type)
                ->where('result_mode', '!=', LotteryMarket::RESULT_MODE_YEEKEE)
                ->first();

            $marketNameTH = $market ? ($market->name ?: $type) : null;
            $nameTH = $marketNameTH ?? $type;
        }

        $fromDateStr = $fromDate->format('Y-m-d');
        $toDateStr = $toDate->format('Y-m-d');

        $queryFingerprint = md5(($type ?: 'all').'|'.$fromDateStr.'|'.$toDateStr.'|'.$page.'|'.$perPage);

        if ($type) {
            $version = Cache::get("lotto:archive:{$type}:version", 1);
            $cacheKey = "lotto:archive:legacy:{$type}:v{$version}:list:{$queryFingerprint}";
            $ttl = 120;
        } else {
            $cacheKey = "lotto:archive:legacy:all:list:{$queryFingerprint}";
            $ttl = 60;
        }

        $payload = Cache::remember($cacheKey, $ttl, function () use (
            $type, $fromDateStr, $toDateStr, $perPage, $page, $dateLabel, $nameTH, $marketNameTH
        ): array {
            $resolvedNameTH = $nameTH;
            if ($type !== null && $marketNameTH === null) {
                $snapshotNameTH = LottoResultArchiveLegacyResult::where('type', $type)
                    ->where('fetch_status', 'success')
                    ->whereNotNull('name_th')
                    ->value('name_th');
                $resolvedNameTH = $snapshotNameTH ?: $type;
            }

            $result = $this->legacyService->query(
                type: $type,
                fromDate: $fromDateStr,
                toDate: $toDateStr,
                perPage: $perPage,
                page: $page,
            );

            $errors = [];
            if (empty($result['results'])) {
                $errors[] = [
                    'code' => 'DRAW_DATE_NOT_FOUND',
                    'message' => 'Archive result not found.',
                ];
            }

            return [
                'type' => $type ?: 'all',
                'nameTH' => $resolvedNameTH,
                'date' => $dateLabel,
                'page' => $page,
                'count' => count($result['results']),
                'results' => $result['results'],
                'errors' => $errors,
            ];
        });

        return response()->json($this->sanitizeUtf8($payload));
    }

    protected function validDrawDate(string $raw): bool
    {
        $validator = Validator::make(['drawDate' => $raw], [
            'drawDate' => ['required', 'date_format:Y-m-d'],
        ]);

        if ($validator->fails()) {
            return false;
        }

        $parsed = Carbon::createFromFormat('!Y-m-d', $raw);

        return $parsed && $parsed->format('Y-m-d') === $raw;
    }

    /**
     * Recursively sanitize all string values to valid UTF-8, stripping invalid byte sequences.
     *
     * @param  mixed  $data
     * @return mixed
     */
    private function sanitizeUtf8($data)
    {
        if (is_string($data)) {
            return mb_convert_encoding($data, 'UTF-8', 'UTF-8');
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitizeUtf8($value);
            }
        }

        return $data;
    }
}
