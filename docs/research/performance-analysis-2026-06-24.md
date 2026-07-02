# Performance Analysis — 2026-06-24

> **Perspective**: ⚡ Performance — hot paths, caching, query patterns, bottlenecks

---

## Critical Hot Paths

### 1. SettlementService: N+1 Inside N+1 (CRITICAL)
- **File**: `SettlementService.php:89-167, 412-465`
- **Complexity**: 10,000 tickets → ~21,500 individual DB statements in one transaction
- **Details**: Per-item UPDATE + per-win upsert + per-ticket UPDATE + per-winner wallet check
- **Impact**: 50k-ticket draw may exceed 120s Horizon timeout → settlement failure
- **Fix**: Chunked batch UPDATEs, bulk INSERT IGNORE for winnings, single wallet preload query

### 2. DashboardSummaryProjector: 3x SUM on wallet_transactions (HIGH)
- **File**: `DashboardSummaryProjector.php:523-567`
- **Complexity**: Three separate aggregate queries on same date range — 3× full scan
- **Fix**: Single query with CASE WHEN aggregation:
  ```sql
  SELECT SUM(CASE WHEN direction='DEBIT' AND ref_type IN (...) THEN amount ELSE 0 END) as sales,
         SUM(CASE WHEN direction='CREDIT' AND ref_type IN (...) THEN amount ELSE 0 END) as payout
  ```

### 3. LottoController.tickets: Full Load Without Index (HIGH)
- **File**: `LottoController.php:437-463, 1692-1704`
- **Complexity**: Loads ALL member tickets without LIMIT, then filters in-memory
- **Impact**: Power user with 5k+ tickets → OOM risk
- **Fix**: Database-level LIMIT + proper index on `(member_id, status, created_at)`

---

## High-Impact Performance Issues

| # | Finding | File | Complexity | Fix |
|---|---------|------|------------|-----|
| 4 | Extra JOIN per broadcast during settlement | `WalletTransactionService.php:379-408` | O(n) per winner | Pass draw_id+market_name in $meta |
| 5 | marketsLatestByGroup: 6 serial queries | `LottoController.php:48-214` | 400ms+ baseline | Coalesce into 2-3 queries |
| 6 | Dynamic OR-group query plan | `BetService.php:462-484` | Per-item subquery overhead | Use WHERE IN or JSON containment |
| 7 | N+1 exposure updates on cancel | `LottoController.php:840-854` | 20 items = 20 SELECT FOR UPDATE | Batch lock + batch update |
| 8 | Double query pass for transactions | `WalletController.php:186-222` | 2× full scan | Single query with window functions |
| 9 | Unbounded risk snapshot cross-join | `DashboardSummaryProjector.php:738-764` | Tens of thousands of rows | Add date bound + LIMIT |
| 10 | Queue flood on settlement | `WalletTransactionService.php:257-268` | 1 job per winning transaction | Coalesce into batched dashboard sync |

---

## Cache Architecture

### Current State
- **Lada-Cache**: Auto-invalidating query cache, 600s TTL, 30+ model allowlist — `config/lada-cache.php`
- **Manual caching**: `Cache::remember()` with raw TTL, NO lock-based refresh
- **Result archive**: 120s TTL — `LottoResultArchiveController.php:83`
- **Market content**: `LottoMarketContentService.php:168`
- **Yeekee codes**: 3600s TTL — `LegacyArchiveResultService.php:334`

### Cache Stampede Risk
- Result archive endpoints: 120s TTL + 60 req/min throttle → moderate risk
- No `Cache::lock()` wrapper on any `Cache::remember()` call
- **Fix**: Use `Cache::lock()` with `block()` for hot keys:
  ```php
  $lock = Cache::lock($cacheKey.':lock', 10);
  if ($lock->get()) { /* recompute */ $lock->release(); }
  ```

---

## Database Performance

### Missing Composite Indexes
| Table | Missing Index | Query Pattern |
|-------|--------------|---------------|
| wallet_transactions | `(scope, status, created_at)` | Dashboard aggregation |
| wallet_transactions | `(created_at)` alone | Time-range scans |
| lotto_ticket_items | `(bet_type, number, result_status)` | Settlement lookup |
| bank_payment | `(tx_hash, status, enable)` | Pending deposit matching |
| members_credit_log | `(member_code)` | Per-member credit history |

### wallet_transactions Index Analysis
- 18 total indexes — high write overhead
- UNIQUE `(member_id, direction, ref_type, ref_id)` — essential idempotency guard
- Composite `(member_id, scope, created_at)` — good for member timeline
- Missing: standalone `created_at` for dashboard time-range scans

---

## Octane State Safety

### Static Property Risk: BetService
- **File**: `BetService.php:37,399-406`
- **Issue**: `private static ?bool $hasBetConfirmedAtColumn = null` persists across requests
- **Impact**: If `bet_confirmed_at` column added via migration while Octane running → stale `false` for up to 500 requests
- **Fix**: Check per-request or use `rememberRequestValue()` pattern:
  ```php
  private function supportsBetConfirmedAtColumn(): bool {
      return $this->request->attributes->remember('_bet_supports_confirmed_at', fn() =>
          Schema::hasColumn('lotto_tickets', 'bet_confirmed_at')
      );
  }
  ```

---

## Payment Gateway Performance

### Uniform 30s Timeout, No Circuit Breaker
- All providers: ~30s timeout
- Only APay has `retry(2, 250ms)`
- OnPay has commented reference to 3x retry with backoff
- 8 Octane workers × 30s blocked = site effectively down for new requests
- **Fix**: Add circuit breaker (e.g., 3 failures in 60s → open circuit for 30s)

---

## Dashboard Query Optimization Priority

1. `lottoCashMetrics`: Merge 3 SUM queries → 1 CASE WHEN (3× improvement)
2. `lottoRiskSnapshotMetrics`: Add date bound + LIMIT 1000
3. `lottoBetTypeInsightMetrics`: Chunk processing, add LIMIT
4. `SyncDashboardSummaryBucket`: Batch dispatch instead of per-transaction

---

## Settlement Optimization Strategy

### Current: ~21,500 statements for 10k tickets
### Target: ~50 statements for same load

1. **Batch item updates**: Collect → chunk(500) → `UPDATE ... SET result_status = CASE WHEN id IN (...) THEN ... END`
2. **Bulk winning insert**: `INSERT INTO lotto_winnings (...) VALUES (...), (...), ... ON DUPLICATE KEY UPDATE ...` — chunk 500
3. **Bulk ticket updates**: Same CASE WHEN pattern
4. **Preload wallet status**: Single `WHERE ref_type='LOTTO_SETTLE_WIN' AND ref_id IN (winning_ticket_ids)` before loop
5. **Pass context in meta**: `draw_id` + `market_name` in `$meta` → no JOIN in broadcast
