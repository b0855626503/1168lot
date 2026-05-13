<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryGroup;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LegacyArchiveResultService
{
    /**
     * Query snapshot rows from lotto_result_archive_legacy_results with pagination.
     *
     * @return array{results: array, total: int}
     */
    public function query(
        ?string $type,
        string $fromDate,
        string $toDate,
        int $perPage = 100,
        int $page = 1,
    ): array {
        $yeekeeCodes = $this->getYeekeeCodes();

        $query = LottoResultArchiveLegacyResult::query()
            ->where('fetch_status', 'success')
            ->where('request_date', '>=', $fromDate)
            ->where('request_date', '<=', $toDate)
            ->whereNotIn('type', $yeekeeCodes);

        if ($type) {
            $query->where('type', $type);
        }

        $total = $query->count();

        if ($total === 0) {
            return ['results' => [], 'total' => 0];
        }

        $rows = (clone $query)
            ->orderBy('request_date', 'desc')
            ->orderBy('type')
            ->offset(($page - 1) * $perPage)
            ->limit($perPage)
            ->get();

        $results = $this->formatResults($rows);

        return [
            'results' => $results,
            'total' => $total,
        ];
    }

    /**
     * Query snapshot rows grouped by lottery_group → market, matching the
     * response shape of GET /api/v1/lotto/results/by-date.
     *
     * Primary data comes from lotto_result_archive_legacy_results (snapshot).
     * Market name, logo, icon, and group info are supplemented from
     * lotto_markets and lottery_groups via LotteryRelayTypeRegistry alias mapping.
     *
     * @return array{draw_date: string, groups: array, summary: array, language: string}
     */
    public function queryGrouped(string $date, ?string $type = null, string $language = 'th'): array
    {
        $yeekeeCodes = $this->getYeekeeCodes();

        $query = LottoResultArchiveLegacyResult::query()
            ->where('fetch_status', 'success')
            ->where('request_date', $date)
            ->whereNotNull('lottos_number')
            ->whereNotIn('type', $yeekeeCodes);

        if ($type) {
            $query->where('type', $type);
        }

        $allRows = $query->orderBy('id', 'desc')->get();

        if ($allRows->isEmpty()) {
            return [
                'draw_date' => $date,
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

        $types = $latestByType->keys()->all();

        $typeToMarket = $this->buildTypeToMarketMap($types);

        $marketsById = [];
        foreach ($typeToMarket as $t => $market) {
            $marketsById[$market->id] = $market;
        }

        $groupMap = $this->buildGroupMap($marketsById);

        $grouped = [];
        foreach ($latestByType as $snapshotType => $row) {
            $market = $typeToMarket[$snapshotType] ?? null;

            $groupId = $market ? (int) $market->group_id : 0;
            $group = $groupMap[$groupId] ?? null;

            $groupKey = $groupId > 0 ? (string) $groupId : 'unknown';

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'group_id' => $groupId,
                    'group_code' => $group ? (string) $group->code : '',
                    'group_name' => $this->localizedName([
                        'name' => $group ? (string) $group->name : 'อื่นๆ',
                        'name_en' => $group ? (string) ($group->name_en ?? '') : 'Others',
                        'name_kh' => $group ? (string) ($group->name_kh ?? '') : '',
                        'name_laos' => $group ? (string) ($group->name_laos ?? '') : '',
                    ], $language, 'name'),
                    'markets' => [],
                ];
            }

            $marketKey = $market ? (string) $market->id : $snapshotType;

            if (! isset($grouped[$groupKey]['markets'][$marketKey])) {
                $grouped[$groupKey]['markets'][$marketKey] = [
                    'market_code' => $snapshotType,
                    'market_name' => $market
                        ? $this->localizedName([
                            'name' => (string) $market->name,
                            'name_en' => (string) ($market->name_en ?? ''),
                            'name_kh' => (string) ($market->name_kh ?? ''),
                            'name_laos' => (string) ($market->name_laos ?? ''),
                        ], $language, 'name')
                        : $snapshotType,
                    'market_logo' => $market ? (string) ($market->logo ?? '') : '',
                    'market_icon' => $market ? (string) ($market->icon ?? '') : '',
                    'results' => [],
                ];
            }

            $grouped[$groupKey]['markets'][$marketKey]['results'][] = $this->formatResultItem($row);
        }

        $groups = [];
        foreach ($grouped as &$g) {
            $g['markets'] = array_values($g['markets']);
            $groups[] = $g;
        }
        unset($g);

        usort($groups, static function (array $a, array $b): int {
            if ($a['group_id'] === 0 && $b['group_id'] !== 0) {
                return 1;
            }
            if ($b['group_id'] === 0 && $a['group_id'] !== 0) {
                return -1;
            }

            return $a['group_id'] <=> $b['group_id'];
        });

        $marketCount = array_sum(array_map(static fn (array $g): int => count($g['markets']), $groups));
        $resultCount = $latestByType->count();

        return [
            'draw_date' => $date,
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
     * Build a map from snapshot type (e.g. "xsthm") to LotteryMarket model.
     *
     * Uses LotteryRelayTypeRegistry to resolve market-code aliases in reverse:
     * for every non-yeekee market, canonicalTypeForMarketCode(code) → type,
     * then invert so type → market.
     *
     * @param  string[]  $types
     * @return array<string, LotteryMarket>
     */
    protected function buildTypeToMarketMap(array $types): array
    {
        $registry = new LotteryRelayTypeRegistry;

        $markets = LotteryMarket::query()
            ->where('result_mode', '!=', LotteryMarket::RESULT_MODE_YEEKEE)
            ->select(['id', 'group_id', 'code', 'name', 'name_en', 'name_kh', 'name_laos', 'logo', 'icon'])
            ->get();

        $map = [];
        foreach ($markets as $market) {
            $canonical = $registry->canonicalTypeForMarketCode((string) $market->code) ?? (string) $market->code;
            $map[$canonical] = $market;
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
     * Build a map from group_id to LotteryGroup for all groups referenced by the given markets.
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

    /**
     * Format a single snapshot row into the result item shape.
     *
     * @return array{id: int, lottosName: string, lottosTH: string, lottosDate: string, lottosTime: string, lottosNumber: string, lottosUnder: string}
     */
    protected function formatResultItem(LottoResultArchiveLegacyResult $row): array
    {
        return [
            'id' => $row->source_result_id !== null
                ? (int) $row->source_result_id
                : (int) $row->id,
            'lottosName' => (string) $row->lottos_name,
            'lottosTH' => (string) ($row->lottos_th ?? $row->lottos_name),
            'lottosDate' => (string) ($row->lottos_date_raw ?? ''),
            'lottosTime' => (string) ($row->lottos_time ?? ''),
            'lottosNumber' => (string) ($row->lottos_number ?? ''),
            'lottosUnder' => (string) ($row->lottos_under ?? ''),
        ];
    }

    /**
     * Resolve a localized name from an entity array.
     *
     * @param  array<string, string>  $entity
     */
    protected function localizedName(array $entity, string $language, string $baseField): string
    {
        $suffix = match ($language) {
            'en' => 'en',
            'kh' => 'kh',
            'la' => 'laos',
            default => '',
        };

        $preferredField = $suffix === '' ? $baseField : $baseField.'_'.$suffix;
        $preferred = trim((string) ($entity[$preferredField] ?? ''));
        if ($preferred !== '') {
            return $preferred;
        }

        return trim((string) ($entity[$baseField] ?? ''));
    }

    /**
     * Format snapshot rows into the legacy API response shape.
     *
     * @param  Collection<int, LottoResultArchiveLegacyResult>  $rows
     * @return array<int, array{
     *     id: int,
     *     lottosName: string,
     *     lottosTH: string,
     *     lottosDate: string,
     *     lottosTime: string,
     *     lottosNumber: string,
     *     lottosUnder: string,
     * }>
     */
    protected function formatResults(Collection $rows): array
    {
        $results = [];

        foreach ($rows as $row) {
            $results[] = $this->formatResultItem($row);
        }

        return $results;
    }

    /**
     * Get yeekee market codes for exclusion from the snapshot query.
     * Uses whereNotIn instead of whereHas to avoid hiding historical rows
     * whose market lookup may no longer exist.
     */
    protected function getYeekeeCodes(): array
    {
        return Cache::remember('lotto:legacy:yeekee_codes', 3600, function () {
            return LotteryMarket::where('result_mode', LotteryMarket::RESULT_MODE_YEEKEE)
                ->pluck('code')
                ->all();
        });
    }
}
