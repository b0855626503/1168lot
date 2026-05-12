# Implementation Plan: Lotto Public Result Archive API

**Track ID:** result-archive_20260512
**Spec:** [spec.md](./spec.md)
**Created:** 2026-05-12
**Status:** [ ] Not Started

## Overview

Implement `lotto_result_archives` as a dedicated read model using a phased approach: core storage → internal mirror logic → real-time + API → external fill → quality gates. Each phase is independently verifiable.

---

## Phase 1: Core Storage (BOA-250)

Create the database schema, Eloquent models, contracts, and repository layer.

### Tasks

- [x] Task 1.1: Create migration `2026_05_12_000001_create_lotto_result_archives.php`
  - `lotto_result_archives` table with all columns, unique index, FK constraints (SET NULL)
  - `lotto_result_archive_logs` table with all columns, indexes, no timestamps
- [x] Task 1.2: Create `LottoResultArchive` model (implements contract, `$casts`, `$fillable`)
- [x] Task 1.3: Create `LottoResultArchiveProxy` (extends model)
- [x] Task 1.4: Create `LottoResultArchiveLog` model (`public $timestamps = false`)
- [x] Task 1.5: Create `LottoResultArchiveContract` interface
- [x] Task 1.6: Create `ArchiveWriteResult` value object contract
- [x] Task 1.7: Run `php artisan migrate` and verify schema

### Verification

- [x] Migration runs without error (rollback tested)
- [x] Models instantiate with correct table names and casts
- [x] Schema matches design: columns, unique index, FK constraints
- [x] `./vendor/bin/pint --dirty` clean

---

## Phase 2: Normalizer + Checksum (BOA-251)

Build the data transformation layer: map lotto_draws → archive rows, compute integrity hashes.

### Tasks

- [x] Task 2.1: Implement `ArchiveChecksumService::computeResultHash(array<string>): string`
- [x] Task 2.2: Implement `ArchiveNormalizerService::normalizeDraw(LottoDraw): array`
  - bet_type → draw_key mapping table
  - result_set always array<string>
  - preserve leading zeros
  - unknown bet_type → skip + log warning, never throw
  - mirror command summary reports `skipped_unknown_bet_type`
- [x] Task 2.3: Unit test normalizer (leading zero, single value wrap, unknown type skip)
- [x] Task 2.4: Unit test checksum (sorted determinism, different inputs)

### Verification

- [x] Normalizer output validated: all result_sets are array<string>
- [x] Checksum deterministic: same input → same hash
- [x] Unknown bet_type logged with draw_id, market_code, bet_type, run_id
- [x] Leading zero "01" preserved as string
- [x] All new tests passing

---

## Phase 3: Writer + Idempotent Correction (BOA-252)

Build the archive writer with three-branch idempotent logic, duplicate-key race handling, and cache invalidation.

### Tasks

- [ ] Task 3.1: Implement `ArchiveRepository::persistArchiveRow(...): ArchiveWriteResult`
  - `SELECT ... FOR UPDATE` on existing row
  - CREATE branch: INSERT + log success (correction_count stays 0)
  - SKIP branch: same hash → no-op + log skipped (no correction_count increment)
  - CORRECT branch: UPDATE + increment correction_count + save previous_result_set + log corrected
  - Duplicate-key race: try/catch DuplicateEntryException → re-read with lock → fall through
  - All writes in `DB::transaction()`
- [ ] Task 3.2: Implement `ArchiveLogRepository::logAction(...)`
- [ ] Task 3.3: Implement `ArchiveWriterService::writeArchive(...): [created, skipped, corrected]`
  - iterate normalized rows → persistArchiveRow per row
  - external_fetch: validate source_info_json REQUIRED fields
  - external not overwrite internal_mirror by default (enforced HERE)
  - cache invalidation after every write
- [ ] Task 3.4: External-overwrite guard in Writer with `$allowExternalOverwrite` flag
- [ ] Task 3.5: Test writer (same hash retry = no correction, different hash = correction, concurrent insert guard)

### Verification

- [ ] Same hash re-write: correction_count unchanged, log status='skipped'
- [ ] Different hash write: correction_count incremented, previous_result_set saved
- [ ] external_fetch without source_info_json → InvalidArgumentException
- [ ] external_fetch overwriting internal_mirror → skipped by default
- [ ] Concurrent insert race: no duplicate rows, no double-count correction
- [ ] Cache keys evicted after write
- [ ] All new tests passing: `php artisan test --compact --filter=ArchiveWriter`

---

## Phase 4: Mirror Existing Command (BOA-253)

Build the CLI command to backfill archive from already-resulted draws.

### Tasks

- [ ] Task 4.1: Implement `MirrorExistingResultedDrawsCommand`
  - `--market=CODE`, `--from=DATE`, `--to=DATE`, `--chunk=N`, `--mode=missing-only|sync`
  - Default `--mode=missing-only` (safe)
  - Query: `lotto_draws WHERE status='resulted' AND draw_date BETWEEN`
  - Check per draw_key: `existsBySourceDrawAndDrawKey(drawId, drawKey)`
  - Report: created/skipped/corrected counts, skipped_unknown_bet_type
- [ ] Task 4.2: Register command in `LottoServiceProvider`
- [ ] Task 4.3: Test command (idempotent re-run, skips already-archived, creates new)

### Verification

- [ ] `php artisan lotto:mirror-result-archives --dry-run` shows counts without writes
- [ ] Two consecutive runs produce same state (idempotent)
- [ ] Unknown bet_type reported in summary
- [ ] Command handles empty result set gracefully

---

## Phase 5: Mirror Job + afterCommit Integration (BOA-254)

Wire up real-time mirroring: every draw → resulted triggers an archive job.

### Tasks

- [ ] Task 5.1: Implement `MirrorDrawToArchiveJob`
  - `ShouldQueue`, queue `lotto`, tries=3, backoff=[30,120,300]
  - Guard: skip if draw status != 'resulted'
  - No `uniqueId()` — rely on writer idempotency
- [ ] Task 5.2: Add `DB::afterCommit()` dispatch in result flow
  - Find exact location in `ResultApplier` where draw.status → 'resulted'
  - `MirrorDrawToArchiveJob::dispatch($draw->id, Str::uuid())`
- [ ] Task 5.3: Test job (draw not resulted → skip, draw resulted → archive, retry → idempotent)

### Verification

- [ ] Job dispatched only after DB commit (afterCommit verified)
- [ ] Draw not resulted → job returns without side effects
- [ ] Failed job retries, does not rollback draw
- [ ] Retry after success: same hash skip, no extra correction_count
- [ ] All new tests passing: `php artisan test --compact --filter=MirrorDraw`

---

## Phase 6: Public API + Cache + Throttle (BOA-257, BOA-258)

Build the FrontendApi read path with grouped pagination, cache, and rate limiting.

### Tasks

- [ ] Task 6.1: Implement `ArchiveRepository` read methods
  - `findByIdentity(marketCode, drawDate, drawKey): ?LottoResultArchive`
  - `findBySourceDraw(sourceDrawId): Collection`
  - `findDistinctDrawDates(marketCode, fromDate, toDate, perPage): Paginator`
  - `findByDrawDates(marketCode, array drawDates): Collection`
- [ ] Task 6.2: Implement `LottoResultArchiveController` (FrontendApi)
  - `index(marketCode)`: grouped draw_date pagination, date range validation
  - `show(marketCode, drawDate)`: all keys for one date
  - `item(marketCode, drawDate, drawKey)`: single result entry
  - Market existence check (disabled = still readable, non-existent = 404)
  - Date range rules: both-or-neither, max 366 days, single param = 422
- [ ] Task 6.3: Add routes with constraints
  - `where('marketCode', '[A-Za-z0-9_-]+')`
  - `where('drawDate', '\d{4}-\d{2}-\d{2}')`
  - `where('drawKey', '[A-Za-z0-9_-]+')`
- [ ] Task 6.4: Add throttle middleware (`throttle:60,1`)
- [ ] Task 6.5: Implement cache layer (60-120s TTL for list, 1 day for specific)
- [ ] Task 6.6: No raw_payload, source_info_json, or internal columns in any API response

### Verification

- [ ] List paginates at draw_date level: per_page=20 = 20 dates, not 20 keys
- [ ] Only from_date → 422; range > 366 days → 422
- [ ] from_date > to_date → 422
- [ ] Disabled market → results still returned (historical record)
- [ ] Non-existent market → 404
- [ ] No internal fields in response (verify JSON structure)
- [ ] per_page > 50 → capped
- [ ] Throttle blocks at 61 req/min
- [ ] Cache hit returns fresh data; cache miss queries DB
- [ ] All new tests passing: `php artisan test --compact --filter=LottoResultArchive`

---

## Phase 7: Fill Missing + External Fetcher (BOA-255)

Build external gap-filling: identify missing dates, fetch from external sources, write with safety rules.

### Tasks

- [ ] Task 7.1: Implement `ExternalResultFetcherService::fetchMissing(marketCode, drawDate)`
  - Uses existing `LottoResultSource` configs
  - Returns normalized archive rows or null
- [ ] Task 7.2: Implement `FillMissingResultsCommand`
  - `--market=CODE`, `--from=DATE`, `--to=DATE`, `--dry-run`
  - Identify gaps where zero archive rows exist
  - Fetch → normalize → writer (with source_info_json)
- [ ] Task 7.3: Verify external never overwrites internal_mirror
- [ ] Task 7.4: Test fill command (dry-run, actual fill, idempotent re-run)

### Verification

- [ ] External fill writes with source_type=external_fetch, source_draw_id=null
- [ ] source_info_json present with source_url, fetched_at, parser_version
- [ ] Internal_mirror rows protected from external overwrite
- [ ] Dry-run reports what would happen without writing
- [ ] All new tests passing

---

## Phase 8: Reconcile (BOA-256)

Build consistency checking: compare archive against source, guarded fix capability.

### Tasks

- [ ] Task 8.1: Implement `ArchiveReconcileService`
  - Compare archive hash vs lotto_draws computed hash
  - Compare archive vs external source (for external_fetch rows)
  - Report mismatches with detail
  - `--fix` with internal source priority, explicit `--source-priority`
- [ ] Task 8.2: Implement `ReconcileResultArchiveCommand`
  - REQUIRED: `--market`, `--from`, `--to`
  - Max 366 days per run
  - `--dry-run` default (report only)
  - `--fix` requires `--yes`
- [ ] Task 8.3: Test reconcile (dry-run, fix with internal priority, guard enforcement)

### Verification

- [ ] `--fix` without `--yes` → rejected
- [ ] `--fix` without `--market`/`--from`/`--to` → rejected
- [ ] Range > 366 days → rejected
- [ ] Dry-run reports mismatches without modifying data
- [ ] Fix with internal priority: archive updated to match lotto_draws
- [ ] External_fetch rows not overwritten without explicit `--source-priority=external`
- [ ] All new tests passing

---

## Phase 9: Tests + Regression Guards (BOA-259)

Comprehensive test suite covering all critical paths.

### Tasks

- [ ] Task 9.1: Normalizer tests (leading zero, single→array, unknown bet_type skip)
- [ ] Task 9.2: Writer tests (create, same-hash skip, different-hash correct, concurrent insert, cache invalidation)
- [ ] Task 9.3: Job tests (draw not resulted skip, mirror success, retry idempotent, no rollback)
- [ ] Task 9.4: API tests (grouped pagination, max per_page, no raw_payload, date range validation, market existence)
- [ ] Task 9.5: Fill tests (external does not overwrite internal, source_info_json required)
- [ ] Task 9.6: Reconcile tests (guard enforcement, internal priority, dry-run)
- [ ] Task 9.7: Command tests (mirror idempotent, fill dry-run, reconcile guards)
- [ ] Task 9.8: Run full test suite → `php artisan test --compact`

### Verification

- [ ] All tests passing
- [ ] No regression in existing Lotto/FrontendApi tests
- [ ] Critical test cases from architecture doc §10 covered

---

## Phase 10: Docs + Runbook (BOA-260)

Documentation and operational guides.

### Tasks

- [ ] Task 10.1: API documentation (endpoints, request/response examples, error codes)
- [ ] Task 10.2: Database schema documentation (migration notes, rollback procedure)
- [ ] Task 10.3: Command reference (`lotto:mirror-result-archives`, `lotto:fill-missing-results`, `lotto:reconcile-result-archive`)
- [ ] Task 10.4: Runbook (initial mirror procedure, fill-missing procedure, reconcile procedure, incident response)
- [ ] Task 10.5: Update `docs/internal/03_DOMAINS/lotto.md` with archive section
- [ ] Task 10.6: Update `docs/internal/01_SYSTEM/system-map.md` with new entrypoints

### Verification

- [ ] All docs use bilingual convention (Thai docs + English code references)
- [ ] Runbook covers: initial setup, daily operations, incident response
- [ ] `bash scripts/docs-validation/run.sh` passes

---

## Final Verification

- [ ] All 10 acceptance criteria met
- [ ] All tests passing: `php artisan test --compact`
- [ ] Validation passing: `bash scripts/docs-validation/run.sh`
- [ ] Formatting clean: `./vendor/bin/pint --dirty`
- [ ] Ready for PR review

---

_Generated by Conductor. Tasks will be marked [~] in progress and [x] complete._
