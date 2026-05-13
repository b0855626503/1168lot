<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Gametech\Lotto\Repositories\LegacyArchiveResultRepository;
use Gametech\Lotto\Services\LegacyArchiveSourceClient;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class FetchLegacyArchiveResultsCommand extends Command
{
    protected $signature = 'lotto:legacy-results:fetch
        {--from= : Start date YYYY-MM-DD}
        {--to= : End date YYYY-MM-DD}
        {--type= : Single source type code (the external ?type= value, e.g. xsthm)}
        {--today : Shorthand for --from=today --to=today}
        {--exclude-group=* : Lottery group code(s) to exclude (e.g. lotto-thai)}
        {--source= : Source type: legacy (get_lottery.php, by-date), exphuay (batch, all dates in one request)}
        {--force : Overwrite existing success rows}
        {--limit= : Maximum rows to process (default unlimited)}
        {--sleep=0 : Milliseconds to sleep between fetch calls}';

    protected $description = 'Fetch legacy archive results from configured lotto_result_sources and upsert into lotto_result_archive_legacy_results';

    public function handle(LegacyArchiveResultRepository $repository, LegacyArchiveSourceClient $client): int
    {
        $sourceType = $this->option('source') ?? 'legacy';

        if ($sourceType === 'exphuay') {
            return $this->handleExphuayBatch($repository, $client);
        }

        $today = $this->option('today');
        $from = $this->option('from');
        $to = $this->option('to');

        if ($today) {
            $from = Carbon::today()->format('Y-m-d');
            $to = Carbon::today()->format('Y-m-d');
        } elseif (! $from || ! $to) {
            $this->error('You must provide --from and --to, or use --today.');

            return self::FAILURE;
        }

        try {
            $fromDate = Carbon::createFromFormat('Y-m-d', (string) $from);
            $toDate = Carbon::createFromFormat('Y-m-d', (string) $to);
        } catch (\Throwable $e) {
            $this->error('Invalid date format. Expected YYYY-MM-DD.');

            return self::FAILURE;
        }

        if ($fromDate->format('Y-m-d') !== (string) $from || $toDate->format('Y-m-d') !== (string) $to) {
            $this->error('Invalid date. Expected a real calendar date in YYYY-MM-DD format.');

            return self::FAILURE;
        }

        $current = $fromDate->copy()->startOfDay();
        $end = $toDate->copy()->startOfDay();

        if ($current->gt($end)) {
            $this->error('--from date must not be after --to date.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $limitRaw = $this->option('limit');
        $limit = $limitRaw !== null ? (int) $limitRaw : null;
        $sleepMs = max(0, (int) ($this->option('sleep') ?? 0));
        $typeFilter = $this->option('type');
        $excludeGroups = (array) $this->option('exclude-group');

        $sources = $this->resolveSources($typeFilter, $excludeGroups);

        if ($sources->isEmpty()) {
            if ($typeFilter) {
                $this->warn('No matching lotto_result_sources found. Falling back to --type value directly.');

                $sources = collect([[
                    'type_code' => $typeFilter,
                    'endpoint_url' => (string) config('lotto.legacy_archive.base_url'),
                    'query_params' => ['type' => $typeFilter, 'date' => '{{lookup_date}}'],
                ]]);
            } else {
                $sources = $this->resolveSourcesFromMarkets($excludeGroups);

                if ($sources->isEmpty()) {
                    $this->warn('No active lotto_result_sources or lotto_markets found, nothing to fetch.');

                    return self::SUCCESS;
                }

                $this->line(sprintf('Auto-discovered %d types from lotto_markets.', $sources->count()));
            }
        }

        $totalUpserted = 0;
        $limitReached = false;

        while ($current->lte($end) && ! $limitReached) {
            $dateStr = $current->format('Y-m-d');

            foreach ($sources as $source) {
                if ($limit !== null && $totalUpserted >= $limit) {
                    $limitReached = true;
                    break;
                }

                $typeCode = $source['type_code'];
                $endpointUrl = $source['endpoint_url'];
                $queryParams = $this->buildQueryParams($source['query_params'], $dateStr);

                $this->line(sprintf('Processing type=%s date=%s url=%s', $typeCode, $dateStr, $endpointUrl));

                $rows = $this->fetchForDate($typeCode, $dateStr, $client, $endpointUrl, $queryParams);

                foreach ($rows as $row) {
                    if ($limit !== null && $totalUpserted >= $limit) {
                        $limitReached = true;
                        break;
                    }

                    $result = $repository->upsertWithResult($row, $force);
                    $totalUpserted++;

                    if ($result->wasWritten() && $result->model->fetch_status === 'success') {
                        Cache::increment('lotto:archive:'.$typeCode.':version');
                    }
                }

                if ($sleepMs > 0) {
                    usleep($sleepMs * 1000);
                }
            }

            $current->addDay();
        }

        $this->line(sprintf('Done. Total upserted: %d', $totalUpserted));

        return self::SUCCESS;
    }

    /**
     * Resolve the list of source configurations from lotto_result_sources.
     *
     * Each source yields: { type_code: string, endpoint_url: string, query_params: array<string, string> }
     * - type_code: unique identifier for this source (from request_query_template_json.type, or market code fallback)
     * - endpoint_url: base URL to call
     * - query_params: query-string parameters with {{lookup_date}} placeholder for date substitution
     *
     * @return Collection<int, array{type_code: string, endpoint_url: string, query_params: array<string, string>}>
     */
    protected function resolveSources(?string $typeFilter, array $excludeGroups = []): Collection
    {
        try {
            $query = LottoResultSource::query()
                ->where('is_active', true)
                ->whereHas('market', function ($q) use ($excludeGroups): void {
                    $q->where('result_mode', '!=', 'yeekee');

                    if (! empty($excludeGroups)) {
                        $q->whereHas('group', function ($gq) use ($excludeGroups): void {
                            $gq->whereIn('code', $excludeGroups);
                        }, '=', 0);
                    }
                })
                ->with('market');

            if ($typeFilter) {
                $query->where('request_query_template_json->type', $typeFilter);
            }

            return $query->get()
                ->map(function (LottoResultSource $source): ?array {
                    $queryTemplate = $source->request_query_template_json;
                    $typeCode = $queryTemplate['type'] ?? null;

                    if (empty($typeCode)) {
                        return null;
                    }

                    $endpointUrl = $source->fetch_config_json['endpoint_url']
                        ?? $source->endpoint_url
                        ?? config('lotto.legacy_archive.base_url');

                    if (empty($endpointUrl)) {
                        return null;
                    }

                    return [
                        'type_code' => $typeCode,
                        'endpoint_url' => (string) $endpointUrl,
                        'query_params' => $queryTemplate,
                    ];
                })
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            $this->warn('Could not query lotto_result_sources: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Fallback: auto-discover source types from lotto_markets when lotto_result_sources is empty.
     *
     * Uses LotteryRelayTypeRegistry to map market codes (e.g. hanoi-special) to canonical
     * external API types (e.g. xsthm). Each unique canonical type becomes one source entry
     * pointing at the default legacy archive endpoint.
     *
     * @return Collection<int, array{type_code: string, endpoint_url: string, query_params: array<string, string>}>
     */
    protected function resolveSourcesFromMarkets(array $excludeGroups = []): Collection
    {
        $baseUrl = (string) config('lotto.legacy_archive.base_url');

        if (empty($baseUrl)) {
            return collect();
        }

        try {
            $registry = new LotteryRelayTypeRegistry;

            $query = LotteryMarket::query()
                ->where('result_mode', '!=', 'yeekee');

            if (! empty($excludeGroups)) {
                $query->whereHas('group', function ($q) use ($excludeGroups): void {
                    $q->whereIn('code', $excludeGroups);
                }, '=', 0);
            }

            return $query->pluck('code')
                ->map(fn (string $marketCode): ?string => $registry->canonicalTypeForMarketCode($marketCode))
                ->filter()
                ->unique()
                ->values()
                ->map(fn (string $canonicalType): array => [
                    'type_code' => $canonicalType,
                    'endpoint_url' => $baseUrl,
                    'query_params' => ['type' => $canonicalType, 'date' => '{{lookup_date}}'],
                ]);
        } catch (\Throwable $e) {
            $this->warn('Could not query lotto_markets: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Build final query params from a source template, substituting {{lookup_date}}.
     *
     * @param  array<string, string>  $template
     * @return array<string, string>
     */
    protected function buildQueryParams(array $template, string $dateStr): array
    {
        $params = [];

        foreach ($template as $key => $value) {
            $params[$key] = str_replace('{{lookup_date}}', $dateStr, (string) $value);
        }

        return $params;
    }

    /**
     * Fetch raw result rows for a given type and date via the source client.
     *
     * Returns an array of mapped field arrays suitable for repository->upsert().
     *
     * @param  array<string, string>  $queryParams
     * @return array<int, array<string, mixed>>
     */
    protected function fetchForDate(string $type, string $date, LegacyArchiveSourceClient $client, ?string $endpointUrl = null, array $queryParams = []): array
    {
        return $client->fetch($type, $date, $endpointUrl, $queryParams);
    }

    /**
     * Batch fetch from exphuay.com — one request per type returns ~30 days.
     */
    protected function handleExphuayBatch(LegacyArchiveResultRepository $repository, LegacyArchiveSourceClient $client): int
    {
        $typeFilter = $this->option('type');
        $excludeGroups = (array) $this->option('exclude-group');
        $force = (bool) $this->option('force');
        $sleepMs = max(0, (int) ($this->option('sleep') ?? 0));

        $sources = $this->resolveSources($typeFilter, $excludeGroups);

        if ($sources->isEmpty()) {
            if ($typeFilter) {
                $this->warn('No sources found for exphuay batch fetch.');

                return self::SUCCESS;
            }

            $sources = $this->resolveSourcesFromMarkets($excludeGroups);

            if ($sources->isEmpty()) {
                $this->warn('No lotto_markets found for exphuay batch fetch.');

                return self::SUCCESS;
            }

            $this->line(sprintf('Auto-discovered %d types from lotto_markets.', $sources->count()));
        }

        $totalUpserted = 0;
        $typeCodes = $sources->pluck('type_code');

        foreach ($typeCodes as $typeCode) {
            $this->line(sprintf('Fetching exphuay batch for type=%s', $typeCode));

            $rows = $client->fetchFromExphuay($typeCode, '');

            foreach ($rows as $row) {
                $result = $repository->upsertWithResult($row, $force);
                $totalUpserted++;

                if ($result->wasWritten() && $result->model->fetch_status === 'success') {
                    Cache::increment('lotto:archive:'.$typeCode.':version');
                }
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $this->line(sprintf('Done. Total upserted: %d', $totalUpserted));

        return self::SUCCESS;
    }
}
