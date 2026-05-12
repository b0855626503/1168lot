<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
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
     * Format snapshot rows into the legacy API response shape.
     *
     * @param  \Illuminate\Support\Collection<int, LottoResultArchiveLegacyResult>  $rows
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
    protected function formatResults(\Illuminate\Support\Collection $rows): array
    {
        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'id' => $row->source_result_id !== null
                    ? (int) $row->source_result_id
                    : (int) sprintf('%u', crc32($row->lottos_name.'|'.($row->lottos_date_raw ?? ''))),
                'lottosName' => (string) $row->lottos_name,
                'lottosTH' => (string) ($row->lottos_th ?? $row->lottos_name),
                'lottosDate' => (string) ($row->lottos_date_raw ?? ''),
                'lottosTime' => (string) ($row->lottos_time ?? ''),
                'lottosNumber' => (string) ($row->lottos_number ?? ''),
                'lottosUnder' => (string) ($row->lottos_under ?? ''),
            ];
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
