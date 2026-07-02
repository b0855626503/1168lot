# Business Rules — 2026-06-24

> **Perspective**: 📋 Business — domain rules, validation logic, workflows, state machines

---

## 1. Lotto Bet Lifecycle

### State Machine: `draft → open → closed → resulted`
- **draft → open**: `DrawService::openDraw()` — snapshots market bet settings to `lotto_draw_bet_settings` on first open
- **open → closed**: `DrawService::closeDraw()` — manual close requires `lotto.allow_manual_close_without_close_at` config
- **closed → resulted**: `SettlementService::settleDraw()` — idempotent via `idempotency_key = "settlement:{draw_id}:{result_hash}"`
- **open → resulted**: Edge case handled by SettlementService
- Force-open requires `bouncer()` permission: `lotto_settings.draws.force_open`
- Re-opening `resulted` draws: ❌ NOT ALLOWED
- **File**: `DrawService.php:17-237`, `LottoDraw.php:13-16`

### Ticket Lifecycle: `active → resulted | cancelled`
- **Creation** (`BetService::placeBet`): Validation + wallet debit + exposure increment in single `DB::transaction()`
- **Self-cancel** (`LottoController::cancel`): Only while draw is `open`, ≥10 min before `close_at`, max 4 per day
- **Admin mass cancel** (`DrawCancelAllRefundService::cancelAllActiveTickets`): All active tickets in a draw — "no result" scenario
- **Result** (`SettlementService::settleDraw`): Per-ticket, per-item evaluation with lockForUpdate()
- **Files**: `BetService.php:52-136`, `LottoController.php:811-900`, `DrawCancelAllRefundService.php:19-111`, `SettlementService.php:33-228`

---

## 2. Fixed Bet Types (6 Types, Immutable)

```
TOP_3     = 'top_3'     → Exact 3-digit match with prize
TOD_3     = 'tod_3'     → All permutations of 3 digits (sorted comparison)
TOP_2     = 'top_2'     → Exact 2-digit match
BOTTOM_2  = 'bottom_2'  → Last 2 digits of prize
RUN_TOP   = 'run_top'   → 1 digit appears anywhere in top_3
RUN_BOTTOM = 'run_bottom' → 1 digit appears anywhere in bottom_2
```
- Defined as static constants in `BetType.php` — "ห้ามแก้ได้แค่เพิ่ม"
- Each has specific matching rule in `SettlementService::isWinningBet()` at lines 314-323
- All settings (payout, min/max, discount) are per-bet_type
- **File**: `BetType.php:1-49`

---

## 3. Settings Cascade: 3-Tier Override

1. **Market defaults** (`lotto_market_bet_settings`): Per-market, per-bet_type defaults
2. **Draw snapshot** (`lotto_draw_bet_settings`): Frozen copy from market defaults at draw-open time — **immutable after open**
3. **Package overrides** (`lotto_group_package_bet_settings`): Per-package payout/discount overrides — only applies if package belongs to draw's group, is active, and bet_type is enabled

**Key insight**: Changing market defaults does NOT affect open draws — only new draws get the updated settings. Package selection is per-user, per-group via `LottoPackageSelectionService`.

**Files**: `DrawService.php:156-172`, `LottoConfigResolver.php:1-65`, `LottoPackageResolver.php:15-42`

---

## 4. Member Market Policy: Default-Allow Blacklist

- Members can bet on ALL markets by default — no rows in `member_lotto_market_policies` = full access
- To block: insert row with `is_allowed = false` for member+market pair
- All mass rollout methods (`bootstrapForMember`, `bootstrapAllMembers`, `applyGroupRollout`) are **intentionally disabled** — log warnings, return zero
- Old allow-creation code preserved with `@deprecated` markers for rollback safety
- Migration: system moved from "default-deny with explicit allow" to "default-allow with explicit deny"
- **File**: `MemberMarketPolicyService.php:48-59`, lines 37-40, 62-66, 76-83

---

## 5. Number Blocking: Two Modes

| Mode | Scope | Propagation |
|------|-------|-------------|
| `block` | Current draw only | Single draw |
| `limit_future` | All future draws of same market | Auto-propagates via SQL `draw_date < current` comparison |

- Checked at bet time before any money moves — `BetService.php:240-246`
- ORDER BY `mode='block'` then `mode='limit_future'` → block takes priority
- Removing the parent block instantly unblocks all future draws (SQL query, not materialized)
- **File**: `BetService.php:436-497`

---

## 6. Exposure: Max-Per-Number Risk Control

- Each number's `sold_amount` tracked in `lotto_number_exposures` per `(draw_id, bet_type, number)`
- Checked against `max_per_number` from draw bet settings
- Lock pattern: `INSERT OR IGNORE` → `SELECT ... FOR UPDATE` → validate → increment
- On cancel: decrement with `max(0, ...)` guard
- Items chunked at 200 for insert performance
- **File**: `ExposureService.php:20-88`, `BetService.php:303-369`

---

## 7. Settlement: Winning Detection

### Result Normalization
- Modern format: `first_prize` (3-7 digits) + `last_2_digits`
- Legacy format: `top_3` + `bottom_2`
- `first_prize` resolves to: `top_3` (last 3 digits), `top_2` (last 2), `bottom_2` (last 2)
- `no_result` flag → all bets lose
- **File**: `SettlementService.php:234-297, 302-323`

### Idempotency
- `SettlementBatch.idempotency_key = "settlement:{draw_id}:{result_hash}"`
- Same result_hash + same draw → returns cached summary from `lotto_winnings`
- Different hash on already-resulted draw → throws `InvalidArgumentException`
- Per-winning-item dedup: `wallet_transactions` unique index `(member_id, direction, ref_type, ref_id)`
- **File**: `SettlementService.php:44-56`

### Win Amount Calculation
- Win amount = `potential_win_amount_at_time` (snapshotted at bet time) or `amount × payout_at_time`
- Settlement is NOT reversible — use `ResultCorrectionApplyService` for corrections
- If `auto_settle_on_result = true` on market → auto-result pipeline triggers settlement
- **File**: `SettlementService.php:33-228`

---

## 8. Wallet Transactions: Financial Audit Trail

- All money movements recorded in `wallet_transactions` — immutable source of truth
- Columns: `member_id`, `direction` (CREDIT/DEBIT), `amount`, `balance_before`, `balance_after`, `ref_type`, `ref_id`, `ref_code`, `group_code`, `related_txn_id`, `status`, `meta` (JSON)
- **Ref types**: `LOTTO_BET` (debit), `LOTTO_SETTLE_WIN` (credit), `LOTTO_CANCEL` (refund), `DEPOSIT`, `WITHDRAW`
- Concurrency: `lockForUpdate()` on member row → serializes all balance mutations per member
- Balance: cached on `members.balance`, verified by replaying `wallet_transactions`
- Realtime broadcast on every credit/debit + dashboard sync dispatch
- **File**: `WalletTransactionService.php:17-281`

---

## 9. Deposit Flow: Bank Account Matching

1. Webhook received from bank notification service (SCB, GSB, TrueWallet)
2. Find `bank_account` by bank code + bank type
3. Validate within 10-minute window of current time
4. Match member: `RIGHT(member.acc_no, suffix_len) = suffix` + name matching
5. Single match → `autocheck='W'` (auto-topup pending admin approval)
6. Multiple/no match → manual review
7. Idempotency: `tx_hash = md5(balance+amount+account+date)` unique
8. Balance sync to sibling accounts
- **File**: `WebhookController.php:154-359`

---

## 10. Withdraw Flow: Multi-Mode

| Mode | Config | Repository Method | Lock Strategy |
|------|--------|-------------------|---------------|
| Seamless | `seamless='Y'` | `withdrawSeamless()` | ✅ `lockForUpdate()` |
| Multi-game | `multigame_open='Y'` | `withdraw()` (legacy) | ❌ No lockForUpdate |
| Single | default | `withdrawSingle()` | ✅ `lockForUpdate()` |

- All modes: amount ≥ 1, balance ≥ amount, daily limit check, duplicate pending check
- Seamless: `wallet_withdraw_all='Y'` forces full balance withdrawal
- API mode: `config.withdraw_status='N'` blocks all withdrawals
- **File**: `WithdrawController.php:216-397`, `WithdrawRepository.php:54-953`

---

## 11. Result Correction System

- Status flow: `pending → previewed → processing → completed | partial_failed | failed`
- Preview counts affected tickets and financial impact
- Processing: voids old `lotto_winnings` → recalculates → credits new winnings
- Creates new `settlement_batch` with `mode='result_correction'`
- `lotto_winnings.voided_by_correction_id` links to the correction
- **File**: `LottoResultCorrection` model, `ResultCorrectionApplyService`
