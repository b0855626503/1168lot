# Fix Plan: C3 — SettlementService N+1 Performance

**Severity**: 🔴🔴 CRITICAL
**Found by**: Performance Perspective
**File**: `packages/Gametech/Lotto/src/Services/SettlementService.php:89-167, 412-465`

---

## Problem

`settleDraw()` executes individual DB statements inside nested loops within a single transaction:

```php
// Current (simplified)
foreach ($tickets as $ticket) {                    // 10,000 iterations
    foreach ($ticket->items as $item) {            // avg 1-2 items each
        // 1. UPDATE lotto_ticket_items SET result_status=..., win_amount=... WHERE id=?
        $item->update(...);                        // ~10,000 statements
        
        if ($isWinning) {
            // 2. INSERT INTO lotto_winnings (...) VALUES (...) ON DUPLICATE KEY ...
            LottoWinning::query()->updateOrCreate(...);  // ~500 statements
            
            // 3. SELECT EXISTS(SELECT 1 FROM wallet_transactions WHERE ...)
            DB::table('wallet_transactions')->where(...)->exists();  // ~500 statements
            
            // 4. SELECT created_at FROM wallet_transactions WHERE ...
            DB::table('wallet_transactions')->...->value('created_at');  // ~500 statements
        }
    }
    // 5. UPDATE lotto_tickets SET status='resulted', total_win_amount=... WHERE id=?
    $ticket->update(...);                          // ~10,000 statements
}
// Total: ~21,500 individual SQL statements for 10,000 tickets
```

## Root Cause

Direct row-by-row approach without considering batch size. No use of MySQL's bulk update capabilities (`CASE WHEN`, `INSERT ... VALUES (...), (...)`).

## Fix: Batch Operations

### Step 1: Batch item status updates

```php
// Before: per-item UPDATE
foreach ($tickets as $ticket) {
    foreach ($ticket->items as $item) {
        $item->update(['result_status' => $status, 'win_amount' => $amount]);
    }
}

// After: collect → chunk → batch UPDATE
$itemUpdates = [];
foreach ($tickets as $ticket) {
    foreach ($ticket->items as $item) {
        $itemUpdates[] = ['id' => $item->id, 'result_status' => $status, 'win_amount' => $amount];
    }
}

// Chunk into 500 rows per batch
foreach (array_chunk($itemUpdates, 500) as $chunk) {
    $ids = array_column($chunk, 'id');
    $cases = [
        'result_status' => [],
        'win_amount' => [],
    ];
    foreach ($chunk as $row) {
        $cases['result_status'][] = "WHEN {$row['id']} THEN '{$row['result_status']}'";
        $cases['win_amount'][] = "WHEN {$row['id']} THEN {$row['win_amount']}";
    }
    
    DB::update("
        UPDATE lotto_ticket_items 
        SET result_status = CASE id " . implode(' ', $cases['result_status']) . " END,
            win_amount = CASE id " . implode(' ', $cases['win_amount']) . " END
        WHERE id IN (" . implode(',', $ids) . ")
    ");
}
```

**Result**: ~10,000 statements → ~20 statements (20 chunks × 500 rows)

### Step 2: Bulk winning insert

```php
// Before: per-win updateOrCreate
LottoWinning::query()->updateOrCreate(
    ['draw_id' => $drawId, 'bet_item_id' => $itemId],
    ['payout' => $amount, ...]
);

// After: collect → batch INSERT ON DUPLICATE KEY UPDATE
$winningRows = []; // collect all winning items
foreach (array_chunk($winningRows, 500) as $chunk) {
    $values = [];
    $params = [];
    foreach ($chunk as $i => $row) {
        $values[] = "(?, ?, ?, ?, ?, ?, ?)";
        $params = array_merge($params, [
            $row['draw_id'], $row['bet_item_id'], $row['bet_id'],
            $row['payout'], $row['status'], now(), now()
        ]);
    }
    
    DB::insert("
        INSERT INTO lotto_winnings 
            (draw_id, bet_item_id, bet_id, payout, status, settled_at, created_at, updated_at)
        VALUES " . implode(', ', $values) . "
        ON DUPLICATE KEY UPDATE 
            payout = VALUES(payout), 
            settled_at = VALUES(settled_at),
            updated_at = VALUES(updated_at)
    ", $params);
}
```

**Result**: ~500 upserts → ~1-2 batch INSERT statements

### Step 3: Preload wallet status

```php
// Before: per-winner existence check
DB::table('wallet_transactions')
    ->where('member_id', $memberId)
    ->where('ref_type', 'LOTTO_SETTLE_WIN')
    ->where('ref_id', $ticketId)
    ->exists();  // ~500 separate queries

// After: single preload query BEFORE the loop
$winningTicketIds = $allWinningItems->pluck('ticket_id')->unique();
$alreadyCredited = DB::table('wallet_transactions')
    ->whereIn('ref_id', $winningTicketIds)
    ->where('ref_type', 'LOTTO_SETTLE_WIN')
    ->where('direction', 'CREDIT')
    ->pluck('ref_id')
    ->toArray();

// Then in loop: check in-memory
if (!in_array($ticketId, $alreadyCredited)) {
    // credit the winner
}
```

**Result**: ~500 EXISTS queries → 1 SELECT query

### Step 4: Batch ticket status updates

Same CASE WHEN pattern as Step 1, applied to `lotto_tickets`.

**Result**: ~10,000 individual UPDATEs → ~20 batch UPDATEs

### Step 5: Pass context in meta (avoid broadcast JOIN)

```php
// Before: WalletTransactionService queries DB to resolve market name
$row = DB::table('lotto_draws as draws')
    ->leftJoin('lotto_markets as markets', ...)
    ->where('draws.id', $drawId)
    ->first(['markets.name as market_name', 'draws.draw_date']);

// After: pass in meta from SettlementService (already known)
$meta = [
    'draw_id' => $draw->id,
    'market_name' => $draw->market->name,
    'draw_date' => $draw->draw_date,
];
// WalletTransactionService reads from $meta instead of querying
```

**Result**: ~1,000 JOIN queries → 0 queries (data already in memory)

## Summary: Before vs After

| Operation | Before (10k tickets) | After (10k tickets) | Reduction |
|-----------|---------------------|---------------------|-----------|
| Item status UPDATEs | ~10,000 | ~20 | 99.8% |
| Winning upserts | ~500 | ~1-2 | 99.6% |
| Wallet existence checks | ~500 | 1 | 99.8% |
| Wallet credit_at queries | ~500 | 0 (merged) | 100% |
| Ticket status UPDATEs | ~10,000 | ~20 | 99.8% |
| Broadcast JOINs | ~1,000 | 0 | 100% |
| **Total** | **~21,500** | **~45** | **99.8%** |

## Estimated Effort
**3-5 days** including comprehensive testing of all bet types + correction scenarios

## Affected Files
1. `packages/Gametech/Lotto/src/Services/SettlementService.php` — primary changes (~100 lines refactored)
2. `packages/Gametech/Lotto/src/Services/WalletTransactionService.php` — meta-based context passing (~20 lines)

## Risks
- CASE WHEN batch updates must handle NULL and empty arrays correctly
- `ON DUPLICATE KEY UPDATE` assumes existing unique index `(draw_id, bet_item_id)` — verify this index exists before deploying
- Settlement correctness is CRITICAL — must test every bet type + every winning scenario + corrections before deploying
- Chunk size (500) must be tuned — too large = DB memory pressure, too small = more round trips

## Testing Strategy
1. Unit test: batch update builder produces correct SQL for 0, 1, 500, 501 items
2. Feature test: settle 5,000-ticket draw with mixed winners/losers → verify identical results to old code
3. Feature test: settle draw that's already been settled (idempotency) → verify no double-credit
4. Feature test: result correction after batch settlement → verify correct reversal
5. Performance test: time settlement of 10,000 tickets → verify <30 seconds (was potentially 60+ seconds)
