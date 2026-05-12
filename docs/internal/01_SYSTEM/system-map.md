# System Map

แผนที่เร็วสำหรับ agent — ค้นหา domain / package / entrypoint / table / test ก่อนเริ่ม code discovery

## Purpose

**Role: entrypoint index เท่านั้น**
- ห้ามใส่ logic detail หรือ business rule ในไฟล์นี้
- entries ที่นี่ = ชี้ทางค้นต่อ ไม่ใช่ source of truth
- source of truth = code เสมอ
- ถ้าไม่แน่ใจ entrypoint จริง ให้ verify ด้วย `rg "<ClassName|TableName>" packages/ app/`

**Sync contract:**
เมื่อ route / service / table เปลี่ยน ต้องอัปเดต:
- ไฟล์นี้ ถ้า entrypoint package/path เปลี่ยน
- `*_discovery.md` ที่เกี่ยวข้อง ถ้า flow เปลี่ยน

---

## Domains

### Lotto
- Package: `packages/Gametech/Lotto/src/`
- Admin controllers: `packages/Gametech/Lotto/src/Http/Controllers/Admin/`
  - `MemberLottoPermissionController` (member-permissions CRUD + delete)
- API controllers: `packages/Gametech/Lotto/src/Http/Controllers/Api/`
  - `BetController`, `DrawController`, `TicketController`, `PackageController`
  - `CentralLotteryResultController`, `InternalResultController`
- Routes: `packages/Gametech/Lotto/src/Routes/admin.php`, `api.php`
- Key services: `BetService`, `DrawService`, `SettlementService`, `ResultApplier`, `MemberMarketPolicyService`, `ArchiveNormalizerService`, `ArchiveWriterService`
- Key tables: `lotto_draws`, `lotto_draw_bet_settings`, `lotto_market_bet_settings`, `lotto_tickets`, `lotto_ticket_items`, `member_lotto_market_policies`, `lotto_result_archives`, `lotto_result_archive_logs`
- Tests: `tests/Feature/Lotto/`, `tests/Unit/Lotto/`
- Domain docs: `docs/internal/03_DOMAINS/lotto.md`, `docs/internal/03_DOMAINS/lotto-discovery.md`

### Wallet
- Package: `packages/Gametech/Wallet/src/`
- Routes: `packages/Gametech/Wallet/src/Http/Routes/`
- Financial source of truth: `wallet_transactions`
- Frontend entry: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- Tests: search `tests/Feature/` and `tests/Unit/` with `rg "wallet"`
- Domain docs: `docs/internal/03_DOMAINS/wallet.md`, `docs/internal/03_DOMAINS/wallet-discovery.md`

### Payment
- Package: `packages/Gametech/Payment/src/`
- Controllers: `packages/Gametech/Payment/src/Http/Controllers/`
  - PayoneX, DeepPay, MaxPay, WellPay, OnPay, KingPay, Xpay, WildPay + others
- Routes: `packages/Gametech/Payment/src/Routes/routes.php`
- Key models: `Payment`, `PaymentLog`, `Withdraw`, `BankAccount`
- Key tables: `bank_payment`, `payments_waiting`, `withdraws`
- Tests: `tests/Unit/Payment/`
- Domain docs: `docs/internal/03_DOMAINS/payment.md`

### Frontend API (BFF)
- Package: `packages/Gametech/FrontendApi/src/`
- Routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controllers: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`
  - `LottoResultArchiveController` (public result archive — index/show/item, paginated, cached)
- Middleware: `packages/Gametech/FrontendApi/src/Http/Middleware/`
- Public API docs: `docs/public/api/frontend-v1/index.md`
- Tests: `tests/Feature/FrontendApi/`, `tests/Unit/FrontendApi/`
- Domain docs: `docs/internal/03_DOMAINS/frontend-api.md`, `docs/internal/03_DOMAINS/frontend-api-discovery.md`

### Admin / ACL
- Package: `packages/Gametech/Admin/src/`
- Lotto admin controllers: `packages/Gametech/Lotto/src/Http/Controllers/Admin/`
- Tests: `tests/Feature/Admin/`
- Domain docs: `docs/internal/03_DOMAINS/admin-lotto.md`

### Dashboard
- Services: `DashboardService`, `DashboardSummaryProjector`, `DashboardSummarySyncService` — verify with `rg`
- Key tables: `lotto_dashboard_summary_daily`, `lotto_dashboard_risk_snapshot`, `lotto_dashboard_risk_aggregates`
- Domain docs: `docs/internal/03_DOMAINS/lotto.md` (dashboard section)

### Realtime / Broadcast
- Config: `routes/channels.php`
- Domain docs: `docs/internal/03_DOMAINS/realtime.md`

### Auth
- Controllers: `app/Http/Controllers/Auth/`
- Domain docs: `docs/internal/03_DOMAINS/auth.md`

---

## Routes Overview

- Main web: `routes/web.php`
- Main API: `routes/api.php`
- Broadcast: `routes/channels.php`
- Lotto admin/api: `packages/Gametech/Lotto/src/Routes/`
- Payment: `packages/Gametech/Payment/src/Routes/routes.php`
- Wallet: `packages/Gametech/Wallet/src/Http/Routes/`
- FrontendApi: `packages/Gametech/FrontendApi/src/Routes/api.php`
