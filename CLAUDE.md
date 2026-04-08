# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Documentation — Source of Truth

`/docs` is the authoritative source of truth. **Never change logic without updating docs.**

Before starting any task, read the core startup set in order:

1. `docs/internal/00_RULES/agent_rules.md`
2. `docs/internal/01_SYSTEM/startup_digest.md`
3. `docs/internal/02_DECISIONS/adr_baseline.md`
4. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. `docs/04_PLANS/README.md`

Then read domain-specific docs on-demand from `docs/internal/03_DOMAINS/`:
- `frontend_api.md` for FrontendApi / BFF work
- `wallet.md` for financial/ledger/wallet work
- `lotto.md` for lotto policy, draw, cancel, result
- `admin_lotto.md` for admin reports, badge, loadCnt
- `realtime.md` for WebSocket / broadcasting

Open `system_current_state.md` or `decision_log.md` only when the task changes real behavior, touches high-risk flows (financial, auth, settlement, refund), involves state machines / queues / schema rollout, or when code and docs appear mismatched.

If code does not match docs → report the mismatch before making changes.

## Commands

```bash
# Install
composer install && npm install

# Development (server + horizon + queue + frontend)
composer run dev

# Frontend only
npm run dev          # build with watch
npm run production   # production build

# Test environment (server + horizon + reverb + queue)
composer run test

# Unit tests
./vendor/bin/phpunit tests/Unit

# Feature tests
./vendor/bin/phpunit tests/Feature

# Single test / suite
./vendor/bin/phpunit tests/Unit/Lotto/AutoResultV2 --testsuite=Unit

# Code style (Laravel Pint)
./vendor/bin/pint

# Migrations
php artisan migrate
php artisan migrate:rollback
php artisan migrate:fresh --seed

# Docs validation (CI)
bash scripts/docs-validation/run.sh
```

## Architecture

**Stack:** Laravel 10.49 (PHP 8.2+), Vue.js 2.6 SPA, MySQL + MongoDB, Redis, Laravel Reverb (WebSocket), Laravel Horizon (queues), Webpack via Laravel Mix.

**Package-based modular structure** — all business logic lives in `packages/Gametech/`:

| Package | Purpose |
|---|---|
| `FrontendApi` | BFF (Backend for Frontend) — owns all customer-facing API contracts |
| `Wallet` | Financial hub, balance management, transaction ledger |
| `Lotto` | Lottery draw lifecycle, ticket processing, result engine |
| `Admin` | Admin panel features, dashboard, reporting |
| `Payment` | Payment gateway integrations (Pompay, HengPay, LuckyPay, PapayaPay, EzPay, CommsPay, Binance) |
| `Member` | User/member management |
| `LogAdmin` / `LogUser` | Audit logging |
| `Core` | Shared utilities, traits, helpers |

## Critical Architecture Boundaries (ADRs)

**ADR-001 — FrontendApi cannot call other packages' controllers directly.** Use domain services, repositories, queries, and models instead. Some code duplication in FrontendApi is acceptable to reduce coupling.

**ADR-002 — Admin and customer realtime channels are strictly separated.**
- Admin/team: `{APP_NAME}_events`
- All customers: `{APP_NAME}_members`
- Individual customer: `{APP_NAME}_members.{member_code}`
- FrontendApi must not expose admin events to the customer frontend.

**ADR-003 — `wallet_transactions` is the financial source of truth.** Every balance change must produce a transaction row. Never change `members.balance` without a corresponding `wallet_transactions` record. Include `ref_type`, `ref_id`, `group_code`, `meta` context.

**ADR-004 — Lotto draw lifecycle is a fixed state machine:** `draft → open → closed → resulted`. `no_result` and `refunded` are result contexts, not new states. UI buttons and endpoint validation must match this state.

**ADR-005 — Ticket cancellation must preserve audit context:** `cancelled_at`, `cancelled_by`, `refund_amount`, `reason`. Use `wallet_transactions(ref_type=LOTTO_CANCEL)` as primary source.

**ADR-006 — Admin `/lotto/tickets` shows active tickets only.** Badges and `loadCnt` count active tickets only.

**ADR-009 — Schema rollout requires fallback compatibility.** When adding columns needed by live flows, provide temporary fallbacks; remove them after rollout stabilizes.

**ADR-011 — `DashboardController@loadCnt` is the single source for Lotto admin menu badge counts.** Every new Lotto admin page must wire `loadCnt` on entry.

**ADR-013 — Wallet ledger is append-only.** No flow may update `members.balance` without leaving a transaction row. Consult `2026-03-21_wallet-ledger-implementation.md` plan for ongoing ledger evolution work.

## Development Workflow

- Docs language: Thai (primary); use English only for field names, method/function names, and technical keywords.
- When changing behavior that affects lifecycle / validation / ACL / route / cron / schema, update `system_current_state.md`, `decision_log.md`, and the relevant domain/plan docs.
- If you see related issues while fixing something, ask before fixing them — don't wait to be told.
- Prefer minimal, focused responses. Avoid unnecessary explanation.
- Use repository pattern for data access.
- `docs/internal` is private — never move its contents to `docs/public`.
