<?php

namespace Gametech\Lotto\Console\Commands;

use Carbon\Carbon;
use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Gametech\Lotto\Services\Relay\LotteryRelayTypeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BackfillExpalertLegacyArchiveCommand extends Command
{
    protected $signature = 'lotto:backfill-expalert-insert
        {--slug= : Insert a single lottery slug}
        {--dir= : Directory containing JSON files (default: storage/app/lotto/expalert-backfill)}
        {--dry-run : Show what would be inserted without persisting}';

    protected $description = 'Insert expalert backfill JSON data into lotto_result_archive_legacy_results';

    private array $marketCodeMap;
    private array $marketIdMap;

    public function handle(LotteryRelayTypeRegistry $typeRegistry): int
    {
        $baseDir = $this->option('dir')
            ? (string) $this->option('dir')
            : storage_path('app/lotto/expalert-backfill');

        $dryRun = (bool) $this->option('dry-run');
        $targetSlug = $this->option('slug') ? trim((string) $this->option('slug')) : null;

        if (! is_dir($baseDir)) {
            $this->error("Directory not found: {$baseDir}");
            $this->info('Run lotto:backfill-expalert-fetch first.');

            return self::FAILURE;
        }

        $this->buildReverseMarketMap($typeRegistry);

        // Read manifest or scan directory
        $manifestPath = $baseDir.'/_manifest.json';
        $slugs = [];

        if ($targetSlug !== null) {
            $filePath = $baseDir.'/'.$targetSlug.'.json';
            if (! file_exists($filePath)) {
                $this->error("JSON file not found: {$filePath}");

                return self::FAILURE;
            }
            $slugs[] = $targetSlug;
        } elseif (file_exists($manifestPath)) {
            $manifest = json_decode((string) file_get_contents($manifestPath), true);
            if (is_array($manifest) && isset($manifest['slugs'])) {
                foreach ($manifest['slugs'] as $entry) {
                    $slug = $entry['slug'] ?? null;
                    if ($slug !== null && ! ($entry['error'] ?? false)) {
                        $slugs[] = $slug;
                    }
                }
            }
        }

        // Fallback: scan directory for .json files
        if (empty($slugs)) {
            foreach (glob($baseDir.'/*.json') as $file) {
                $slug = basename((string) $file, '.json');
                if ($slug !== '_manifest') {
                    $slugs[] = $slug;
                }
            }
        }

        if (empty($slugs)) {
            $this->warn('No JSON files found in '.$baseDir);

            return self::SUCCESS;
        }

        $this->info('JSON directory: '.$baseDir);
        $this->info('Slugs to process: '.count($slugs));

        $totalInserted = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalErrors = 0;

        foreach ($slugs as $idx => $slug) {
            $slug = trim((string) $slug);
            if ($slug === '') {
                continue;
            }

            $filePath = $baseDir.'/'.$slug.'.json';

            if (! file_exists($filePath)) {
                $this->warn("  [{$idx}/".count($slugs)."] {$slug}: file not found, skipping");
                $totalErrors++;

                continue;
            }

            $raw = file_get_contents($filePath);
            if ($raw === false) {
                $this->warn("  [{$idx}/".count($slugs)."] {$slug}: cannot read file, skipping");
                $totalErrors++;

                continue;
            }

            $entries = json_decode($raw, true);
            if (! is_array($entries)) {
                $this->warn("  [{$idx}/".count($slugs)."] {$slug}: invalid JSON, skipping");
                $totalErrors++;

                continue;
            }

            $slugInserted = 0;
            $slugUpdated = 0;
            $slugSkipped = 0;

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
                $lottosDate = $dt;
                $lottosTime = (string) ($entry['time'] ?? '');
                $thaiName = (string) ($entry['th'] ?? '');
                $uniqueKey = hash('sha256', implode('|', [
                    $slug,
                    $requestDate,
                    $slug,
                    $requestDate,
                ]));

                $data = [
                    'unique_key' => $uniqueKey,
                    'type' => $slug,
                    'name_th' => $thaiName,
                    'request_date' => $requestDate,
                    'page' => 1,
                    'source_result_id' => 0,
                    'lottos_name' => $slug,
                    'lottos_th' => $thaiName,
                    'lottos_date' => $lottosDate,
                    'lottos_date_raw' => $requestDate,
                    'lottos_time' => $lottosTime,
                    'lottos_number' => $number,
                    'lottos_under' => (string) ($result['under'] ?? ''),
                    'market_code' => $this->marketCodeMap[$slug] ?? null,
                    'market_id' => $this->marketIdMap[$slug] ?? null,
                    'source_url' => null,
                    'fetched_at' => now(),
                    'fetch_status' => 'success',
                    'last_error' => null,
                    'checksum' => null,
                    'payload_json' => $entry,
                ];

                if ($dryRun) {
                    $this->line("  [DRY-RUN] {$slug} | {$requestDate} | {$number} | {$lottosTime}");

                    continue;
                }

                $existing = LottoResultArchiveLegacyResult::query()
                    ->where('unique_key', $uniqueKey)
                    ->first();

                if ($existing !== null) {
                    $changed = false;
                    foreach ($data as $key => $val) {
                        if ($key === 'unique_key') {
                            continue;
                        }
                        if ($existing->{$key} != $val) {
                            $changed = true;

                            break;
                        }
                    }
                    if ($changed) {
                        $existing->update(array_diff_key($data, ['unique_key' => true]));
                        $slugUpdated++;
                    } else {
                        $slugSkipped++;
                    }
                } else {
                    LottoResultArchiveLegacyResult::create($data);
                    $slugInserted++;
                }
            }

            if ($slugInserted > 0 || $slugUpdated > 0 || $slugSkipped > 0) {
                $msg = "  [{$idx}/".count($slugs)."] {$slug}: +{$slugInserted} ~{$slugUpdated} ={$slugSkipped}";
                $this->info($msg);
            }

            $totalInserted += $slugInserted;
            $totalUpdated += $slugUpdated;
            $totalSkipped += $slugSkipped;

            if ($slugInserted > 0 || $slugUpdated > 0) {
                Cache::increment('lotto:archive:'.$slug.':version');
            }
        }

        $this->info("Done. Inserted: {$totalInserted}, Updated: {$totalUpdated}, Skipped: {$totalSkipped}, Errors: {$totalErrors}");

        if ($dryRun) {
            $this->warn('DRY-RUN mode — no changes persisted.');
        }

        return self::SUCCESS;
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
