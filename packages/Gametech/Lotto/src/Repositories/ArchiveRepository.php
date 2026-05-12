<?php

namespace Gametech\Lotto\Repositories;

use Gametech\Lotto\Models\LottoResultArchive;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ArchiveRepository
{
    protected LottoResultArchive $model;

    public function __construct(?LottoResultArchive $model = null)
    {
        $this->model = $model ?? new LottoResultArchive;
    }

    public function findByIdentity(string $marketCode, string $drawDate, string $drawKey): ?LottoResultArchive
    {
        return $this->model->newQuery()
            ->where('market_code', $marketCode)
            ->whereDate('draw_date', $drawDate)
            ->where('draw_key', $drawKey)
            ->first();
    }

    public function findByIdentityForUpdate(string $marketCode, string $drawDate, string $drawKey): ?LottoResultArchive
    {
        $query = $this->model->newQuery()
            ->where('market_code', $marketCode)
            ->whereDate('draw_date', $drawDate)
            ->where('draw_key', $drawKey);

        if ($this->supportsLockForUpdate()) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    protected function supportsLockForUpdate(): bool
    {
        return \DB::getDriverName() !== 'sqlite';
    }

    public function findBySourceDraw(int $sourceDrawId): Collection
    {
        return $this->model->newQuery()
            ->where('source_draw_id', $sourceDrawId)
            ->get();
    }

    public function existsBySourceDrawAndDrawKey(int $sourceDrawId, string $drawKey): bool
    {
        return $this->model->newQuery()
            ->where('source_draw_id', $sourceDrawId)
            ->where('draw_key', $drawKey)
            ->exists();
    }

    public function findDistinctDrawDates(
        string $marketCode,
        ?string $fromDate,
        ?string $toDate,
        int $perPage = 20,
    ): LengthAwarePaginator {
        return DB::table('lotto_result_archives')
            ->select('draw_date')
            ->where('market_code', $marketCode)
            ->when($fromDate, fn ($q) => $q->where('draw_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->where('draw_date', '<=', $toDate))
            ->distinct()
            ->orderBy('draw_date', 'desc')
            ->paginate($perPage);
    }

    public function findByDrawDates(string $marketCode, array $drawDates): Collection
    {
        return $this->model->newQuery()
            ->where('market_code', $marketCode)
            ->whereIn('draw_date', $drawDates)
            ->orderBy('draw_date', 'desc')
            ->orderBy('draw_key')
            ->get();
    }

    /**
     * Persist one archive row with idempotent three-branch logic.
     *
     * Returns: ['status' => 'created'|'skipped'|'corrected', 'archive' => ?LottoResultArchive]
     */
    public function persistArchiveRow(
        string $marketCode,
        string $drawDate,
        string $drawKey,
        array $resultSet,
        string $resultHash,
        string $sourceType,
        ?int $sourceDrawId,
        ?array $sourceInfo,
        string $runId,
        bool $allowExternalOverwrite = false,
    ): array {
        return DB::transaction(function () use (
            $marketCode, $drawDate, $drawKey, $resultSet, $resultHash,
            $sourceType, $sourceDrawId, $sourceInfo, $allowExternalOverwrite,
        ) {
            $existing = $this->findByIdentityForUpdate($marketCode, $drawDate, $drawKey);

            if (! $existing) {
                try {
                    $archive = $this->model->newQuery()->create([
                        'market_code' => $marketCode,
                        'draw_date' => $drawDate,
                        'draw_key' => $drawKey,
                        'result_set' => $resultSet,
                        'result_hash' => $resultHash,
                        'source_draw_id' => $sourceDrawId,
                        'source_type' => $sourceType,
                        'correction_count' => 0,
                        'source_info_json' => $sourceInfo,
                    ]);

                    return ['status' => 'created', 'archive' => $archive];
                } catch (UniqueConstraintViolationException $e) {
                    $existing = $this->findByIdentityForUpdate($marketCode, $drawDate, $drawKey);
                }
            }

            if (! $existing) {
                throw new \RuntimeException(
                    "Failed to persist archive row for {$marketCode}/{$drawDate}/{$drawKey}: row not found after insert attempt"
                );
            }

            if ($existing->result_hash === $resultHash) {
                return ['status' => 'skipped', 'archive' => $existing];
            }

            if ($sourceType === 'external_fetch' && $existing->source_type === 'internal_mirror' && ! $allowExternalOverwrite) {
                return ['status' => 'skipped', 'archive' => $existing];
            }

            $previousResultSet = $existing->result_set;
            $existing->update([
                'result_set' => $resultSet,
                'result_hash' => $resultHash,
                'correction_count' => $existing->correction_count + 1,
                'previous_result_set' => $previousResultSet,
                'corrected_at' => now(),
                'source_info_json' => $sourceInfo ?? $existing->source_info_json,
            ]);

            return ['status' => 'corrected', 'archive' => $existing->fresh()];
        });
    }
}
