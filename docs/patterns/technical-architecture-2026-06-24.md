# Technical Architecture — 2026-06-24

> **Perspective**: 🏗️ Technical — design patterns, conventions, module structure, dependency flow

---

## Architecture Overview

### Modular System: Konekt Concord 1.17
- **Module registry**: `config/concord.php` — 13 modules in boot-priority order
- **Model Proxy pattern**: Each model has auto-generated Proxy class — never instantiate Proxy directly
- **Two-tier providers**: `ModuleServiceProvider` (model registration) + `*ServiceProvider` (routes, views, DI)
- **Boot order**: Core → Game → Member → Payment → Promotion → LogAdmin → LogUser → API → Marketing → LineOA → Reward → Lotto → FrontendApi

### Package Dependency Map
```
Core (foundational models, config, repository base)
 ├─ Game (games, types, users)
 ├─ Member (membership, referrals, IC)
 │   └─ Payment (banks, deposits, withdrawals, bills)
 │       └─ Promotion (promotions, content)
 │            └─ Lotto (draws, markets, tickets, settlement)
 │                 └─ Wallet (customer-facing views, transfer)
 │                      └─ FrontendApi (JSON BFF)
 └─ Admin (panel, ACL, data tables) — depends on everything
```

**Leaks identified**:
1. Core depends on Game, Member, Payment (downstream packages) — `Core.php:10-18`
2. FrontendApi routes import Payment controllers directly — `FrontendApi/Routes/api.php:26-29`
3. Lotto `WalletTransactionService` writes `members` table via `DB::table()` — bypasses Wallet

---

## Key Patterns

### Repository Pattern (Prettus + Lada-Cache)
- Base: `Gametech\Core\Eloquent\Repository` extends `Prettus\Repository\Eloquent\BaseRepository`
- Auto-caching: Lada-Cache intercepts Eloquent queries, tags by table+row, auto-invalidates on write
- Cache TTL: 600s, Redis prefix: `gametech:`
- Cache disabled by default in Prettus config — Lada-Cache handles it
- 19 repositories in Core alone

### Service Layer (29 Lotto Services)
- Single-responsibility services: `BetService` (498 lines), `SettlementService`, `DrawService`, `WalletTransactionService`
- Singleton binding with explicit constructor DI
- All bet/settlement operations wrapped in `DB::transaction()`

### Event System (Dual Mechanism)
1. **String-based** (legacy): `customer.transfer.wallet.before|after|rollback` in Core EventServiceProvider
2. **Class-based** (modern): `MemberBalanceUpdated`, `LottoDrawStatusChanged`, `RealtimeMemberActivityUpdated`
   - Implement `ShouldBroadcast`/`ShouldBroadcastNow` for WebSocket
   - Registered in `app/Providers/EventServiceProvider`

### Payment Provider Plugin Architecture
- 19 controllers with identical method signatures: `deposit()`, `deposit_callback()`, `withdraw_callback()`
- **No shared interface contract** — `Payment/src/Contracts/Payment.php` is empty
- Each provider: Library (API client) + Controller + Config file
- `PaymentProviderGeneratorV3` subsystem — code generation tool for scaffolding new providers
- Auto package has 70+ per-provider job classes (`PaymentOut{Provider}`, `UpdateBalance{Provider}`)

### Admin Panel: DataTable + Transformer
- 90+ DataTable classes (`Admin/src/DataTables/`)
- 100+ Transformer classes (`Admin/src/Transformers/`) — Fractal `DataArraySerializer`
- Three-mode deployment: `admin-menu.php` (multi-game), `admin-menu-single.php`, `admin-menu-seamless.php`
- Custom `Bouncer` ACL — not Laravel Gates

---

## Infrastructure

### Queue Topology (Horizon)
| Supervisor | Queue | Workers | Tries | Timeout | Purpose |
|---|---|---|---|---|---|
| supervisor-broadcast | broadcast | 1 | 1 | 30s | WebSocket events |
| supervisor-topup | topup | 1 | 3 | 60s | Deposit processing |
| supervisor-bank | bank | 1 | 3 | 90s | Bank scanning |
| supervisor-lotto | lotto | 1 | 3 | 120s | Result fetch, settlement |
| supervisor-1 | default | 1-2 auto | 3 | 120s | General async |
| — | low | unsupervised | — | — | Low priority |

### Octane/Swoole Configuration
- Workers: 8, Task workers: 8, Max requests: 500
- Max execution time: 30s, Memory limit: 128MB
- Garbage collection: 50MB threshold
- `rememberRequestValue()` pattern: stores cached values on `$request->attributes` (NOT static props) — `AppServiceProvider.php:329-346`

### Broadcasting (Reverb + Redis)
- Reverb at `websocket.168csn.com:443` with TLS
- Scaling: Redis pub/sub (disabled — single node currently)
- Channels: private member, private admin, public events
- 16 event classes implementing `ShouldBroadcast`/`ShouldBroadcastNow`

---

## Anti-Patterns & Concerns

| # | Pattern | Risk | File(s) |
|---|---------|------|---------|
| 1 | Core god object (1239 lines, 4 downstream deps) | 🟡 | `Core/src/Core.php` |
| 2 | Direct DB from Lotto to Wallet tables | 🟡 | `WalletTransactionService.php:35-40` |
| 3 | 19 payment controllers, no interface | 🟡 | `Payment/src/Http/Controllers/` |
| 4 | Dual event systems (string + class) | 🟢 | Both EventServiceProviders |
| 5 | BFF route leak (imports Payment controllers) | 🟡 | `FrontendApi/Routes/api.php:26-29` |
| 6 | Wallet routes use FQCN strings | 🟢 | `route_member.php` |
| 7 | Config-driven table routing (`WithdrawSeamless.getTable()`) | 🟡 | `WithdrawSeamless.php:28-40` |

---

## Key Architectural Constants

| Aspect | Value |
|---|---|
| PHP | 8.2 |
| Framework | Laravel 10 |
| Modular | Konekt Concord 1.17 |
| Repository | Prettus Repository + Lada-Cache |
| Queue | Redis + Horizon, 6 queue groups |
| WebSocket | Reverb (self-hosted) |
| Runtime | Octane/Swoole (8 workers) |
| Serialization | Fractal (DataArraySerializer) |
| Grid | Yajra DataTables + 100+ Transformers |
| Payments | 19 providers, callback-based |
| Cache prefix | `gametech:` |
