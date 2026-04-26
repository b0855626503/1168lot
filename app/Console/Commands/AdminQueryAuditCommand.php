<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminQueryAuditCommand extends Command
{
    protected $signature = 'admin:query-audit
        {--log=storage/logs/01-02-03-slow-requests.log : Path to SQL slow log}
        {--output=docs/ADMIN_QUERY_AUDIT_2026_RUNTIME.md : Output markdown report}
        {--top-routes=25 : Number of routes to report}
        {--top-queries=5 : Number of repeated queries per route}
        {--min-dup=1 : Minimum duplicate_count to include query}
        {--since= : Filter log lines from this date time (Y-m-d H:i:s)}
        {--only-admin=1 : Include only admin.* routes}
        {--route=* : Restrict to exact route names}';

    protected $description = 'Audit SQL duplication by admin menu/route and validate indexes/explain plans from MySQL';

    public function handle(): int
    {
        $logPath = base_path((string) $this->option('log'));
        $outputPath = base_path((string) $this->option('output'));

        if (! is_file($logPath)) {
            $this->error("Log file not found: {$logPath}");

            return self::FAILURE;
        }

        $entries = $this->readSqlLogEntries($logPath);
        if (empty($entries)) {
            $this->warn('No SQL log entries found in target log file.');

            return self::SUCCESS;
        }

        $entries = $this->applyFilters($entries);
        if (empty($entries)) {
            $this->warn('No entries left after filters.');

            return self::SUCCESS;
        }

        $menuMap = $this->buildRouteMenuMap();
        $grouped = $this->groupByRoute($entries);
        $dbName = (string) DB::connection()->getDatabaseName();

        $topRoutes = max(1, (int) $this->option('top-routes'));
        $topQueries = max(1, (int) $this->option('top-queries'));
        $minDup = max(1, (int) $this->option('min-dup'));

        $rankedRoutes = collect($grouped)
            ->map(function (array $routeEntries, string $route) {
                $totalMs = (float) array_sum(array_column($routeEntries, 'ms'));
                $totalCount = count($routeEntries);
                $uniqueCount = count(array_unique(array_column($routeEntries, 'fingerprint')));

                return [
                    'route' => $route,
                    'total_ms' => (int) round($totalMs),
                    'sql_count' => $totalCount,
                    'unique_sql_count' => $uniqueCount,
                    'duplicate_sql_count' => max(0, $totalCount - $uniqueCount),
                    'entries' => $routeEntries,
                ];
            })
            ->sortByDesc('total_ms')
            ->take($topRoutes)
            ->values()
            ->all();

        $report = [];
        $generatedAt = now()->format('Y-m-d H:i:s');
        $report[] = '# Admin Query Audit Runtime Report';
        $report[] = '';
        $report[] = "- Generated at: `{$generatedAt}`";
        $report[] = "- Source log: `{$this->option('log')}`";
        $report[] = "- Database: `{$dbName}`";
        $report[] = "- Filters: `only-admin={$this->option('only-admin')}`, `since={$this->option('since')}`";
        $report[] = '';
        $report[] = '## Top Routes';
        $report[] = '';
        $report[] = '| Route | Menu Key(s) | SQL Count | Unique SQL | Duplicate SQL | SQL ms |';
        $report[] = '|---|---|---:|---:|---:|---:|';

        foreach ($rankedRoutes as $routeRow) {
            $route = (string) $routeRow['route'];
            $menuKeys = $menuMap[$route] ?? [];
            $menuLabel = empty($menuKeys) ? '-' : implode(', ', $menuKeys);
            $report[] = sprintf(
                '| `%s` | `%s` | %d | %d | %d | %d |',
                $route,
                $menuLabel,
                (int) $routeRow['sql_count'],
                (int) $routeRow['unique_sql_count'],
                (int) $routeRow['duplicate_sql_count'],
                (int) $routeRow['total_ms']
            );
        }

        foreach ($rankedRoutes as $routeRow) {
            $route = (string) $routeRow['route'];
            $entriesForRoute = (array) $routeRow['entries'];
            $repeatRows = $this->summarizeRepeatedQueries($entriesForRoute, $topQueries, $minDup);

            $report[] = '';
            $report[] = "## Route: `{$route}`";
            $menuKeys = $menuMap[$route] ?? [];
            $report[] = '- Menu key(s): '.(empty($menuKeys) ? '`-`' : '`'.implode('`, `', $menuKeys).'`');
            $report[] = '- SQL count: `'.(int) $routeRow['sql_count'].'`, unique: `'.(int) $routeRow['unique_sql_count'].'`, duplicate: `'.(int) $routeRow['duplicate_sql_count'].'`, total ms: `'.(int) $routeRow['total_ms'].'`';

            if (empty($repeatRows)) {
                $report[] = '- No repeated SQL above threshold.';

                continue;
            }

            foreach ($repeatRows as $i => $repeat) {
                $sample = $repeat['sample'];
                $sql = (string) $sample['sql'];
                $bindings = (array) ($sample['bindings'] ?? []);
                $tables = $this->extractTables($sql);
                $primaryTable = $tables[0] ?? null;
                $explain = $this->explainQuery($sql, $bindings);
                $indexSummary = $primaryTable ? $this->loadIndexSummary($dbName, $primaryTable) : [];

                $report[] = '';
                $report[] = '### Repeated SQL #'.($i + 1);
                $report[] = '- repeat count: `'.(int) $repeat['count'].'`, duplicate count: `'.(int) $repeat['duplicate_count'].'`, total ms: `'.(int) $repeat['total_ms'].'`, max ms: `'.(int) $repeat['max_ms'].'`';
                $report[] = '- tables: '.(empty($tables) ? '`unknown`' : '`'.implode('`, `', $tables).'`');
                $report[] = '- sql:';
                $report[] = '```sql';
                $report[] = $sql;
                $report[] = '```';
                if (! empty($bindings)) {
                    $report[] = '- bindings: `'.json_encode($bindings, JSON_UNESCAPED_UNICODE).'`';
                }

                if ($explain !== null) {
                    $report[] = '- explain (FORMAT=JSON):';
                    $report[] = '```json';
                    $report[] = $explain;
                    $report[] = '```';
                } else {
                    $report[] = '- explain: `N/A`';
                }

                if (! empty($indexSummary)) {
                    $report[] = '- indexes on primary table:';
                    foreach ($indexSummary as $indexName => $columns) {
                        $report[] = '  - `'.$indexName.'`: `'.implode(', ', $columns).'`';
                    }
                }
            }
        }

        $markdown = implode("\n", $report)."\n";
        @mkdir(dirname($outputPath), 0775, true);
        file_put_contents($outputPath, $markdown);

        $this->info('Query audit report generated: '.$outputPath);
        $this->info('Route groups analyzed: '.count($rankedRoutes));

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readSqlLogEntries(string $logPath): array
    {
        $entries = [];
        $lines = file($logPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            if (! str_contains($line, ' local.INFO: [')) {
                continue;
            }

            if (! preg_match('/^\[(?<ts>[^\]]+)\]\s+[^:]+:\s+\[(?<ms>[\d.]+)\s+ms\]\s+(?<sql>.+?)\s+(?<ctx>\{.*\})\s*$/', $line, $m)) {
                continue;
            }

            $ctxRaw = (string) ($m['ctx'] ?? '{}');
            $context = json_decode($ctxRaw, true);
            if (! is_array($context)) {
                continue;
            }

            $bindings = $this->extractBindings($context);
            $sql = $this->normalizeSql((string) $m['sql']);
            $route = (string) ($context['route'] ?? '');
            $action = (string) ($context['action'] ?? '');
            $url = (string) ($context['url'] ?? '');

            if ($route === '' && $action === '' && $url === '') {
                continue;
            }

            $entries[] = [
                'ts' => (string) $m['ts'],
                'ms' => (float) $m['ms'],
                'sql' => $sql,
                'fingerprint' => md5($sql),
                'route' => $route !== '' ? $route : '(unknown-route)',
                'action' => $action,
                'url' => $url,
                'bindings' => $bindings,
            ];
        }

        return $entries;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function applyFilters(array $entries): array
    {
        $onlyAdmin = (bool) ((int) $this->option('only-admin'));
        $since = (string) $this->option('since');
        $routeFilters = collect((array) $this->option('route'))
            ->map(fn ($x) => trim((string) $x))
            ->filter()
            ->values()
            ->all();

        return array_values(array_filter($entries, function (array $entry) use ($onlyAdmin, $since, $routeFilters): bool {
            $route = (string) ($entry['route'] ?? '');
            $ts = (string) ($entry['ts'] ?? '');

            if ($onlyAdmin && ! Str::startsWith($route, 'admin.')) {
                return false;
            }

            if ($since !== '' && $ts !== '' && strcmp($ts, $since) < 0) {
                return false;
            }

            if (! empty($routeFilters) && ! in_array($route, $routeFilters, true)) {
                return false;
            }

            return true;
        }));
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function groupByRoute(array $entries): array
    {
        $grouped = [];
        foreach ($entries as $entry) {
            $route = (string) ($entry['route'] ?? '(unknown-route)');
            $grouped[$route][] = $entry;
        }

        return $grouped;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function buildRouteMenuMap(): array
    {
        $rows = config('menu.admin', []);
        $map = [];
        if (! is_array($rows)) {
            return $map;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $route = trim((string) ($row['route'] ?? ''));
            $key = trim((string) ($row['key'] ?? ''));
            if ($route === '' || $key === '') {
                continue;
            }
            if (! isset($map[$route])) {
                $map[$route] = [];
            }
            if (! in_array($key, $map[$route], true)) {
                $map[$route][] = $key;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, array<string, mixed>>  $entries
     * @return array<int, array<string, mixed>>
     */
    private function summarizeRepeatedQueries(array $entries, int $top, int $minDup): array
    {
        $bucket = [];
        foreach ($entries as $entry) {
            $fp = (string) ($entry['fingerprint'] ?? '');
            if ($fp === '') {
                continue;
            }
            if (! isset($bucket[$fp])) {
                $bucket[$fp] = [
                    'count' => 0,
                    'total_ms' => 0.0,
                    'max_ms' => 0.0,
                    'sample' => $entry,
                ];
            }

            $ms = (float) ($entry['ms'] ?? 0);
            $bucket[$fp]['count']++;
            $bucket[$fp]['total_ms'] += $ms;
            $bucket[$fp]['max_ms'] = max($bucket[$fp]['max_ms'], $ms);
        }

        return collect($bucket)
            ->map(function (array $item) {
                $count = (int) ($item['count'] ?? 0);

                return [
                    'count' => $count,
                    'duplicate_count' => max(0, $count - 1),
                    'total_ms' => (int) round((float) ($item['total_ms'] ?? 0)),
                    'max_ms' => (int) round((float) ($item['max_ms'] ?? 0)),
                    'sample' => $item['sample'] ?? [],
                ];
            })
            ->filter(fn (array $item) => (int) $item['duplicate_count'] >= $minDup)
            ->sort(function (array $a, array $b) {
                if ($a['total_ms'] === $b['total_ms']) {
                    return $b['count'] <=> $a['count'];
                }

                return $b['total_ms'] <=> $a['total_ms'];
            })
            ->take($top)
            ->values()
            ->all();
    }

    private function normalizeSql(string $sql): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $sql));
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<int, mixed>
     */
    private function extractBindings(array $context): array
    {
        $bindings = [];
        foreach ($context as $k => $v) {
            if (is_numeric((string) $k)) {
                $bindings[(int) $k] = $v;
            }
        }
        if (empty($bindings)) {
            return [];
        }
        ksort($bindings);

        return array_values($bindings);
    }

    /**
     * @return array<int, string>
     */
    private function extractTables(string $sql): array
    {
        $tables = [];
        if (preg_match_all('/\b(?:from|join|update|into)\s+`?([a-zA-Z0-9_]+)`?/i', $sql, $m)) {
            foreach ($m[1] as $table) {
                $t = trim((string) $table);
                if ($t !== '' && ! in_array($t, $tables, true)) {
                    $tables[] = $t;
                }
            }
        }

        return $tables;
    }

    private function explainQuery(string $sql, array $bindings): ?string
    {
        $sql = ltrim($sql);
        if (! Str::startsWith(strtolower($sql), ['select', '('])) {
            return null;
        }

        try {
            $rows = DB::select('EXPLAIN FORMAT=JSON '.$sql, $bindings);
            if (empty($rows)) {
                return null;
            }

            $row = (array) $rows[0];
            $json = (string) ($row['EXPLAIN'] ?? '');
            if ($json === '') {
                return json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }

            return $json;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function loadIndexSummary(string $dbName, string $table): array
    {
        $rows = DB::table('information_schema.statistics')
            ->select(['index_name', 'seq_in_index', 'column_name'])
            ->where('table_schema', $dbName)
            ->where('table_name', $table)
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $indexName = (string) ($row->index_name ?? '');
            $column = (string) ($row->column_name ?? '');
            if ($indexName === '' || $column === '') {
                continue;
            }
            if (! isset($result[$indexName])) {
                $result[$indexName] = [];
            }
            $result[$indexName][] = $column;
        }

        return $result;
    }
}
