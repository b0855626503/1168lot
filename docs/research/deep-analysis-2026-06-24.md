# Deep Analysis Report — 2026-06-24

> **Scope**: ทั้ง codebase แบบ deep — 86 findings จาก 6 perspectives
> **Analysts**: 6 specialist Explore agents (Business, Technical, Security, Performance, Integration, Data)
> **Method**: Mechanism-level analysis — ทุก finding ต้องมี What/How/Why + file:line evidence

---

## Executive Summary

วิเคราะห์ codebase 1168lot ทั้งระบบ (379+ file operations, ~871k tokens) พบ 86 findings แบ่งเป็น:

| Severity | Count | คำอธิบาย |
|----------|-------|-----------|
| 🔴🔴 CRITICAL | 3 | ต้องแก้ทันที — plaintext passwords, race conditions, settlement performance |
| 🔴 HIGH | 11 | ควรแก้ใน sprint นี้ — webhook security, Octane safety, missing indexes |
| 🟡 MEDIUM | ~30 | จัดการตาม priority — cache stampede, queue topology, inconsistent patterns |
| 🟢 LOW | ~42 | ปรับปรุงระยะยาว — naming conventions, schema debt, documentation |

---

## CRITICAL Findings

### C1. Plaintext Password Storage
- **File**: `packages/Gametech/Wallet/src/Http/Controllers/LoginController.php:326,330`
- **What**: `user_pass` field stores plaintext password alongside hashed `password`
- **How**: `LoginController@register()` — `Hash::make($pass)` for `password`, raw `$pass` for `user_pass`
- **Why**: Legacy auth compatibility — old system reads `user_pass` directly
- **Risk**: DB compromise = all passwords exposed. Catastrophic data protection failure.

### C2. Cache::has()/Cache::put() Race Condition
- **Files**: `TransferGameController.php:270-276`, `TransferWalletController.php:232-237`, `WithdrawController.php:418-422`
- **What**: Non-atomic "lock" pattern — `if (Cache::has($key)) { return error; } Cache::put($key, 'lock', 30s)`
- **How**: 2 concurrent requests both pass `has()` check before either does `put()` → both proceed
- **Why**: Misunderstanding of Redis atomicity — `has()`+`put()` is NOT atomic; `Cache::lock()` is
- **Risk**: Double-spend on transfer operations. `store_api` withdraw has `Cache::lock()` COMMENTED OUT.

### C3. SettlementService N+1 Inside N+1
- **File**: `packages/Gametech/Lotto/src/Services/SettlementService.php:89-167, 412-465`
- **What**: Per-row UPDATE/upsert/check in nested loops inside single DB transaction
- **How**: For 10,000 tickets: ~10,000 item UPDATEs + 500 upserts + 10,000 ticket UPDATEs + 500 wallet checks = ~21,500 statements
- **Why**: Direct approach without batch optimization
- **Risk**: Draw with 50k+ tickets may exceed 120s Horizon timeout → settlement failure

---

## HIGH Findings

### Payment Webhook Security
- **H1**: 12/14 providers no webhook signature verification (`docs/interfaces/payment-webhook-security.md`)
- **H2**: `verify_callback_signature` defaults to `false` in WealthPay/FlashPay configs
- **Evidence**: Only FlashPay (`FlashPay.php:177-195`) and WealthPay (`WealthPay.php:158-173`) implement HMAC verification

### Financial Transaction Safety
- **H3**: `BillRepository::transferGame()` — wallet debit outside DB transaction, `auto_wallet='N'` = silent fund loss
- **H7**: `WithdrawRepository::withdraw()` (legacy) no `lockForUpdate()` — race condition on old withdraw path

### Performance
- **H6**: Dashboard 3× SUM on `wallet_transactions` — single query with CASE WHEN would be 3× faster
- **H9**: `LottoController::tickets` loads ALL tickets before in-memory pagination
- **H10**: `DashboardSummaryProjector::lottoRiskSnapshotMetrics` — unbounded cross-join
- **H11**: Settlement dispatches 1 job per winner → queue flood

### Data
- **H8**: Missing `(scope, status, created_at)` composite index on `wallet_transactions`
- **H4**: `BetService::$hasBetConfirmedAtColumn` static property — stale under Octane (up to 500 requests)

### Architecture
- **H5**: `WalletTransactionService` (Lotto package) directly accesses `members` table — bypasses Wallet abstraction

---

## Cross-Cutting Observations

### Chain 1: Payment Webhook Attack Surface
No signature verification (12 providers) × CSRF exempt callbacks × no IP whitelisting × predictable URLs = callback forgery → fake deposits → withdrawable balance

### Chain 2: Settlement Performance Cascade
Settlement N+1 × broadcast JOIN per winner × dashboard job per winner = settlement of 1,000 winners triggers 1,000 broadcasts + 1,000 JOINs + 1,000 jobs

### Chain 3: Payment Provider Fragmentation
No shared interface × inconsistent security × varying data models × per-provider code duplication = 19 controllers with identical method signatures but different behavior

### Chain 4: Schema Evolution Debt
Dual auth (plaintext+hashed) × dual credit logs × inconsistent PK naming (`code` vs `id`) × no DB-level FKs = accumulated technical debt across eras

### Chain 5: Octane State Safety
Static property caching × 500-request worker lifecycle × migration-while-running = stale configuration for up to 500 requests after schema change

---

## Recommendations Summary

| Priority | Recommendation | Scope | Est. Effort |
|----------|---------------|-------|-------------|
| 🔴 | Fix race conditions: `Cache::has/put` → `Cache::lock` | 4 controllers + 1 repo | 3-5 days |
| 🔴 | Optimize SettlementService batch operations | 1 service | 3-5 days |
| 🔴 | Remove `user_pass` plaintext column | 3 files + migration | 1-2 days |
| 🔴 | Enable webhook signature verification | 14 controllers + middleware | 2-3 weeks |
| 🟡 | Create Payment Provider interface | 1 interface + 19 controllers | 3-4 weeks |
| 🟡 | Add missing composite indexes | 3-4 migrations | 1-2 days |
| 🟡 | Refactor `BillRepository::transferGame()` transaction boundary | 1 repository | 1-2 days |
| 🟢 | Fix Octane static property in BetService | 1 service | 1 hour |
| 🟢 | Rename `Core.php` god object → split by domain | 1 class → 4-5 services | 1-2 weeks |

---

## Full Finding Index

See individual perspective reports:
- `docs/domain/business-rules-2026-06-24.md` — 18 business findings
- `docs/patterns/technical-architecture-2026-06-24.md` — 18 technical findings
- `docs/research/security-audit-2026-06-24.md` — 10 security findings
- `docs/research/performance-analysis-2026-06-24.md` — 16 performance findings
- `docs/interfaces/integration-landscape-2026-06-24.md` — 10 integration findings
- `docs/patterns/data-model-2026-06-24.md` — 14 data findings
