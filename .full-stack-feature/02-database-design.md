# Database & Data Model Design: Lotto Public Result Archive API

> Grounded in: `packages/Gametech/Lotto/src/Database/Migrations/`, `packages/Gametech/Lotto/src/Models/`
> Existing patterns: anonymous Migration class, `$fillable` + `$casts`, Proxy models, Contracts

## 1. Entity-Relationship Design

```
lotto_markets (existing)          lotto_draws (existing)
├── code (unique)                 ├── market_id → lotto_markets.id
├── name                          ├── draw_date (date)
└── is_enabled                    ├── status (enum: draft|open|closed|resulted)
                                  ├── result_number (json)
                                  └── result_normalized_payload_json (json)

           ↓ mirror/fill ↓

lotto_result_archives (new)       lotto_result_archive_logs (new)
├── id (PK)                       ├── id (PK)
├── market_code                   ├── archive_id → lotto_result_archives.id (nullable FK, ON DELETE SET NULL)
├── draw_date (date)              ├── market_code (denormalized)
├── draw_key (string)             ├── draw_date (denormalized)
├── result_set (json)             ├── draw_key (denormalized)
├── result_hash (string)          ├── action (string: mirror|fill|correct|reconcile)
├── source_draw_id (nullable FK)  ├── run_id (string)
├── source_type (string)          ├── status (string: success|failed|skipped|corrected)
├── correction_count (int)        ├── old_result_set (json nullable)
├── previous_result_set (json)    ├── new_result_set (json nullable)
├── source_info_json (json)       ├── changed_keys (json nullable)
├── corrected_at (datetime)       ├── source_info_json (json nullable)
└── timestamps                    ├── error_message (text nullable)
                                  ├── trace_json (json nullable)
                                  └── created_at (datetime)

Unique: (market_code, draw_date, draw_key)
```

## 2. Schema Definitions

### `lotto_result_archives`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | unsignedBigInteger | PK, auto-increment | |
| `market_code` | string(50) | NOT NULL | matches `lotto_markets.code` |
| `draw_date` | date | NOT NULL | draw date. Internal mirror: use `lotto_draws.draw_date` directly. External fill: map to market calendar date, NOT server timezone. NEVER use `created_at` or fetch time. |
| `draw_key` | string(50) | NOT NULL | result key within the draw e.g. `three_up`, `two_down` |
| `result_set` | json | NOT NULL | **array<string> only** — e.g. `["123","456"]`, NEVER `{"prizes":...}` |
| `result_hash` | string(64) | NOT NULL | SHA-256 of `sorted(result_set)` joined by `\|` (see checksum rules) |
| `source_draw_id` | unsignedBigInteger | nullable | FK → `lotto_draws.id` ON DELETE SET NULL; null when external fill |
| `source_type` | string(30) | NOT NULL, default `internal_mirror` | Source of CURRENT data: `internal_mirror` (from lotto_draws) or `external_fetch` (from external source). Correction is tracked via `correction_count` and log `action`, NOT via source_type. |
| `correction_count` | unsignedInteger | NOT NULL, default 0 | increments only on real correction (different hash) |
| `previous_result_set` | json | nullable | array<string>, previous value before latest correction |
| `source_info_json` | json | nullable | provenance metadata (source URL, fetched_at, parser version) |
| `corrected_at` | datetime | nullable | when correction was applied |
| `created_at` | datetime | NOT NULL | |
| `updated_at` | datetime | NOT NULL | |

**REMOVED:** `is_correction` — derived from `correction_count > 0`, avoids data drift risk.
**RENAMED:** `correction_source_info_json` → `source_info_json` — reusable for both mirror and fill metadata.

**Indexes:**
```sql
UNIQUE INDEX `lotto_result_archives_unique` (`market_code`, `draw_date`, `draw_key`)
INDEX `lotto_result_archives_market_date` (`market_code`, `draw_date`)
INDEX `lotto_result_archives_source_draw_id` (`source_draw_id`)
INDEX `lotto_result_archives_source_type` (`source_type`)
```

### `lotto_result_archive_logs`

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| `id` | unsignedBigInteger | PK, auto-increment | |
| `archive_id` | unsignedBigInteger | nullable, FK → `lotto_result_archives.id` **ON DELETE SET NULL** | null when pre-insert attempt fails; survives archive deletion |
| `market_code` | string(50) | NOT NULL | denormalized |
| `draw_date` | date | NOT NULL | denormalized |
| `draw_key` | string(50) | NOT NULL | denormalized |
| `action` | string(30) | NOT NULL | `mirror`, `fill`, `correct`, `reconcile` |
| `run_id` | string(64) | NOT NULL | batch identifier UUID |
| `status` | string(20) | NOT NULL | `success`, `failed`, `skipped`, `corrected` |
| `old_result_set` | json | nullable | pre-change state (array<string>) |
| `new_result_set` | json | nullable | post-change / intended state (array<string>) |
| `changed_keys` | json | nullable | semantic diff for unordered result_set: `{"added":["789"],"removed":["123"]}` — uses values, NOT array indices (indices are unstable after sort) |
| `source_info_json` | json | nullable | data provenance metadata |
| `error_message` | text | nullable | failure reason |
| `trace_json` | json | nullable | debug context |
| `created_at` | datetime | NOT NULL | append-only — no `updated_at` |

`public $timestamps = false;` — managed manually (only `created_at`)

**Indexes:**
```sql
INDEX `lotto_result_archive_logs_archive_id` (`archive_id`)
INDEX `lotto_result_archive_logs_identity` (`market_code`, `draw_date`, `draw_key`)
INDEX `lotto_result_archive_logs_action` (`action`)
INDEX `lotto_result_archive_logs_run_id` (`run_id`)
INDEX `lotto_result_archive_logs_created_at` (`created_at`)
```

## 3. result_set: Strict array<string> Only

**Hard rule:** `result_set` is always `array<string>`, no exceptions.

```json
// CORRECT:
["123", "456"]

// CORRECT (single value):
["01"]

// WRONG — rejected by Normalizer:
{"prizes": ["123"], "display": "123"}
"123"
```

**Rationale:**
- Checksum: simple `sorted(result_set)` → deterministic hash
- Compare/reconcile: direct array comparison, no nested key traversal
- Public contract: consumers always get the same structure
- Leading zero: each element is `string`, preserved naturally
- Debug: flat structure = easy to log and inspect

**Display rendering:** If a display format is needed, it belongs in the API transformer layer, not in the database.

## 4. Checksum Rules

**Algorithm:** `sha256(sorted(result_set) joined by '|')`

```
computeResultHash(array<string> $resultSet): string
    $normalized = sort($resultSet);
    return hash('sha256', implode('|', $normalized));
```

Example: `result_set = ["456", "123"]` → sort → `["123", "456"]` → `sha256("123|456")`

**Order semantics rule:** All result_set data is treated as an unordered set. If a draw type requires position semantics (e.g., "first prize" vs "second prize"), these MUST be separated into distinct `draw_key` values:

- `first_prize` → `["123456"]` (the 1st prize number)
- `three_front` → `["123", "456"]` (unordered set of 3-digit front numbers)
- `two_down` → `["45"]` (the 2-digit bottom number)

This ensures `sorted()` never destroys meaningful order.

## 5. Indexing Strategy

| Query Pattern | Index Used |
|--------------|-----------|
| `WHERE market_code=? AND draw_date=? AND draw_key=?` | PK-style UNIQUE |
| `WHERE market_code=? AND draw_date=?` | `lotto_result_archives_market_date` |
| `WHERE market_code=? AND draw_date BETWEEN ? AND ?` | `lotto_result_archives_market_date` |
| `SELECT DISTINCT draw_date WHERE market_code=? AND draw_date BETWEEN ? ORDER BY draw_date DESC LIMIT ?` | `lotto_result_archives_market_date` |
| `WHERE source_draw_id=?` (mirror check which keys exist) | `lotto_result_archives_source_draw_id` |
| Log queries by archive | `lotto_result_archive_logs_archive_id` |
| Reconcile scan | `lotto_result_archive_logs_identity` |

## 6. Migration Strategy

1. **Migration file:** `packages/Gametech/Lotto/src/Database/Migrations/2026_05_12_000001_create_lotto_result_archives.php`
2. **No existing data risk** — new tables only, zero-downtime create
3. **Foreign key `source_draw_id`** → `lotto_draws.id` uses `onDelete('set null')` — archive survives draw deletion
4. **Foreign key `archive_id`** → `lotto_result_archives.id` uses **`onDelete('set null')`** — audit log survives archive deletion. This is intentional: logs are append-only audit records that must outlive their parent row.

## 7. Query Patterns

### Write — Idempotent Mirror (Writer Service)
```sql
-- Step 1: Lock existing row (if any)
SELECT * FROM lotto_result_archives
WHERE market_code = ? AND draw_date = ? AND draw_key = ?
FOR UPDATE;

-- Step 2a: No row → INSERT
INSERT INTO lotto_result_archives (...) VALUES (...);

-- Step 2b: Row exists, same hash → SKIP (no-op, no correction_count increment)
-- Step 2c: Row exists, different hash → UPDATE, increment correction_count
UPDATE lotto_result_archives
SET result_set = ?, result_hash = ?, correction_count = correction_count + 1,
    previous_result_set = ?, corrected_at = NOW(), source_info_json = ?
WHERE id = ?;

-- Step 3: Always append log (in same transaction)
INSERT INTO lotto_result_archive_logs (...) VALUES (...);
```

### Read (FrontendApi) — Grouped Pagination by draw_date

**Step 1: Paginate distinct draw_dates**
```sql
SELECT DISTINCT draw_date FROM lotto_result_archives
WHERE market_code = ? AND draw_date BETWEEN ? AND ?
ORDER BY draw_date DESC
LIMIT ? OFFSET ?;
```

**Step 2: Fetch all archive rows for those dates**
```sql
SELECT * FROM lotto_result_archives
WHERE market_code = ? AND draw_date IN (?, ?, ...)
ORDER BY draw_date DESC, draw_key ASC;
```

**Step 3: Group in application layer** — merge draw_key rows into `results` object.

## 8. Data Access Patterns

### Models
```
Gametech\Lotto\Models\LottoResultArchive (implements LottoResultArchiveContract)
Gametech\Lotto\Models\LottoResultArchiveProxy
Gametech\Lotto\Models\LottoResultArchiveLog
```

### Repository (in Lotto package)
```
Gametech\Lotto\Repositories\ArchiveRepository
  - persistArchiveRow(...): ArchiveWriteResult   // INSERT or idempotent no-op or correction (NOT upsert)
  - findByIdentity(marketCode, drawDate, drawKey): ?LottoResultArchive

Gametech\Lotto\Contracts\ArchiveWriteResult  // Value object contract
  - status: 'created' | 'skipped' | 'corrected'
  - archive: ?LottoResultArchive
  - previousResultSet: ?array<string>
  - logId: ?int
  - findBySourceDraw(sourceDrawId): Collection  // one draw → multiple draw_keys
  - existsBySourceDrawAndDrawKey(sourceDrawId, drawKey): bool
  - findDistinctDrawDates(marketCode, dateFrom, dateTo, perPage): LengthAwarePaginator  // paginated at draw_date level
  - findByDrawDates(marketCode, array drawDates): Collection  // bulk fetch for grouping

Gametech\Lotto\Repositories\ArchiveLogRepository
  - logAction(...): LottoResultArchiveLog
  - getLogsForArchive(archiveId): Collection
  - getLogsForRun(runId): Collection
```

## 9. draw_key Convention

`draw_key` maps from `lotto_draw_bet_settings.bet_type` to a public-facing stable key:

| bet_type (internal) | draw_key (public) |
|---------------------|-------------------|
| `three_top` | `three_up` |
| `two_below` | `two_down` |
| `three_front` | `three_front` |
| `three_below` | `three_down` |
| `run_top` | `run_up` |
| `run_below` | `run_down` |

Result numbers are always strings with preserved leading zeros (e.g., `"01"`, `"007"`).

## 10. Idempotent Writer Contract

```
writeArchive(normalizedRows, sourceType, ?sourceDrawId, runId):
  FOR EACH row:
    lockForUpdate() existing row
    IF no archive:
      → INSERT, log action=mirror status=success (correction_count stays 0)
    ELSE IF result_hash == existing.result_hash:
      → SKIP, log action=mirror status=skipped (NO correction_count increment)
    ELSE (different hash):
      → UPDATE result_set, increment correction_count, save previous_result_set
      → log action=correct status=corrected
  RETURN written/skipped/corrected counts
```

**Crucial:** Same-hash retry MUST NOT increment `correction_count`. This is the idempotency contract that makes queue retries safe.

**Duplicate-key race handling (concurrent insert):** Two workers may try to insert the same `(market_code, draw_date, draw_key)` simultaneously — the second `lockForUpdate()` will wait, then see the row exists and follow the skip/correct branch. However, the INSERT path needs a guard: wrap in `try/catch` for `DuplicateEntryException` (MySQL error 1062). On duplicate key after lock acquisition, re-read with `lockForUpdate()` and fall through to the skip/correct branch. This ensures concurrent inserts never produce duplicate rows or double-count correction_count.

## 11. External Fill Safety Rules

1. `source_draw_id = null` (no internal draw reference)
2. `source_type = 'external_fetch'`
3. `source_info_json` REQUIRED and validated by Writer: must contain `{source_url, fetched_at, parser_version}` — Writer MUST reject (`throw InvalidArgumentException`) if source_type is `external_fetch` and source_info_json is missing any required field
4. MUST NOT create `lotto_draws` rows
5. **If archive row already exists with `source_type='internal_mirror'`:**
   - Default: **DO NOT overwrite** — log mismatch only
   - Override: only via `reconcile --fix` with explicit source priority
6. External fill writes to `lotto_result_archives` only, never to any other table

## 12. Anti-Pattern: Do NOT Use Plain `upsert()` for Writer

The Writer correction flow requires three distinct branches (create / skip-same-hash / correct-different-hash) with audit logging of old/new values. Laravel's `->upsert()` flattens this into a single operation and does NOT provide safe access to the pre-existing row state.

**Explicit transaction pattern is MANDATORY:**

```php
DB::transaction(function () use (...) {
    $existing = LottoResultArchive::where(...)->lockForUpdate()->first();
    
    if (!$existing) {
        // CREATE
        LottoResultArchive::create(...);
        LottoResultArchiveLog::create([...action='mirror', status='success'...]);
    } elseif ($existing->result_hash === $computedHash) {
        // SKIP — idempotent, no correction_count increment
        LottoResultArchiveLog::create([...action='mirror', status='skipped'...]);
    } else {
        // CORRECT — different hash
        $oldResultSet = $existing->result_set;
        $existing->update([
            'result_set' => $newResultSet,
            'result_hash' => $computedHash,
            'correction_count' => $existing->correction_count + 1,
            'previous_result_set' => $oldResultSet,
            'corrected_at' => now(),
        ]);
        LottoResultArchiveLog::create([...action='correct', status='corrected', old_result_set=>$oldResultSet...]);
    }
    
    // Invalidate cache
    Cache::forget("lotto:archive:{$marketCode}:{$drawDate}");
});
```

`insertOrIgnore()` is acceptable ONLY for the initial create path in a concurrent-safe manner, but the full correction flow must use explicit transaction + lockForUpdate.

## 13. Laravel-Specific Implementation Notes

- Migration uses `return new class extends Migration` (anonymous class, Laravel 10 convention)
- `$casts = ['result_set' => 'array', 'previous_result_set' => 'array', 'source_info_json' => 'array', ...]`
- `LottoServiceProvider` registers models, repository bindings, commands, and jobs
- Use `DB::transaction()` with `lockForUpdate()` for archive write + log append atomicity
- **Do NOT use plain `->upsert()`** — see §12 Anti-Pattern above; use explicit SELECT FOR UPDATE + INSERT/UPDATE branching
- Follow existing Proxy model pattern (`LottoResultArchiveProxy` extends `LottoResultArchive`)
- Log FK: `$table->foreign('archive_id')->references('id')->on('lotto_result_archives')->onDelete('set null')`
