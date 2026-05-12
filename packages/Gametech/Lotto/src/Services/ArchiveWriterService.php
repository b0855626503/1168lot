<?php

namespace Gametech\Lotto\Services;

use Gametech\Lotto\Repositories\ArchiveLogRepository;
use Gametech\Lotto\Repositories\ArchiveRepository;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ArchiveWriterService
{
    public const ACTION_MIRROR_INTERNAL = 'mirror_internal';
    public const ACTION_FETCH_EXTERNAL = 'fetch_external';
    public const ACTION_CORRECTION = 'correction';
    public const ACTION_RECONCILE = 'reconcile';
    protected ArchiveRepository $archiveRepo;
    protected ArchiveLogRepository $logRepo;

    public function __construct(
        ?ArchiveRepository $archiveRepo = null,
        ?ArchiveLogRepository $logRepo = null,
    ) {
        $this->archiveRepo = $archiveRepo ?? new ArchiveRepository;
        $this->logRepo = $logRepo ?? new ArchiveLogRepository;
    }

    /**
     * Write multiple normalized archive rows with idempotent three-branch logic.
     *
     * @param  array  $normalizedRows  From ArchiveNormalizerService::normalizeDraw()
     * @param  string  $sourceType  'internal_mirror' or 'external_fetch'
     * @param  int|null  $sourceDrawId  lotto_draws.id or null
     * @param  string  $runId  UUID for this batch
     * @param  bool  $allowExternalOverwrite  Only true from ReconcileService with --fix --source-priority=external
     * @return array{created: int, skipped: int, corrected: int, logs: int}
     */
    public function writeArchive(
        array $normalizedRows,
        string $sourceType,
        ?int $sourceDrawId,
        string $runId,
        bool $allowExternalOverwrite = false,
    ): array {
        if ($sourceType === 'external_fetch') {
            $this->validateExternalSourceInfo($normalizedRows, $sourceType);
        }

        $created = 0;
        $skipped = 0;
        $corrected = 0;
        $logs = 0;

        foreach ($normalizedRows as $row) {
            $result = $this->archiveRepo->persistArchiveRow(
                marketCode: $row['market_code'],
                drawDate: $row['draw_date'],
                drawKey: $row['draw_key'],
                resultSet: $row['result_set'],
                resultHash: $row['result_hash'],
                sourceType: $sourceType,
                sourceDrawId: $sourceDrawId,
                sourceInfo: $row['source_info_json'] ?? $row['source_info'] ?? null,
                allowExternalOverwrite: $allowExternalOverwrite,
            );

            $status = $result['status'];
            $archive = $result['archive'];

            match ($status) {
                'created' => $created++,
                'skipped' => $skipped++,
                'corrected' => $corrected++,
                default => null,
            };

            $logAction = match ($status) {
                'corrected' => self::ACTION_CORRECTION,
                default => $sourceType === 'external_fetch'
                    ? self::ACTION_FETCH_EXTERNAL
                    : self::ACTION_MIRROR_INTERNAL,
            };

            $logStatus = match ($status) {
                'created' => 'success',
                'skipped' => 'skipped',
                'corrected' => 'corrected',
                default => 'success',
            };

            $this->logRepo->logAction(
                archiveId: $archive?->id,
                marketCode: $row['market_code'],
                drawDate: $row['draw_date'],
                drawKey: $row['draw_key'],
                action: $logAction,
                runId: $runId,
                status: $logStatus,
                oldResultSet: $status === 'corrected' ? $archive?->previous_result_set : null,
                newResultSet: $row['result_set'],
                changedKeys: $status === 'corrected' ? $this->computeChangedKeys(
                    $archive?->previous_result_set ?? [],
                    $row['result_set'],
                ) : null,
            );
            $logs++;

            $this->invalidateCache($row['market_code'], $row['draw_date'], $row['draw_key']);
        }

        return compact('created', 'skipped', 'corrected', 'logs');
    }

    protected function validateExternalSourceInfo(array $normalizedRows, string $sourceType): void
    {
        foreach ($normalizedRows as $row) {
            $info = $row['source_info_json'] ?? $row['source_info'] ?? null;

            if ($info === null) {
                throw new InvalidArgumentException(
                    'source_info_json is REQUIRED for external_fetch source_type'
                );
            }

            foreach (['source_url', 'fetched_at', 'parser_version'] as $field) {
                if (! isset($info[$field])) {
                    throw new InvalidArgumentException(
                        "source_info_json.{$field} is REQUIRED for external_fetch"
                    );
                }
            }
        }
    }

    protected function computeChangedKeys(array $old, array $new): array
    {
        $oldSorted = $old;
        $newSorted = $new;
        sort($oldSorted, SORT_STRING);
        sort($newSorted, SORT_STRING);

        $added = array_values(array_diff($newSorted, $oldSorted));
        $removed = array_values(array_diff($oldSorted, $newSorted));

        return array_filter(compact('added', 'removed'), fn ($v) => $v !== []);
    }

    protected function invalidateCache(string $marketCode, string $drawDate, string $drawKey): void
    {
        Cache::forget("lotto:archive:{$marketCode}:{$drawDate}");
        Cache::forget("lotto:archive:{$marketCode}:{$drawDate}:{$drawKey}");
        Cache::increment("lotto:archive:{$marketCode}:version");
    }
}
