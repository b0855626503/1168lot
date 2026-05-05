<?php

namespace App\Console\Commands;

use App\Services\Dashboard\LottoDashboardMetricConfig;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArchiveLottoRiskSnapshotsCommand extends Command
{
    private const DAYS_RANGE_ERROR = '--days must be an integer between 1 and 180';
    private const MIN_ARCHIVE_DAYS = 1;
    private const MAX_ARCHIVE_DAYS = 180;
    private const DEFAULT_CHUNK_SIZE = 3000;
    private const DEFAULT_MAX_RUNTIME_SECONDS = 180;
    private const DEFAULT_SLEEP_MS = 100;

    protected $signature = 'dashboard:lotto-risk-archive
        {--days= : Archive snapshots older than N days}
        {--chunk= : Archive batch size per iteration}
        {--max-runtime= : Max runtime in seconds}
        {--sleep-ms= : Sleep between batches in milliseconds}
        {--delete-source : Delete rows from source table after archive insert}
        {--dry-run : Preview only, do not write}';

    protected $description = 'Archive old lotto risk snapshots to cold storage table';

    public function handle(): int
    {
        if (! Schema::hasTable('lotto_dashboard_risk_snapshot')) {
            $this->warn('table lotto_dashboard_risk_snapshot not found');

            return 0;
        }

        if (! Schema::hasTable('lotto_dashboard_risk_snapshot_archive')) {
            $this->warn('table lotto_dashboard_risk_snapshot_archive not found');

            return 0;
        }

        $daysOption = $this->option('days');
        $days = LottoDashboardMetricConfig::riskSnapshotRetentionDays();
        if ($daysOption !== null) {
            $normalizedDaysOption = trim((string) $daysOption);
            if ($normalizedDaysOption !== '') {
                if (! preg_match('/^-?\d+$/', $normalizedDaysOption)) {
                    $this->error(self::DAYS_RANGE_ERROR);

                    return 1;
                }
                $days = (int) $normalizedDaysOption;
            }
        }
        if ($days < self::MIN_ARCHIVE_DAYS || $days > self::MAX_ARCHIVE_DAYS) {
            $this->error(self::DAYS_RANGE_ERROR);

            return 1;
        }

        $chunkSize = $this->resolveIntegerOption('chunk', self::DEFAULT_CHUNK_SIZE);
        if ($chunkSize === null || $chunkSize < 1) {
            $this->error('--chunk must be an integer greater than 0');

            return 1;
        }

        $maxRuntime = $this->resolveIntegerOption('max-runtime', self::DEFAULT_MAX_RUNTIME_SECONDS);
        if ($maxRuntime === null || $maxRuntime < 1) {
            $this->error('--max-runtime must be an integer greater than 0');

            return 1;
        }

        $sleepMs = $this->resolveIntegerOption('sleep-ms', self::DEFAULT_SLEEP_MS);
        if ($sleepMs === null || $sleepMs < 0) {
            $this->error('--sleep-ms must be an integer greater than or equal to 0');

            return 1;
        }

        $cutoff = Carbon::now()->subDays($days)->startOfSecond();
        $cutoffText = $cutoff->toDateTimeString();
        $isDryRun = (bool) $this->option('dry-run');
        $deleteSource = (bool) $this->option('delete-source');
        $archiveTimestamp = Carbon::now()->toDateTimeString();

        $this->line('message=lotto_risk_snapshot_archive_started');
        $this->line('archive_days='.$days);
        $this->line('cutoff='.$cutoffText);
        $this->line('chunk='.$chunkSize);
        $this->line('max_runtime='.$maxRuntime);
        $this->line('sleep_ms='.$sleepMs);
        $this->line('delete_source='.($deleteSource ? 'yes' : 'no'));
        $this->line('dry_run='.($isDryRun ? 'yes' : 'no'));

        if ($isDryRun) {
            $sampleIds = DB::table('lotto_dashboard_risk_snapshot')
                ->where('snapshot_at', '<', $cutoffText)
                ->orderBy('snapshot_at')
                ->orderBy('id')
                ->limit($chunkSize)
                ->pluck('id');
            $sampleCount = $sampleIds->count();

            $this->line('first_batch_would_archive='.$sampleCount);
            $this->line('message=lotto_risk_snapshot_archive_finished');
            $this->line('archived_rows=0');
            $this->line('deleted_source_rows=0');
            $this->line('elapsed_seconds=0');
            $this->line('stopped_by=dry_run');

            return 0;
        }

        $startedAt = microtime(true);
        $totalArchived = 0;
        $totalSourceDeleted = 0;
        $batchNumber = 0;
        $stoppedBy = 'complete';

        while (true) {
            if ((microtime(true) - $startedAt) >= $maxRuntime) {
                $stoppedBy = 'max_runtime';
                break;
            }

            $rows = DB::table('lotto_dashboard_risk_snapshot')
                ->where('snapshot_at', '<', $cutoffText)
                ->orderBy('snapshot_at')
                ->orderBy('id')
                ->limit($chunkSize)
                ->get();

            if ($rows->isEmpty()) {
                break;
            }

            $ids = [];
            $archiveRows = [];
            $sourceRowsById = [];
            foreach ($rows as $row) {
                $rowId = (int) $row->id;
                $ids[] = $rowId;
                $sourceRowsById[$rowId] = [
                    'id' => $rowId,
                    'web_code' => (string) $row->web_code,
                    'market_id' => (int) $row->market_id,
                    'round_id' => (int) $row->round_id,
                    'bet_type' => (string) $row->bet_type,
                    'number' => (string) $row->number,
                    'snapshot_at' => (string) $row->snapshot_at,
                    'stake_total' => (string) $row->stake_total,
                    'payout_if_hit' => (string) $row->payout_if_hit,
                    'liability' => (string) $row->liability,
                ];

                $archiveRows[] = [
                    'id' => $rowId,
                    'web_code' => $sourceRowsById[$rowId]['web_code'],
                    'market_id' => $sourceRowsById[$rowId]['market_id'],
                    'round_id' => $sourceRowsById[$rowId]['round_id'],
                    'bet_type' => $sourceRowsById[$rowId]['bet_type'],
                    'number' => $sourceRowsById[$rowId]['number'],
                    'snapshot_at' => $sourceRowsById[$rowId]['snapshot_at'],
                    'stake_total' => $sourceRowsById[$rowId]['stake_total'],
                    'payout_if_hit' => $sourceRowsById[$rowId]['payout_if_hit'],
                    'liability' => $sourceRowsById[$rowId]['liability'],
                    'archived_at' => $archiveTimestamp,
                    'created_at' => $row->created_at !== null ? (string) $row->created_at : null,
                    'updated_at' => $row->updated_at !== null ? (string) $row->updated_at : null,
                ];
            }

            $archivedThisBatch = (int) DB::table('lotto_dashboard_risk_snapshot_archive')->insertOrIgnore($archiveRows);

            $deletedThisBatch = 0;
            $skippedDeleteThisBatch = 0;
            if ($deleteSource) {
                $archivedRows = DB::table('lotto_dashboard_risk_snapshot_archive')
                    ->whereIn('id', $ids)
                    ->select([
                        'id',
                        'web_code',
                        'market_id',
                        'round_id',
                        'bet_type',
                        'number',
                        'snapshot_at',
                        'stake_total',
                        'payout_if_hit',
                        'liability',
                    ])
                    ->get();

                $confirmedArchivedIds = [];
                foreach ($archivedRows as $archivedRow) {
                    $archivedId = (int) $archivedRow->id;
                    $sourceRow = $sourceRowsById[$archivedId] ?? null;
                    if ($sourceRow === null) {
                        continue;
                    }

                    if (
                        $sourceRow['web_code'] === (string) $archivedRow->web_code
                        && $sourceRow['market_id'] === (int) $archivedRow->market_id
                        && $sourceRow['round_id'] === (int) $archivedRow->round_id
                        && $sourceRow['bet_type'] === (string) $archivedRow->bet_type
                        && $sourceRow['number'] === (string) $archivedRow->number
                        && $sourceRow['snapshot_at'] === (string) $archivedRow->snapshot_at
                        && (string) $sourceRow['stake_total'] === (string) $archivedRow->stake_total
                        && (string) $sourceRow['payout_if_hit'] === (string) $archivedRow->payout_if_hit
                        && (string) $sourceRow['liability'] === (string) $archivedRow->liability
                    ) {
                        $confirmedArchivedIds[] = $archivedId;
                    }
                }

                if (! empty($confirmedArchivedIds)) {
                    $deletedThisBatch = (int) DB::table('lotto_dashboard_risk_snapshot')
                        ->whereIn('id', $confirmedArchivedIds)
                        ->delete();
                }

                $skippedDeleteThisBatch = count($ids) - count($confirmedArchivedIds);
            }

            $totalArchived += $archivedThisBatch;
            $totalSourceDeleted += $deletedThisBatch;
            $batchNumber++;

            $this->line(sprintf(
                'batch=%d archived=%d deleted_source=%d skipped_delete=%d cutoff=%s',
                $batchNumber,
                $archivedThisBatch,
                $deletedThisBatch,
                $skippedDeleteThisBatch,
                $cutoffText
            ));

            if ($rows->count() < $chunkSize) {
                break;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }
        }

        $elapsedSeconds = (int) round(microtime(true) - $startedAt);
        $this->line('message=lotto_risk_snapshot_archive_finished');
        $this->line('archived_rows='.$totalArchived);
        $this->line('deleted_source_rows='.$totalSourceDeleted);
        $this->line('elapsed_seconds='.$elapsedSeconds);
        $this->line('stopped_by='.$stoppedBy);

        return 0;
    }

    private function resolveIntegerOption(string $name, int $default): ?int
    {
        $rawValue = $this->option($name);
        if ($rawValue === null) {
            return $default;
        }

        $normalizedValue = trim((string) $rawValue);
        if ($normalizedValue === '') {
            return $default;
        }

        if (! preg_match('/^-?\d+$/', $normalizedValue)) {
            return null;
        }

        return (int) $normalizedValue;
    }
}
