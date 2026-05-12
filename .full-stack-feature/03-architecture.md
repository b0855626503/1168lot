# Backend Architecture: Lotto Public Result Archive API

> Grounded in: Lotto package + FrontendApi BFF patterns
> Pure backend — no frontend components
> Revised: 2026-05-12 (incorporating review feedback)

## 1. Package & Module Layout

```
packages/Gametech/Lotto/src/
├── Database/Migrations/
│   └── 2026_05_12_000001_create_lotto_result_archives.php
├── Models/
│   ├── LottoResultArchive.php          (implements LottoResultArchiveContract)
│   ├── LottoResultArchiveProxy.php
│   ├── LottoResultArchiveLog.php
├── Contracts/
│   └── LottoResultArchive.php
├── Repositories/
│   ├── ArchiveRepository.php
│   ├── ArchiveLogRepository.php
├── Services/
│   ├── ArchiveNormalizerService.php     (BOA-251)
│   ├── ArchiveWriterService.php         (BOA-252 — idempotent with correction)
│   ├── ArchiveChecksumService.php       (BOA-251 checksum)
│   ├── ArchiveReconcileService.php      (BOA-256)
│   ├── ExternalResultFetcherService.php (BOA-255 — external fill, never overwrites internal)
├── Jobs/
│   ├── MirrorDrawToArchiveJob.php       (BOA-254 afterCommit, retry-safe)
├── Console/Commands/
│   ├── MirrorExistingResultedDrawsCommand.php (BOA-253)
│   ├── FillMissingResultsCommand.php          (BOA-255)
│   ├── ReconcileResultArchiveCommand.php      (BOA-256 — guarded --fix)
├── Providers/
│   └── LottoServiceProvider.php          (updated: register commands, jobs, bindings)

packages/Gametech/FrontendApi/src/
├── Http/Controllers/Api/V1/
│   └── LottoResultArchiveController.php  (BOA-257 — grouped draw_date pagination)
├── Routes/
│   └── api.php                            (updated: add archive routes, throttle)
```

## 2. API Design

### Public Endpoints (FrontendApi, no auth required)

```
GET  /api/v1/lotto/results/{marketCode}
     → Paginated results for a market — paginated at draw_date level, NOT row level
     Query: ?from_date=2026-01-01&to_date=2026-05-12&page=1&per_page=20
     Max per_page: 50

GET  /api/v1/lotto/results/{marketCode}/{drawDate}
     → All result entries for a specific draw date, grouped by draw_key

GET  /api/v1/lotto/results/{marketCode}/{drawDate}/{drawKey}
     → Single result entry
```

**Market existence policy:**
- Market must exist in `lotto_markets` (by `code`) to query
- Disabled markets (`is_enabled=false`): historical data is still readable — archive is a historical record, not a live betting service
- Non-existent market code → 404
- This decision avoids coupling archive API availability to market operational status

**Date range rules (enforced at controller, before any query):**
- Neither `from_date` nor `to_date` provided: default to last 365 days from today
- Only one of `from_date` / `to_date` provided: **422** — both or neither
- Both provided but range > 366 days: **422** — max 366 days per request
- `from_date > to_date`: **422** — invalid range

### Pagination Design (Critical)

List endpoint paginates at **draw_date group level**, NOT at individual archive row level.

```
Query step 1: SELECT DISTINCT draw_date ... ORDER BY draw_date DESC LIMIT per_page OFFSET ...
Query step 2: SELECT * WHERE market_code=? AND draw_date IN (dates from step 1) ORDER BY draw_date DESC, draw_key ASC
Application: group rows by draw_date → results object
```

This ensures `per_page=20` means 20 draw dates, not 20 result keys across potentially only 3-4 dates.

**Pagination guards:**
- `per_page` max: 50, default: 20
- Date range enforcement: see Date Range Rules above (both-or-neither, max 366 days, 422 on violation)

### Response Format

```json
// GET /api/v1/lotto/results/{marketCode}
{
  "data": [
    {
      "market_code": "huay-thai",
      "draw_date": "2026-05-12",
      "results": {
        "three_up": ["123"],
        "two_down": ["45"],
        "three_front": ["456", "789"],
        "run_up": ["1"],
        "run_down": ["2"]
      }
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}

// GET /api/v1/lotto/results/{marketCode}/{drawDate}
{
  "data": {
    "market_code": "huay-thai",
    "draw_date": "2026-05-12",
    "results": {
      "three_up": ["123"],
      "two_down": ["45"]
    }
  }
}

// GET /api/v1/lotto/results/{marketCode}/{drawDate}/{drawKey}
{
  "data": {
    "market_code": "huay-thai",
    "draw_date": "2026-05-12",
    "draw_key": "three_up",
    "result_set": ["123"]
  }
}
```

**No `raw_payload`, no `source_info_json`, no internal columns in any API response.**

## 3. Service Layer

### 3.1 ArchiveNormalizerService (BOA-251)

**Responsibility:** Transform `lotto_draws.result_number` + `lotto_draw_bet_settings.bet_type` → archive rows

```
normalizeDraw(LottoDraw $draw): array<int, array>
  Returns: [{market_code, draw_date, draw_key, result_set: array<string>}]

normalizeDrawResultSet(LottoDraw $draw, string $betType): array<string>
  Extracts result_number for specific bet_type from the JSON structure
  GUARANTEE: returns array<string> only
  Leading zeros preserved: "01", "007"
  Single value wrapped: ["123"] not "123"
```

**Key behaviors:**
- Reads `lotto_markets.code` from `draw.market.code`
- Maps `bet_type` → `draw_key` using internal mapping table
- **Unknown bet_type:** skip and log warning (with `draw_id`, `market_code`, `bet_type`, `run_id`); NEVER throw (don't break mirror flow for new types)
- Mirror command summary MUST report `skipped_unknown_bet_type` count
- Reconcile MUST flag draw_keys present in source but missing in archive (could be unknown bet_type gaps)
- **result_set always array<string>** — validated at output boundary

### 3.2 ArchiveChecksumService (BOA-251)

```
computeResultHash(array<string> $resultSet): string
  $sorted = sort($resultSet);
  return hash('sha256', implode('|', $sorted));
  // Example: ["456","123"] → sha256("123|456")

verifyArchiveIntegrity(LottoResultArchive $archive): bool
  Returns: computed_hash === archive.result_hash
```

**All result_sets are treated as unordered sets.** Positional semantics handled via separate `draw_key` values.

### 3.3 ArchiveWriterService (BOA-252) — Idempotent

```
writeArchive(array $normalizedRows, string $sourceType, ?int $sourceDrawId, string $runId): array
  Returns: [created => N, skipped => N, corrected => N]

  Transaction per row:
    1. Lock existing row: SELECT ... FOR UPDATE
    2. No existing → INSERT, log action=mirror status=success
    3. Existing + same hash → SKIP, log action=mirror status=skipped
       **DO NOT increment correction_count**
    4. Existing + different hash → UPDATE, increment correction_count,
       save previous_result_set, set corrected_at,
       log action=correct status=corrected
    5. Invalidate cache keys (see cache invalidation)

handleCorrection(int $archiveId, array<string> $newResultSet, string $reason, string $runId): LottoResultArchive
  Same as writeArchive case 4 (different hash)
  Transaction: lockForUpdate → move result_set to previous_result_set → write new → log → invalidate cache
```

**Idempotency contract:** Same input + same result = no side effects. Queue retry on Horizon will never inflate `correction_count`.

**External-overwrite guard (enforced HERE, not just in fetcher):**

Writer MUST reject or skip `sourceType='external_fetch'` updates when:
- Existing row has `source_type='internal_mirror'` AND
- New `result_hash` differs from existing hash

Exception: only when `$allowExternalOverwrite=true` is explicitly passed (by ReconcileService with `--fix --source-priority=external`).

This ensures even if future code paths call Writer directly with `external_fetch` (bypassing ExternalResultFetcherService), the default behavior is safe.

### 3.4 ExternalResultFetcherService (BOA-255)

```
fetchMissing(string $marketCode, string $drawDate): ?array
  Uses existing LottoResultSource configuration to fetch from external API
  Returns normalized archive rows or null

Safety rules:
  - source_draw_id = null
  - source_type = 'external_fetch'
  - source_info_json REQUIRED: {source_url, fetched_at, parser_version, source_id}
  - NEVER create lotto_draws rows
  - If archive row exists with source_type='internal_mirror':
    → DEFAULT: log mismatch, DO NOT overwrite
    → OVERRIDE: only via reconcile --fix with explicit --source-priority
```

### 3.5 ArchiveReconcileService (BOA-256)

```
reconcileMarket(string $marketCode, string $fromDate, string $toDate, bool $dryRun=true): array
  Returns: [{market_code, draw_date, draw_key, archive_hash, internal_hash, match, action_taken}]

autoFix(array $mismatches, string $sourcePriority='internal'): array
  - internal priority: overwrite archive with lotto_draws data
  - NEVER overwrites internal_mirror with external_fetch data (source priority)
  - Requires explicit --source-priority flag for non-default

Safety guards:
  - --market REQUIRED
  - --from and --to REQUIRED
  - Date range ≤ 366 days per run
  - --fix requires --yes confirmation
  - Default = --dry-run (report only)
```

## 4. Jobs

### 4.1 MirrorDrawToArchiveJob (BOA-254)

```php
class MirrorDrawToArchiveJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;
    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $drawId,
        public string $runId,
    ) {
        $this->onQueue('lotto');
    }

    public function handle(
        ArchiveNormalizerService $normalizer,
        ArchiveWriterService $writer,
    ): void {
        $draw = LottoDraw::findOrFail($this->drawId);

        // GUARD: skip if draw is not in resulted status
        if ($draw->status !== 'resulted') {
            Log::info('MirrorDrawToArchiveJob: draw not resulted, skipping', [
                'draw_id' => $this->drawId,
                'status' => (string) $draw->status,
            ]);
            return;
        }

        $rows = $normalizer->normalizeDraw($draw);
        $writer->writeArchive($rows, 'internal_mirror', $draw->id, $this->runId);
    }

    // No uniqueId() — uniqueness is enforced at the Writer level via
    // lockForUpdate + same-hash skip. Job-level uniqueness would risk
    // blocking legitimate re-mirror on correction/re-result.
}
```

**Safety:** Job failure does NOT rollback draw. afterCommit dispatch ensures draw transaction already committed.
**Idempotency:** Same draw re-processed → Writer skips same-hash rows, never double-counts corrections.
**Retry:** Horizon retry with backoff [30s, 120s, 300s]. Writer's lockForUpdate prevents concurrent corruption.

## 5. Commands

### 5.1 MirrorExistingResultedDrawsCommand (BOA-253)

```bash
php artisan lotto:mirror-result-archives [--market=CODE] [--from=DATE] [--to=DATE] [--chunk=N] [--mode=missing-only|sync]
```

- Default: `--mode=missing-only` (safe — only fill gaps)
- `--mode=sync`: compare hash, trigger correction if different
- Query: `lotto_draws WHERE status='resulted' AND draw_date BETWEEN ? AND ?`
- Check per draw_key (NOT per draw): `existsBySourceDrawAndDrawKey(drawId, drawKey)`
- Process in chunks via queue dispatch for large date ranges

### 5.2 FillMissingResultsCommand (BOA-255)

```bash
php artisan lotto:fill-missing-results [--market=CODE] [--from=DATE] [--to=DATE] [--dry-run]
```

- Identify draw dates with zero archive rows for the market
- For each gap: ExternalResultFetcherService → ArchiveNormalizerService → ArchiveWriterService
- **NEVER overwrites existing internal_mirror rows** (see external fill safety rules)
- Log all actions to archive_logs

### 5.3 ReconcileResultArchiveCommand (BOA-256)

```bash
php artisan lotto:reconcile-result-archive --market=CODE --from=DATE --to=DATE [--fix] [--yes] [--source-priority=internal|external]
```

- REQUIRED: `--market`, `--from`, `--to`
- Date range ≤ 366 days
- Default: `--dry-run` (report only, no writes)
- `--fix` requires `--yes`
- `--source-priority=internal` (default): internal draw data wins
- `--source-priority=external`: only for external_fetch rows without internal counterpart

## 6. Integration Points

### 6.1 Hook into Result Flow (BOA-254)

Dispatch `MirrorDrawToArchiveJob` after draw status → `resulted`:

```php
// In ResultApplier (packages/Gametech/Lotto/src/Services/AutoResult/ResultApplier.php)
// After draw status update to 'resulted' commits:
DB::afterCommit(function () use ($draw) {
    MirrorDrawToArchiveJob::dispatch(
        $draw->id,
        (string) Str::uuid(),
    );
});
```

**Critical:** `afterCommit` ensures job dispatches only after DB transaction commits. A failed mirror job does NOT rollback the draw.

### 6.2 LottoServiceProvider Registration

```php
// In LottoServiceProvider
$this->app->singleton(ArchiveNormalizerService::class);
$this->app->singleton(ArchiveChecksumService::class);
$this->app->singleton(ArchiveWriterService::class);

$this->commands([
    MirrorExistingResultedDrawsCommand::class,
    FillMissingResultsCommand::class,
    ReconcileResultArchiveCommand::class,
]);
```

## 7. Throttle & Cache (BOA-258)

### Middleware: Throttle + Cache Headers

```php
// In FrontendApi routes
Route::middleware(['throttle:60,1'])->group(function () {
    Route::get('lotto/results/{marketCode}', [LottoResultArchiveController::class, 'index'])
        ->where('marketCode', '[A-Za-z0-9_-]+');
    Route::get('lotto/results/{marketCode}/{drawDate}', [LottoResultArchiveController::class, 'show'])
        ->where('marketCode', '[A-Za-z0-9_-]+')
        ->where('drawDate', '\d{4}-\d{2}-\d{2}');
    Route::get('lotto/results/{marketCode}/{drawDate}/{drawKey}', [LottoResultArchiveController::class, 'item'])
        ->where('marketCode', '[A-Za-z0-9_-]+')
        ->where('drawDate', '\d{4}-\d{2}-\d{2}')
        ->where('drawKey', '[A-Za-z0-9_-]+');
});
```

### Cache Strategy

```php
// Cache keys
// List: include from_date, to_date, page, per_page — NOT just page/perPage
$queryFingerprint = md5("{$fromDate}|{$toDate}|{$page}|{$perPage}");
"lotto:archive:{$marketCode}:list:{$queryFingerprint}"   → TTL 60-120 seconds
"lotto:archive:{$marketCode}:{$drawDate}"                 → TTL 1 day (historical — immutable after settlement)
"lotto:archive:{$marketCode}:{$drawDate}:{$drawKey}"      → TTL 1 day

// Recommended: use cache tags if Redis driver supports it
Cache::tags(["lotto_archive", "lotto_archive:{$marketCode}"])

// Date range enforcement: see Date Range Rules in API Design section
```

### Cache Invalidation (in ArchiveWriterService)

**Every write/correction MUST invalidate related caches:**

```php
// After any INSERT/UPDATE to lotto_result_archives
Cache::forget("lotto:archive:{$marketCode}:{$drawDate}");
Cache::forget("lotto:archive:{$marketCode}:{$drawDate}:{$drawKey}");

// If using tags:
Cache::tags(["lotto_archive:{$marketCode}"])->flush();

// List caches are short-lived (60-120s TTL) and naturally expire;
// or flush them explicitly if immediate consistency is required
```

**Without tags:** Reduce list endpoint TTL to 60-120 seconds. Invalidation flushes specific date/key caches plus "latest" short-TTL cache.

## 8. Safety Guardrails

| Rule | Implementation |
|------|---------------|
| Never touch `lotto_draws` writes | Archive services read only, use separate tables |
| No wallet transactions | Archive scope excluded from Wallet package |
| No ticket status changes | Archive doesn't reference ticket tables |
| No yeekee | Filter: exclude markets/group with yeekee type |
| No new queue | All jobs use `onQueue('lotto')` |
| No raw_payload exposure | Archive API returns only `result_set`, never raw data |
| Unbounded prevention | All list endpoints paginated (max per_page=50) at draw_date level |
| Leading zero preservation | result_set values always `string` type, validated in Normalizer |
| result_set array guarantee | Always `array<string>`, validated at Normalizer boundary |
| Mirror fail ≠ draw rollback | afterCommit dispatch + job retry isolation |
| External never overwrites internal | Writer + storage rules enforced |
| Correction idempotency | Same hash = skip, never inflate correction_count |
| Log survives archive deletion | FK `ON DELETE SET NULL`, log is append-only |
| Reconcile guard rails | `--market` + `--from` + `--to` required; `--fix` requires `--yes`; max 366 days |

## 9. Implementation Order

```
Phase A (Core storage):
  1. Migration + Models + Contracts                          [BOA-250]
  
Phase B (Core logic — internal mirror first):
  2. Normalizer + draw_key mapping + Checksum                [BOA-251]
  3. Writer + log + idempotent correction                    [BOA-252]
  4. Mirror command (direct/sync, --mode=missing-only)       [BOA-253]

Phase C (Real-time + read path):
  5. Mirror job + afterCommit integration                    [BOA-254]
  6. Archive repository + grouped draw_date pagination       [BOA-257 repo layer]
  7. FrontendApi controller + routes                         [BOA-257]
  8. Cache + throttle + cache invalidation on write          [BOA-258]

Phase D (External & maintenance — start only after Phase B-C stable):
  9. Fill missing command + external fetcher                  [BOA-255]
  10. Reconcile command + guarded --fix                       [BOA-256]

Phase E (Quality):
  11. Tests + regression guards                               [BOA-259]
  12. Docs + runbook                                          [BOA-260]
```

**Rationale:** Don't start external fetch before internal mirror/read path is stable. External sources add ambiguity about source priority.

## 10. Test Plan (BOA-259)

Minimum required test cases:

| Group | Test Case |
|-------|----------|
| **Normalizer** | preserve leading zero: `"01"`, `"007"` → string |
| **Normalizer** | single result → array: `"123"` → `["123"]` |
| **Normalizer** | unknown bet_type → skip + log warning, never throw |
| **Normalizer** | multiple bet_types → correct draw_key mapping |
| **Writer** | insert new archive → log success, correction_count=0 |
| **Writer** | same hash retry → skipped, correction_count unchanged |
| **Writer** | different hash → corrected, correction_count incremented, previous_result_set saved |
| **Writer** | concurrent writes → lockForUpdate prevents duplicate, correction_count correct |
| **Writer** | cache keys invalidated on write |
| **Job** | draw not resulted → skip silently |
| **Job** | draw resulted, mirror works → archive row created |
| **Job** | failed mirror → retries 3x, draw unaffected |
| **Job** | retry after success → idempotent (same hash skip) |
| **API** | list paginates at draw_date level, not row level |
| **API** | max per_page=50 enforced |
| **API** | no raw_payload or internal fields in response |
| **API** | cache hit returns correct data |
| **API** | throttle blocks >60 req/min |
| **Fill** | external does not overwrite internal_mirror by default |
| **Reconcile** | `--fix` requires `--market`, `--from`, `--to`, `--yes` |
| **Reconcile** | internal source priority honored |

## 11. Key Decisions (ADR)

1. **result_set = always array<string>** — no objects, no nested keys, no display metadata. Simplicity > flexibility.
2. **Pagination at draw_date level** — user sees draw dates, not internal rows. Two-step query design.
3. **Idempotent writer** — same-hash retry = no-op, not correction. Safe for Horizon retries.
4. **Log survives archive** — FK ON DELETE SET NULL. Audit trail > referential convenience.
5. **External fill is secondary** — mirror internal data first, fill gaps later. Internal always wins by default.
6. **Reconcile is guarded** — cannot run blind `--fix` across all markets. Requires explicit scope and confirmation.
7. **No is_correction column** — derived from `correction_count > 0`. Avoids data drift.
8. **Separate draw_key from bet_type** — public API stability even if internal naming changes.
