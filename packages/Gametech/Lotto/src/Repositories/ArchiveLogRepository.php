<?php

namespace Gametech\Lotto\Repositories;

use Gametech\Lotto\Models\LottoResultArchiveLog;
use Illuminate\Support\Collection;

class ArchiveLogRepository
{
    protected LottoResultArchiveLog $model;

    public function __construct(?LottoResultArchiveLog $model = null)
    {
        $this->model = $model ?? new LottoResultArchiveLog;
    }

    /**
     * Append an audit log entry for an archive operation.
     */
    public function logAction(
        ?int $archiveId,
        string $marketCode,
        string $drawDate,
        string $drawKey,
        string $action,
        string $runId,
        string $status,
        ?array $oldResultSet = null,
        ?array $newResultSet = null,
        ?array $changedKeys = null,
        ?array $sourceInfo = null,
        ?string $errorMessage = null,
        ?array $trace = null,
    ): LottoResultArchiveLog {
        return $this->model->newQuery()->create([
            'archive_id' => $archiveId,
            'market_code' => $marketCode,
            'draw_date' => $drawDate,
            'draw_key' => $drawKey,
            'action' => $action,
            'run_id' => $runId,
            'status' => $status,
            'old_result_set' => $oldResultSet,
            'new_result_set' => $newResultSet,
            'changed_keys' => $changedKeys,
            'source_info_json' => $sourceInfo,
            'error_message' => $errorMessage,
            'trace_json' => $trace,
            'created_at' => now(),
        ]);
    }

    public function getLogsForArchive(int $archiveId): Collection
    {
        return $this->model->newQuery()
            ->where('archive_id', $archiveId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getLogsForRun(string $runId): Collection
    {
        return $this->model->newQuery()
            ->where('run_id', $runId)
            ->orderBy('created_at')
            ->get();
    }
}
