# ADR-002: Wallet Package Abstraction Boundary

**Date**: 2026-06-24
**Status**: Proposed
**Deciders**: Deep Analysis (6 perspectives)

---

## Context

`WalletTransactionService` currently lives in the Lotto package (`packages/Gametech/Lotto/src/Services/WalletTransactionService.php`) but directly accesses the `members` and `wallet_transactions` tables via `DB::table()` — bypassing any Wallet package abstraction.

This creates hidden coupling: Lotto depends on Wallet's database schema, but no formal dependency exists at the package level. If the Wallet schema changes (e.g., column rename, table split), Lotto breaks silently.

Additionally, BillRepository (Payment package) also directly manipulates `members.balance` and `wallet_transactions` outside DB transactions in some paths.

This was identified by 2 perspectives (Technical, Security) as an architectural boundary violation.

## Decision

We will clarify the Wallet package as the **sole writer** to `members.balance` and `wallet_transactions`. All other packages (Lotto, Payment, Promotion) must go through the Wallet package's public API.

**Wallet package will expose:**
```php
interface WalletServiceInterface
{
    public function debit(int $memberId, Money $amount, string $refType, int $refId): TransactionResult;
    public function credit(int $memberId, Money $amount, string $refType, int $refId): TransactionResult;
    public function getBalance(int $memberId): Money;
    public function getTransactions(int $memberId, TransactionFilter $filter): PaginatedResult;
}
```

**WalletTransactionService will move from Lotto to Wallet package** and implement this interface. Lotto will inject `WalletServiceInterface` instead of directly using `WalletTransactionService`.

## Consequences

### Positive
- Single source of truth for balance mutations — easier to audit, test, and secure
- Wallet package can evolve its schema independently
- All balance operations guaranteed to use `lockForUpdate()` via the centralized service
- BillRepository refactoring becomes possible within Wallet's transaction boundaries

### Negative
- Cross-package dependency inversion: Lotto → Wallet (via interface), not the current direct DB access
- Performance: one extra method call indirection (negligible vs current DB::table() calls)
- Migration effort: all direct `members.balance` writes and `wallet_transactions` inserts outside Wallet must be refactored

### Neutral
- `WalletTransactionService` class name preserved; only namespace changes
- Existing callers in Lotto update their imports

## Migration Path

1. Define `WalletServiceInterface` in Wallet package
2. Move `WalletTransactionService` from Lotto to Wallet, implement interface
3. Bind interface to implementation in WalletServiceProvider
4. Update Lotto's BetService, SettlementService, etc. to inject interface
5. Refactor BillRepository to use Wallet API instead of direct DB writes
6. Add integration tests: verify Lotto can only write via Wallet API
7. Consider adding a DB-level trigger or policy as defense-in-depth (future)

## Open Questions

1. Should the Wallet package also own the `members` table, or should Member remain separate?
2. Does the Promotion package also write to `members`? If so, same refactoring needed.
3. Is a DB-level enforcement mechanism (e.g., restricted MySQL user for non-Wallet packages) feasible?
