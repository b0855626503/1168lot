<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultArchive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class LegacyArchiveResultService
{
    /**
     * Query archive rows for legacy-compatible response.
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
        $query = LottoResultArchive::query()
            ->whereDate('draw_date', '>=', $fromDate)
            ->whereDate('draw_date', '<=', $toDate);

        if ($type) {
            $query->where('market_code', $type);
        }

        $query->whereHas('market', fn ($q) => $q->where('result_mode', '!=', LotteryMarket::RESULT_MODE_YEEKEE));

        $paginator = $query
            ->orderBy('draw_date', 'desc')
            ->orderBy('market_code')
            ->orderBy('draw_key')
            ->paginate($perPage, ['*'], 'page', $page);

        $results = $this->formatResults($paginator);

        return [
            'results' => $results,
            'total' => $paginator->total(),
        ];
    }

    /**
     * Format paginated archive rows into legacy response shape.
     *
     * @return array<int, array>
     */
    protected function formatResults(LengthAwarePaginator $paginator): array
    {
        $marketNames = $this->loadMarketNames($paginator->getCollection());

        return $paginator->getCollection()
            ->groupBy(fn (LottoResultArchive $row) => $row->market_code.'|'.$row->draw_date->format('Y-m-d'))
            ->map(function (Collection $group, string $key) use ($marketNames): array {
                [$marketCode, $drawDate] = explode('|', $key, 2);

                $resultsByKey = $group->pluck('result_set', 'draw_key');

                $lottosNumber = $this->extractResultValue($resultsByKey, 'three_up');
                $lottosUnder = $this->extractResultValue($resultsByKey, 'two_down');

                $sourceDrawId = $group->first()->source_draw_id;

                return [
                    'id' => $sourceDrawId
                        ? (int) $sourceDrawId
                        : (int) sprintf('%u', crc32($marketCode.'|'.$drawDate)),
                    'lottosName' => $marketCode,
                    'lottosTH' => $marketNames[$marketCode] ?? $marketCode,
                    'lottosDate' => $drawDate,
                    'lottosTime' => '', // archive rows don't store draw time
                    'lottosNumber' => $lottosNumber,
                    'lottosUnder' => $lottosUnder,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Extract a single value from a draw_key -> array mapping.
     * Returns first element if array, or the value itself.
     */
    protected function extractResultValue(Collection $resultsByKey, string $drawKey): string
    {
        $resultSet = $resultsByKey->get($drawKey);

        if ($resultSet === null) {
            return '';
        }

        if (is_array($resultSet) && count($resultSet) > 0) {
            return (string) $resultSet[0];
        }

        return (string) $resultSet;
    }

    /**
     * Load market names for the result set, with cache.
     *
     * @return array<string, string> market_code => name
     */
    protected function loadMarketNames(Collection $rows): array
    {
        $codes = $rows->pluck('market_code')->unique()->values()->all();

        return LotteryMarket::whereIn('code', $codes)
            ->pluck('name', 'code')
            ->all();
    }
}
