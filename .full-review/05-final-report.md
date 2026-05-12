# Comprehensive Code Review Report — Lotto Public Result Archive API

## Review Target

result-archive_20260512 track: 7 commits, 27 files across 4 packages + tests + docs

## Executive Summary

The archive subsystem is well-structured with clear separation from settlement/wallet/ticket domains. The idempotent writer, external-overwrite guard, and FK SET NULL are correctly implemented. However, **two production-blocking issues** were found: (1) the mirror job is only dispatched for the no-result path, missing all auto-settled draws; (2) the external fetcher calls an undefined method, causing runtime crashes. Two N+1 query issues in the mirror and reconcile commands will degrade significantly at scale. All issues are fixable.

## Findings by Priority

### Critical Issues (P0 — Must Fix Immediately)

| # | File:Line | Issue | Fix |
|---|-----------|-------|-----|
| C1 | `ExternalResultFetcherService.php:65` | `fetchUsingSource()` undefined on `AutoResultPipelineService` — runtime `BadMethodCallException` | Implement `fetchUsingSource()` on pipeline, or route to existing method |
| C2 | `ReconcileResultArchiveCommand.php:95-96` | N+1: per-archive `LottoDraw::find()` inside loop; 1000 archives = 1000 queries | Batch `whereIn` fetch, key by ID |
| C3 | `MirrorExistingResultedDrawsCommand.php:121-138` | N+1: per-row `exists()` query in `filterOnlyMissing()`; 400+ queries per chunk | Batch `whereIn` on draw_keys |

### High Priority (P1 — Fix Before Next Release)

| # | File:Line | Issue | Fix |
|---|-----------|-------|-----|
| H1 | `ResultApplier.php:138-156` | `MirrorDrawToArchiveJob` not dispatched for settlement path — settled draws never archived | Add `DB::afterCommit()` dispatch after settlement returns |
| H2 | `ArchiveRepository.php:20-94` | Missing native return types on 6 public methods | Add `: ?LottoResultArchive`, `: Collection`, `: LengthAwarePaginator`, `: array` |
| H3 | `MirrorExistingResultedDrawsCommand.php:127` | `\DB::table()` bypasses Eloquent model and repository | Use `ArchiveRepository::existsBySourceDrawAndDrawKey()` or add bulk variant |

### Medium Priority (P2 — Plan for Next Sprint)

| # | File:Line | Issue | Fix |
|---|-----------|-------|-----|
| M1 | `LottoResultArchiveController.php:28-29` | No YYYY-MM-DD format validation on query params; `strtotime(false)` edge case | Add `preg_match('/^\d{4}-\d{2}-\d{2}$/')` check |
| M2 | `ResultApplier.php:141-149` | Double-write of `result_fetch_status`, `result_hash`, `result_{applied,fetched}_at` in settlement path (safe but redundant) | Remove already-set fields from forceFill |
| M3 | 3 call sites | Pipeline duplication: `LottoDraw→normalize→writeArchive` repeated in Job + 2 Commands | Extract `ArchiveMirrorOrchestrator` service |
| M4 | `ReconcileResultArchiveCommand.php:69` | Uses `LottoResultArchive::where()` directly instead of repository | Add `findByMarketAndDateRange()` to `ArchiveRepository` |

### Low Priority (P3 — Track in Backlog)

| # | File:Line | Issue | Fix |
|---|-----------|-------|-----|
| L1 | `ArchiveWriterService.php:73-85` | Duplicated `match` blocks for log status mapping | Single lookup map |
| L2 | `ArchiveWriterService.php:49-105` | Per-row transaction for large batches (scalability concern for future bulk commands) | Batch insert path for known-unique writes |

### Confirmed Safe (No Issues)

- Route isolation: public archive routes correctly separated from authenticated routes
- External overwrite guard: properly enforced in `persistArchiveRow()` with `allowExternalOverwrite` flag
- No settlement/wallet/ticket interaction from archive services
- No internal fields exposed in API responses (`source_draw_id`, `source_type`, etc.)
- FK SET NULL correctly implemented for audit preservation
- Schema indexes match query patterns
- Octane safety: archive services use transient resolution, not singletons

## Findings by Category

| Category | Critical | High | Medium | Low | Total |
|----------|----------|------|--------|-----|-------|
| Code Quality | 2 | 1 | 2 | 2 | 7 |
| Architecture | 0 | 1 | 1 | 0 | 2 |
| Security | 0 | 0 | 1 | 0 | 1 |
| Performance | 2 | 0 | 0 | 1 | 3 |
| Testing | 0 | 0 | 0 | 0 | 0 |
| **Total** | **4** | **2** | **4** | **3** | **13** |

## Recommended Action Plan

1. **Fix C1 (fetchUsingSource)** — small effort, blocks fill-missing command
2. **Fix H1 (settlement path dispatch)** — 1 line change, blocks all regular result archiving
3. **Fix C2 + C3 (N+1 queries)** — medium effort, prevents performance degradation at scale
4. **Fix H2 (return types)** — 6 type declarations, satisfies Laravel Boost convention
5. **Fix H3 (DB::table bypass)** — small refactor, adds method to repository
6. **Fix M1 (date validation)** — small guard addition
7. **M2, M3, M4** — medium refactors, plan for next iteration
8. **L1, L2** — nice-to-have, no urgency

---
Review date: 2026-05-12T08:25:00Z
Phases completed: Code Quality, Architecture, Security
