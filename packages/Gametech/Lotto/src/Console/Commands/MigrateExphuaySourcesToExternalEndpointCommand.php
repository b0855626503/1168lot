<?php

namespace Gametech\Lotto\Console\Commands;

use Gametech\Lotto\Models\LotteryMarket;
use Gametech\Lotto\Models\LottoResultSource;
use Illuminate\Console\Command;

class MigrateExphuaySourcesToExternalEndpointCommand extends Command
{
    protected $signature = 'lotto:migrate-exphuay-sources-to-get-lottery
        {--apply : Persist migrated source rows}
        {--source-id=* : Limit to specific source IDs}
        {--market-code=* : Limit to specific market codes}
        {--priority=1 : Priority to set after migration}
        {--is-active=1 : Active flag to set after migration (1|0)}';

    protected $description = 'Migrate existing exphuay-related source rows to external get_lottery.php endpoint';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $priority = max(1, (int) $this->option('priority'));
        $isActive = ((int) $this->option('is-active')) === 1;

        $sourceIds = array_values(array_filter(array_map(
            static fn ($value): int => (int) $value,
            (array) $this->option('source-id')
        ), static fn (int $id): bool => $id > 0));

        $marketCodes = array_values(array_filter(array_map(
            static fn ($value): string => strtolower(trim((string) $value)),
            (array) $this->option('market-code')
        ), static fn (string $code): bool => $code !== ''));

        $query = LottoResultSource::query()
            ->with('market:id,code')
            ->orderBy('id');

        if ($sourceIds !== []) {
            $query->whereIn('id', $sourceIds);
        }

        if ($marketCodes !== []) {
            $marketIds = LotteryMarket::query()
                ->whereIn('code', $marketCodes)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            if ($marketIds === []) {
                $this->warn('No market matched given --market-code filter.');

                return self::SUCCESS;
            }

            $query->whereIn('market_id', $marketIds);
        }

        $items = $query->get();
        $rows = [];
        $migratable = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($items as $source) {
            $type = $this->resolveExphuayType($source);
            if ($type === null) {
                $skipped++;
                $rows[] = [
                    'source_id' => (int) $source->id,
                    'market_code' => (string) ($source->market->code ?? '-'),
                    'type' => '-',
                    'action' => 'skip(non-exphuay)',
                    'endpoint_url' => (string) $source->endpoint_url,
                ];
                continue;
            }

            $migratable++;
            $newEndpoint = 'http://203.146.127.170/~anan/get_lottery.php';
            $newQuery = [
                'type' => $type,
                'date' => '{{lookup_date}}',
                'page' => 1,
            ];

            $nextFetchConfig = is_array($source->fetch_config_json) ? $source->fetch_config_json : [];
            if ($nextFetchConfig === []) {
                $nextFetchConfig = [
                    'fetch_strategy' => 'JSON_HTTP',
                    'http_method' => 'GET',
                ];
            }

            $nextFetchConfig['endpoint_url'] = $newEndpoint;
            $nextFetchConfig['http_method'] = 'GET';
            $nextFetchConfig['query'] = $newQuery;
            $nextFetchConfig['headers'] = [];
            $meta = is_array($nextFetchConfig['meta'] ?? null) ? $nextFetchConfig['meta'] : [];
            $runtime = is_array($meta['runtime'] ?? null) ? $meta['runtime'] : [];
            $runtime['fetch_capability'] = 'http_only';
            $runtime['allow_dom_fallback'] = false;
            $meta['runtime'] = $runtime;
            $meta['target_family'] = 'external-get-lottery';
            $meta['migrated_from_exphuay'] = true;
            $nextFetchConfig['meta'] = $meta;

            $nextParserConfig = [
                'version' => 2,
                'mode' => 'single_payload',
                'parser_type' => 'JSON_PATH',
                'fields' => [
                    'draw_date_raw' => ['type' => 'JSON_PATH', 'path' => '$.date'],
                    'first_prize_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosNumber'],
                    'last_2_raw_1' => ['type' => 'JSON_PATH', 'path' => '$.results[0].lottosUnder'],
                ],
            ];

            if ($apply) {
                $source->update([
                    'is_active' => $isActive,
                    'priority' => $priority,
                    'endpoint_url' => $newEndpoint,
                    'request_headers_json' => [],
                    'request_query_template_json' => $newQuery,
                    'fetch_config_json' => $nextFetchConfig,
                    'parser_type' => 'JSON_PATH',
                    'parser_config_json' => $nextParserConfig,
                    'fetch_strategy' => 'JSON_HTTP',
                    'selection_stage' => 'PRE_MAPPING',
                    'requires_browser' => false,
                ]);
                $updated++;
            }

            $rows[] = [
                'source_id' => (int) $source->id,
                'market_code' => (string) ($source->market->code ?? '-'),
                'type' => $type,
                'action' => $apply ? 'updated' : 'would-update',
                'endpoint_url' => $newEndpoint,
            ];
        }

        $this->line(sprintf(
            'Scanned: %d | Migratable: %d | Updated: %d | Skipped: %d | Apply: %s',
            $items->count(),
            $migratable,
            $updated,
            $skipped,
            $apply ? 'yes' : 'no'
        ));

        if ($rows !== []) {
            $this->table(['source_id', 'market_code', 'type', 'action', 'endpoint_url'], $rows);
        }

        $this->info($apply
            ? 'Migration completed.'
            : 'Dry-run completed: use --apply to persist changes.');

        return self::SUCCESS;
    }

    private function resolveExphuayType(LottoResultSource $source): ?string
    {
        $endpoint = trim((string) $source->endpoint_url);

        if (preg_match('#/internal/lottery/results/exphuay/([^/?]+)#', $endpoint, $matches) === 1) {
            return trim((string) ($matches[1] ?? '')) ?: null;
        }

        if (preg_match('#exphuay\.com/backward/([^/]+)/__data\.json#', $endpoint, $matches) === 1) {
            return trim((string) ($matches[1] ?? '')) ?: null;
        }

        $requestQueryTemplate = is_array($source->request_query_template_json) ? $source->request_query_template_json : [];
        $type = trim((string) ($requestQueryTemplate['type'] ?? ''));
        if ($type !== '') {
            return $type;
        }

        $fetchConfig = is_array($source->fetch_config_json) ? $source->fetch_config_json : [];
        $query = is_array($fetchConfig['query'] ?? null) ? $fetchConfig['query'] : [];
        $type = trim((string) ($query['type'] ?? ''));
        if ($type !== '') {
            return $type;
        }

        $requestNode = is_array($fetchConfig['request'] ?? null) ? $fetchConfig['request'] : [];
        $requestNodeQuery = is_array($requestNode['query'] ?? null) ? $requestNode['query'] : [];
        $type = trim((string) ($requestNodeQuery['type'] ?? ''));

        return $type !== '' ? $type : null;
    }
}
