<?php

namespace Gametech\Lotto\Repositories;

use Gametech\Lotto\Models\LottoResultArchiveLegacyResult;

/**
 * Returned by LegacyArchiveResultRepository::upsert() to communicate
 * what actually happened to the caller — created, updated, or skipped
 * by the protection rule.
 */
final class LegacyArchiveUpsertResult
{
    public function __construct(
        public readonly LottoResultArchiveLegacyResult $model,
        public readonly bool $wasCreated,
        public readonly bool $wasUpdated,
        public readonly bool $wasSkipped,
    ) {}

    /**
     * True when a row was actually written (INSERT or UPDATE).
     */
    public function wasWritten(): bool
    {
        return $this->wasCreated || $this->wasUpdated;
    }
}
