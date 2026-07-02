# Fix Plan: C2 — Cache::has()/Cache::put() Race Condition

**Severity**: 🔴🔴 CRITICAL
**Found by**: Security Perspective
**Files**:
- `packages/Gametech/Wallet/src/Http/Controllers/TransferGameController.php:270-276`
- `packages/Gametech/Wallet/src/Http/Controllers/TransferWalletController.php:232-237`
- `packages/Gametech/Wallet/src/Http/Controllers/WithdrawController.php:418-422`

---

## Problem

Transfer operations use a non-atomic "lock" pattern:

```php
// TransferGameController.php:270-276
if (Cache::has('transfer_'.$user_id)) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่']);
}
Cache::put('transfer_'.$user_id, 'lock', now()->addSeconds(30));
```

**Race condition**: Two concurrent requests from the same user:
1. Request A: `Cache::has('transfer_123')` → false → proceeds
2. Request B: `Cache::has('transfer_123')` → false → proceeds (before A does `put()`)
3. Request A: `Cache::put('transfer_123', 'lock', 30)` → lock acquired
4. Request B: `Cache::put('transfer_123', 'lock', 30)` → overwrites lock
5. **Both requests execute the transfer** → potential double-spend

Additionally, `WithdrawController::store_api` (line 418-422) has `Cache::lock()` **commented out** entirely, providing zero concurrency protection.

## Root Cause

Misunderstanding of Redis atomicity:
- `Cache::has()` + `Cache::put()` = **two separate Redis commands** = race window between them
- `Cache::lock()` = **single atomic Redis SET NX command** = no race window

## Fix: Replace with Cache::lock()

### Pattern (applies to all 3 files)

```php
// Before (RACE CONDITION)
if (Cache::has('transfer_'.$user_id)) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่']);
}
Cache::put('transfer_'.$user_id, 'lock', now()->addSeconds(30));

try {
    // ... transfer logic ...
} finally {
    Cache::forget('transfer_'.$user_id);
}
```

```php
// After (ATOMIC)
$lock = Cache::lock('transfer_'.$user_id, 30);

if (!$lock->get()) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่']);
}

try {
    // ... transfer logic ...
} finally {
    $lock->release();
}
```

### Specific changes per file

#### 1. TransferGameController.php (~line 270)
```php
// Replace lines 270-276
$lock = Cache::lock('transfer_'.$user_id, 30);
if (!$lock->get()) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่, มีรายการทำอยู่']);
}
// ... rest of confirm() method unchanged ...
// Add in finally block or after completion:
$lock->release();
```

#### 2. TransferWalletController.php (~line 232)
Same pattern as above:
```php
$lock = Cache::lock('wallet_transfer_'.$user_id, 30);
if (!$lock->get()) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่, มีรายการทำอยู่']);
}
// ... rest of confirm() ...
$lock->release();
```

#### 3. WithdrawController.php (~line 418)
**Uncomment and fix** the existing `Cache::lock()` call:
```php
// Before (commented out)
// $lock = Cache::lock($this->id().':transfer:game', 30);
// if (!$lock->get()) { ... }

// After
$lock = Cache::lock('withdraw_'.$this->id(), 30);
if (!$lock->get()) {
    return response()->json(['success' => false, 'message' => 'กรุณารอสักครู่, มีรายการทำอยู่']);
}
// ... rest of store_api() ...
$lock->release();
```

## Additional Safety: Lock Timeout Tuning

The 30-second timeout should match the expected maximum transaction duration:
- Transfer operations typically complete in <2 seconds
- 30 seconds provides generous headroom for DB contention
- If lock is held for >30 seconds, something is wrong → lock auto-expires (fail-open is safer than permanent deadlock)

## Affected Files
1. `TransferGameController.php` — replace `has/put` with `lock` (~5 lines)
2. `TransferWalletController.php` — same (~5 lines)
3. `WithdrawController.php` — uncomment + fix `Cache::lock` (~5 lines)

## Estimated Effort
**3-5 days** including testing concurrent transfer scenarios

## Testing Strategy
```php
// Integration test: concurrent transfers
public function test_concurrent_transfers_are_serialized(): void
{
    $user = Member::factory()->create(['balance' => 1000]);
    
    // Simulate 10 concurrent transfer requests
    $responses = [];
    for ($i = 0; $i < 10; $i++) {
        $responses[] = Http::async()->post('/transfer/game/confirm', [
            'amount' => 100,
        ]);
    }
    
    // Only 1 should succeed (others get "กรุณารอสักครู่")
    $successes = count(array_filter($responses, fn($r) => $r->json('success')));
    $this->assertEquals(1, $successes);
    
    // Balance should reflect exactly 1 transfer
    $this->assertEquals(900, $user->fresh()->balance);
}
```

## Risks
- Lock key naming must be unique per operation type (transfer_game vs transfer_wallet vs withdraw)
- Lock timeout too short → legitimate slow DB calls fail; too long → user sees "please wait" too long
- Must ensure `$lock->release()` is called in ALL code paths (including exceptions) — use `finally` block
