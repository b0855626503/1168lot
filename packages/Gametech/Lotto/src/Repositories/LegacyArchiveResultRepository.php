<?php

namespace Gametech\Lotto\Repositories;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;
use Illuminate\Support\Facades\DB;

class LegacyArchiveResultRepository
{
    /**
     * Upsert a single result row idempotently and return the persisted model.
     *
     * This method is the original public contract of the repository.
     * For commands or callers that need fine-grained outcome information
     * (created / updated / skipped), use {@see upsertWithResult()} instead.
     *
     * unique_key derivation (if not already present in $data):
     *   - if source_result_id is present: sha256(type|request_date|source_result_id)
     *   - otherwise: sha256(type|request_date|lottos_name|lottos_date_raw)
     *
     * Protection rule when $force = false:
     *   An existing row with fetch_status='success' will NOT be overwritten
     *   by incoming data whose fetch_status is 'failed' or 'not_found'.
     *   Pass $force = true to bypass this protection.
     *
     * @param  array<string, mixed>  $data  Mapped field array (must include type, lottos_name, unique_key or derivable fields)
     * @param  bool  $force  If true, overwrite even success rows
     */
    public function upsert(array $data, bool $force = false): LottoResultArchiveLegacyResult
    {
        return $this->upsertWithResult($data, $force)->model;
    }

    /**
     * Upsert a single result row idempotently and return a rich result object.
     *
     * Identical to {@see upsert()} but returns a {@see LegacyArchiveUpsertResult} value object
     * that reports whether the row was created, actually updated, or skipped — allowing callers
     * such as the fetch command to make accurate cache-invalidation decisions.
     *
     * @param  array<string, mixed>  $data
     */
    public function upsertWithResult(array $data, bool $force = false): LegacyArchiveUpsertResult
    {
        $key = $data['unique_key'] ?? $this->deriveUniqueKey($data);
        $data['unique_key'] = $key;

        return DB::transaction(function () use ($key, $data, $force): LegacyArchiveUpsertResult {
            $existing = LottoResultArchiveLegacyResult::query()
                ->where('unique_key', $key)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && ! $force) {
                $incomingStatus = $data['fetch_status'] ?? null;
                if (
                    $existing->fetch_status === 'success' &&
                    in_array($incomingStatus, ['failed', 'not_found'], true)
                ) {
                    return new LegacyArchiveUpsertResult(
                        model: $existing,
                        wasCreated: false,
                        wasUpdated: false,
                        wasSkipped: true,
                    );
                }
            }

            $values = array_diff_key($data, ['unique_key' => true]);

            $model = LottoResultArchiveLegacyResult::updateOrCreate(
                ['unique_key' => $key],
                $values
            );

            return new LegacyArchiveUpsertResult(
                model: $model,
                wasCreated: $model->wasRecentlyCreated,
                wasUpdated: ! $model->wasRecentlyCreated && $model->wasChanged(),
                wasSkipped: false,
            );
        });
    }

    /**
     * Derive the unique_key from the data array.
     *
     * @param  array<string, mixed>  $data
     */
    protected function deriveUniqueKey(array $data): string
    {
        $type = (string) ($data['type'] ?? '');
        $requestDate = (string) ($data['request_date'] ?? '');
        $sourceResultId = isset($data['source_result_id']) ? (string) $data['source_result_id'] : null;

        if ($sourceResultId !== null && $sourceResultId !== '') {
            return hash('sha256', implode('|', [$type, $requestDate, $sourceResultId]));
        }

        $lottosName = (string) ($data['lottos_name'] ?? '');
        $lottosDateRaw = (string) ($data['lottos_date_raw'] ?? '');

        return hash('sha256', implode('|', [$type, $requestDate, $lottosName, $lottosDateRaw]));
    }
}
