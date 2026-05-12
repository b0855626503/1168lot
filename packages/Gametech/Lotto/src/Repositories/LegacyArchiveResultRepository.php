<?php

namespace Gametech\Lotto\Repositories;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;

class LegacyArchiveResultRepository
{
    /**
     * Upsert a single result row idempotently.
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
        $key = $data['unique_key'] ?? $this->deriveUniqueKey($data);
        $data['unique_key'] = $key;

        $existing = LottoResultArchiveLegacyResult::query()
            ->where('unique_key', $key)
            ->first();

        if ($existing !== null && ! $force) {
            $incomingStatus = $data['fetch_status'] ?? null;
            if (
                $existing->fetch_status === 'success' &&
                in_array($incomingStatus, ['failed', 'not_found'], true)
            ) {
                return $existing;
            }
        }

        return LottoResultArchiveLegacyResult::updateOrCreate(
            ['unique_key' => $key],
            $data
        );
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
