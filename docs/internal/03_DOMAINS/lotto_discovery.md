# Lotto Discovery Map

> **Last Verified:** 2026-05-03 | **Source:** manual | **Confidence:** medium | **Stability:** volatile
> ไฟล์นี้เป็น derived snapshot — อาจ drift ได้ถ้า code เปลี่ยนโดยไม่อัปเดต
> **ให้ verify entrypoint ด้วย `rg` เสมอก่อนตัดสินใจ**

แผนที่สำหรับค้น code path — ใช้คู่กับ `lotto.md` และ `docs/internal/00_RULES/code_discovery_protocol.md`

---

## Betting (แทงหวย)

- Entrypoint: `POST /api/v1/lotto/bet`
- Controller: `packages/Gametech/Lotto/src/Http/Controllers/Api/BetController.php`
- Service: `BetService`
- Tables read: `lotto_draws`, `lotto_draw_bet_settings`, `lotto_market_bet_settings`, `member_lotto_market_policies`
- Tables write: `lotto_tickets`, `lotto_ticket_items`, `wallet_transactions`
- Tests: `tests/Feature/Lotto/`, `tests/Unit/Lotto/`
- Search keywords: `BetService`, `lotto_tickets`, `lotto/bet`, `member_lotto_market_policies`

## Draw Lifecycle

- States: `draft → open → closed → resulted`
- Admin entrypoint: `packages/Gametech/Lotto/src/Http/Controllers/Admin/LottoDrawController.php`
- Service: `DrawService`
- Tables: `lotto_draws`, `lotto_draw_bet_settings`
- Search keywords: `DrawService`, `lotto_draws`, `draw_status`, `lotto:generate-auto-draws`

## Auto Result / Settlement

- Entrypoint: Artisan commands + scheduler
- Services: `ResultApplier`, `SettlementService`, `AutoResult` — verify with `rg`
- Tables: `lotto_result_sources`, `lotto_result_fetch_logs`, `lotto_draws`
- Side effects: `wallet_transactions` (refund/credit), broadcast events
- Tests: `tests/Unit/Lotto/AutoResultV2/`
- Search keywords: `ResultApplier`, `SettlementService`, `result_number`, `lotto_result_sources`, `result_fetch_status`

## Member Market Policy

- Service: `MemberMarketPolicyService`
- Command: `BootstrapMemberMarketPoliciesCommand`
- Table: `member_lotto_market_policies`
- Fields: `rollout_mode`, `policy_version`, `is_allowed`
- Search keywords: `MemberMarketPolicyService`, `member_lotto_market_policies`, `rollout_mode`, `applyMarketRollout`, `applyGroupRollout`

## Yeekee

- Package: `packages/Gametech/Lotto/src/` — verify with `rg "Yeekee" packages/Gametech/Lotto/`
- Search keywords: `yeekee`, `yiki`, `YeekeeService`, `yeekee-shooting`

## Dashboard / Risk

- Tables: `lotto_dashboard_summary_daily`, `lotto_dashboard_risk_snapshot`, `lotto_dashboard_risk_aggregates`
- Services: `DashboardSummaryProjector`, `DashboardSummarySyncService` — verify with `rg`
- Search keywords: `DashboardSummaryProjector`, `lotto_dashboard`, `risk_snapshot`

## Lotto Navbar Config

- Endpoint: `GET /api/v1/lotto/navbar-config`
- Tables: `lotto_navbars`, `lotto_navbar_items`
- Search keywords: `LottoNavbarController`, `navbar-config`, `lotto_navbars`
