<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateExpalertBackfillSqlCommand extends Command
{
    protected $signature = 'lotto:backfill-expalert-sql
        {--dir= : Directory containing JSON files (default: storage/app/lotto/expalert-backfill)}
        {--output= : Output SQL file path (default: stdout)}
        {--slug= : Generate SQL for a single slug}';

    protected $description = 'Generate SQL from expalert backfill JSON for production database sync';

    private array $marketCodeMap;
    private array $marketIdMap;

    public function handle(LotteryRelayTypeRegistry $typeRegistry): int
    {
        $baseDir = $this->option('dir')
            ? (string) $this->option('dir')
            : storage_path('app/lotto/expalert-backfill');

        $outputPath = $this->option('output') ? (string) $this->option('output') : null;
        $targetSlug = $this->option('slug') ? trim((string) $this->option('slug')) : null;

        if (! is_dir($baseDir)) {
            $this->error("Directory not found: {$baseDir}");

            return self::FAILURE;
        }

        $this->buildReverseMarketMap($typeRegistry);

        // Collect slugs
        $slugs = [];
        if ($targetSlug !== null) {
            $filePath = $baseDir.'/'.$targetSlug.'.json';
            if (! file_exists($filePath)) {
                $this->error("JSON file not found: {$filePath}");

                return self::FAILURE;
            }
            $slugs[] = $targetSlug;
        } else {
            $manifestPath = $baseDir.'/_manifest.json';
            if (file_exists($manifestPath)) {
                $manifest = json_decode((string) file_get_contents($manifestPath), true);
                if (is_array($manifest) && isset($manifest['slugs'])) {
                    foreach ($manifest['slugs'] as $entry) {
                        $slug = $entry['slug'] ?? null;
                        if ($slug !== null && ! ($entry['error'] ?? false) && ($entry['count'] ?? 0) > 0) {
                            $slugs[] = $slug;
                        }
                    }
                }
            }
            if (empty($slugs)) {
                foreach (glob($baseDir.'/*.json') as $file) {
                    $slug = basename((string) $file, '.json');
                    if ($slug !== '_manifest') {
                        $slugs[] = $slug;
                    }
                }
            }
        }

        // Build SQL
        $headerLines = [
            '-- ============================================================',
            '-- Expalert Backfill SQL for Production',
            '-- Generated: '.Carbon::now()->toIso8601String(),
            '-- Source: '.$baseDir,
            '-- Slugs: '.count($slugs),
            '-- ============================================================',
            '--',
            '-- Safe to run multiple times (uses INSERT ON DUPLICATE KEY UPDATE)',
            '--',
            '',
            'SET NAMES utf8mb4;',
            'START TRANSACTION;',
            '',
        ];

        $sql = implode("\n", $headerLines);
        $totalRows = 0;

        foreach ($slugs as $slug) {
            $slug = trim((string) $slug);
            $filePath = $baseDir.'/'.$slug.'.json';

            if (! file_exists($filePath)) {
                continue;
            }

            $raw = file_get_contents($filePath);
            if ($raw === false) {
                continue;
            }

            $entries = json_decode($raw, true);
            if (! is_array($entries)) {
                continue;
            }

            $values = [];

            foreach ($entries as $entry) {
                if (! is_array($entry)) {
                    continue;
                }

                $result = $entry['result'] ?? [];
                $number = (string) ($result['number'] ?? '');
                $isoDate = (string) ($result['date'] ?? '');

                if ($number === '' || $isoDate === '') {
                    continue;
                }

                try {
                    $dt = Carbon::parse($isoDate)->setTimezone('Asia/Bangkok');
                } catch (\Throwable) {
                    continue;
                }

                $requestDate = $dt->format('Y-m-d');
                $lottosDate = $dt->format('Y-m-d H:i:s');
                $lottosTime = (string) ($entry['time'] ?? '');
                $thaiName = $this->escape((string) ($entry['th'] ?? ''));
                $under = $this->escape((string) ($result['under'] ?? ''));
                $marketCode = $this->escape((string) ($this->marketCodeMap[$slug] ?? $slug));
                $marketId = isset($this->marketIdMap[$slug]) ? (string) $this->marketIdMap[$slug] : 'NULL';
                $payloadJson = $this->escape(json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                $uniqueKey = hash('sha256', implode('|', [$slug, $requestDate, $slug, $requestDate]));

                $values[] = "('{$uniqueKey}','{$this->escape($slug)}','{$thaiName}','{$requestDate}',1,0,"
                    ."'{$this->escape($slug)}','{$thaiName}','{$lottosDate}','{$requestDate}','{$lottosTime}',"
                    ."'{$this->escape($number)}','{$under}','{$marketCode}',{$marketId},NULL,NOW(),'success',NULL,NULL,'{$payloadJson}')";
            }

            if (empty($values)) {
                continue;
            }

            $cnt = count($values);
            $sql .= "\n-- {$slug} ({$cnt} rows)\n";

            $sql .= "INSERT INTO lotto_result_archive_legacy_results\n"
                ."  (unique_key, type, name_th, request_date, page, source_result_id,\n"
                ."   lottos_name, lottos_th, lottos_date, lottos_date_raw, lottos_time,\n"
                ."   lottos_number, lottos_under, market_code, market_id, source_url,\n"
                ."   fetched_at, fetch_status, last_error, checksum, payload_json)\n"
                ."VALUES\n  ";

            $sql .= implode(",\n  ", $values);

            $sql .= "\nON DUPLICATE KEY UPDATE\n"
                ."  name_th = VALUES(name_th),\n"
                ."  lottos_th = VALUES(lottos_th),\n"
                ."  lottos_date = VALUES(lottos_date),\n"
                ."  lottos_date_raw = VALUES(lottos_date_raw),\n"
                ."  lottos_time = VALUES(lottos_time),\n"
                ."  lottos_number = VALUES(lottos_number),\n"
                ."  lottos_under = VALUES(lottos_under),\n"
                ."  market_code = VALUES(market_code),\n"
                ."  market_id = VALUES(market_id),\n"
                ."  payload_json = VALUES(payload_json),\n"
                ."  fetched_at = NOW(),\n"
                ."  fetch_status = 'success',\n"
                ."  updated_at = NOW();\n";

            $totalRows += count($values);
        }

        $sql .= "\nCOMMIT;\n";
        $sql .= "-- Total rows: {$totalRows}\n";

        if ($outputPath) {
            file_put_contents($outputPath, $sql);
            $this->info("SQL written to: {$outputPath} ({$totalRows} rows)");
        } else {
            $this->line($sql);
        }

        return self::SUCCESS;
    }

    private function escape(string $value): string
    {
        return str_replace(
            ['\\', "'"],
            ['\\\\', "\\'"],
            $value
        );
    }

    private function buildReverseMarketMap(LotteryRelayTypeRegistry $typeRegistry): void
    {
        $this->marketCodeMap = [];
        $this->marketIdMap = [];

        $markets = DB::table('lotto_markets')
            ->select('id', 'code')
            ->where('is_enabled', 1)
            ->get();

        foreach ($markets as $market) {
            $canonical = $typeRegistry->canonicalTypeForMarketCode((string) $market->code);
            if ($canonical !== null) {
                $this->marketCodeMap[$canonical] = (string) $market->code;
                $this->marketIdMap[$canonical] = (int) $market->id;
            }
        }

        foreach ($typeRegistry->marketCodeToCanonicalType() as $marketCode => $canonicalType) {
            if (! isset($this->marketCodeMap[$canonicalType])) {
                $this->marketCodeMap[$canonicalType] = $marketCode;
            }
        }
    }
}
