<?php

namespace Gametech\Lotto\Services;

use Carbon\Carbon;
use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultArchive;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LegacyArchiveResultService
{
    /**
     * Query archive rows for legacy-compatible response with grouped pagination.
     *
     * Two-step query:
     * 1. Paginate distinct (market_code, draw_date) groups
     * 2. Fetch all rows for those groups
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

        $baseQuery = DB::table('lotto_result_archives')
            ->select('market_code', 'draw_date')
            ->whereDate('draw_date', '>=', $fromDate)
            ->whereDate('draw_date', '<=', $toDate)
            ->whereNotIn('market_code', $yeekeeCodes);

        if ($type) {
            $baseQuery->where('market_code', $type);
        }

        // Step 1: paginate distinct grouped keys with subquery-based count
        $groupedQuery = (clone $baseQuery)
            ->groupBy('market_code', 'draw_date');

        $totalGroups = DB::query()->fromSub($groupedQuery, 'groups')->count();
        $offset = ($page - 1) * $perPage;

        $groupKeys = (clone $groupedQuery)
            ->orderBy('draw_date', 'desc')
            ->orderBy('market_code')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        if ($groupKeys->isEmpty()) {
            return ['results' => [], 'total' => 0];
        }

        // Step 2: fetch rows for exact (market_code, draw_date) pairs only
        $rows = LottoResultArchive::query()
            ->whereIn('draw_key', ['three_up', 'two_down'])
            ->where(function ($q) use ($groupKeys) {
                foreach ($groupKeys as $group) {
                    $q->orWhere(function ($sub) use ($group) {
                        $sub->where('market_code', $group->market_code)
                            ->whereDate('draw_date', $group->draw_date);
                    });
                }
            })
            ->get();

        $results = $this->formatGroupedResults($groupKeys, $rows);

        return [
            'results' => $results,
            'total' => $totalGroups,
        ];
    }

    /**
     * Format grouped archive rows into legacy response shape.
     *
     * @param  Collection  $groupKeys  distinct (market_code, draw_date) pairs
     * @param  Collection  $rows  archive rows with draw_key in ['three_up', 'two_down']
     * @return array<int, array>
     */
    protected function formatGroupedResults(Collection $groupKeys, Collection $rows): array
    {
        $marketNames = $this->loadMarketNames($rows);

        // Index rows by market_code|draw_date|draw_key
        $indexed = [];
        foreach ($rows as $row) {
            $drawDate = $row->draw_date instanceof Carbon
                ? $row->draw_date->format('Y-m-d')
                : $row->draw_date;
            $indexed[$row->market_code.'|'.$drawDate.'|'.$row->draw_key] = $row->result_set;
        }

        $results = [];

        foreach ($groupKeys as $group) {
            $marketCode = $group->market_code;
            $drawDate = $group->draw_date;

            $prefix = $marketCode.'|'.$drawDate.'|';
            $lottosNumber = $this->extractResultValue($indexed[$prefix.'three_up'] ?? null);
            $lottosUnder = $this->extractResultValue($indexed[$prefix.'two_down'] ?? null);

            // source_draw_id lookup — must match both market_code AND draw_date
            $sourceDrawId = $rows->first(function ($row) use ($marketCode, $drawDate) {
                $rowDate = $row->draw_date instanceof Carbon
                    ? $row->draw_date->format('Y-m-d')
                    : $row->draw_date;

                return $row->market_code === $marketCode && $rowDate === $drawDate;
            })?->source_draw_id;

            $results[] = [
                'id' => $sourceDrawId
                    ? (int) $sourceDrawId
                    : (int) sprintf('%u', crc32($marketCode.'|'.$drawDate)),
                'lottosName' => $marketCode,
                'lottosTH' => $marketNames[$marketCode] ?? $marketCode,
                'lottosDate' => $drawDate,
                'lottosTime' => '',
                'lottosNumber' => $lottosNumber,
                'lottosUnder' => $lottosUnder,
            ];
        }

        return $results;
    }

    protected function extractResultValue(?array $resultSet): string
    {
        if ($resultSet === null || count($resultSet) === 0) {
            return '';
        }

        return (string) $resultSet[0];
    }

    /**
     * Load market names for the result set.
     *
     * @return array<string, string> market_code => name
     */
    protected function loadMarketNames(Collection $rows): array
    {
        $codes = $rows->pluck('market_code')->unique()->values()->all();

        if (empty($codes)) {
            return [];
        }

        return Cache::remember('lotto:legacy:market_names:'.md5(implode(',', $codes)), 3600, function () use ($codes) {
            return LotteryMarket::whereIn('code', $codes)
                ->pluck('name', 'code')
                ->all();
        });
    }

    /**
     * Get yeekee market codes for exclusion.
     * Uses whereNotIn instead of whereHas to avoid hiding historical archive rows
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
