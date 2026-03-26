# AGENTS.md – AI Coding Agent Guide for Gametech Platform

## Quick Links
- Dev workflow (PHPStorm + WSL): `docs/DEV_WORKFLOW_PHPSTORM_WSL.md`
- Lotto implementation handover: `docs/LOTTO_SYSTEM_HANDOVER_TH.md`
- FrontendApi agent playbook: `docs/AGENT_FRONTENDAPI.md`
- Lotto system roadmap plan: `plan-lottoSystemRoadmap.prompt.md`
- Lotto execution phases plan: `plan-lottoExecutionPhases.prompt.md`
- Lotto Concord/Proxy cleanup plan: `plan-lottoConcordProxyCleanup.prompt.md`
- Admin team menu active plan: `plan-adminTeamMenuActive.prompt.md`
- Immediate query dedup + page speed plan: `plan-immediateQueryDedupPageSpeed.prompt.md`

## Overview
This is a **Laravel 8 gaming platform** with a modular architecture using **Konekt Concord** for domain-driven design. The codebase serves **wallets, games, payments, admin panels, and APIs** across subdomains. AI agents should understand module boundaries, context detection, and integration points before making changes.

---

## 1. Architecture: Three Contexts & Module Boundaries

### Three Execution Contexts
Routes are split by domain/subdomain (see `config/app.php` and `routes/web.php`):

1. **Admin Context** (`admin.168csn.com`)  
   - Admin panel, role-based access (see `Gametech\Admin\Providers\AdminServiceProvider`)
   - Middleware: `'admin' => Bouncer::class`
   - Views: `admin::*` from `packages/Gametech/Admin/src/Resources/views`

2. **Customer/Wallet Context** (`user.168csn.com` or `wallet.168csn.com`)  
   - End-user wallet, games, topup, promotions (see `packages/Gametech/Wallet/src/Http/Routes/routes.php`)
   - Middleware: `'customer' => RedirectIfNotCustomer::class`, `'online'`
   - Views: `wallet::*` from `packages/Gametech/Wallet/src/Resources/views_kimberbet`

3. **API Context** (`api.168csn.com` or path `/api/*`)  
   - **Core API routes are stateless** (`routes/api.php` + `api` middleware, no session/CSRF)
   - Webhook handlers for payment callbacks
   - Routes: `routes/api.php` + module routes (e.g., `Gametech\API\Providers\APIServiceProvider`)

**Note**: `routes/web.php` also defines wallet-domain `Route::prefix('api')` endpoints. Push subscribe/unsubscribe routes use `customer` + `authuser`, while track routes are stateful (`web`) but not guarded by `customer` middleware.

**Critical**: `AppServiceProvider` lazily resolves Core service and skips heavy view compositions for API context to prevent slowdowns. Current `isApiContext()` detection is path/middleware based (`api/*` or `api` middleware), not host-prefix based.

### Modular Structure (Concord-based)
**All domain logic lives in `packages/Gametech/`** (see `config/concord.php`):

- **Core** – Config, notices, FAQs, coupons, daily stats (foundation models & `Core` singleton)
- **Admin** – Role-based access, audit logs, admin controllers
- **Wallet** – Customer portal: profiles, topup, withdraw, promotions, game redirects
- **Payment** – Payment integrations (Pompay, HengPay, LuckyPay, Superrich, EzPay, CommsPay, etc.)
- **Game** – Game library, user game accounts, seamless game login
- **Member** – Customer data, loyalty points, diamonds, cashback tracking
- **Promotion** – Promotion engine (selection, deselection, retrieval)
- **API** – External game provider integration (Jili, PGSoft, Live22, Evoplay, etc.)
- **Lotto** – Lotto admin domain (groups/markets/draws, exposure, tickets, member permissions)
- **Sms, LineOA, Marketing, Reward, LogAdmin, LogUser, Ui, CenterOA** – Specialized modules/features

**Module Registration Pattern**:
- Each module has `Providers/ModuleServiceProvider` (Concord-based) that registers models via `$models = [...]`
- Example: `Gametech\Payment\Providers\ModuleServiceProvider` registers all payment models as Proxy classes
- Routes loaded in module-specific ServiceProviders, NOT in RouteServiceProvider

---

## 2. Request Flow & Route Handling

### URL Structure
```
Routes detected via domain + subdomain routing (routes/web.php):
- {admin_url}.{admin_domain_url}  → Admin routes
- {user_url}.{user_domain_url}    → Wallet routes
/api/*                             → API routes
```

`routes/web.php` also contains wallet-domain `/api/*` endpoints (`PushController`, `TrackController`) that are stateful (`web` middleware). Only push subscribe/unsubscribe routes add `customer` + `authuser`; do not assume every `/api/*` path is stateless or customer-authenticated.

### Critical Route Pattern: `_config` Defaults
Used extensively in Wallet & Promotion routes to pass view + action metadata:
```php
Route::post('promotion', 'PromotionController@store')->defaults('_config', [
    'view' => 'wallet::customer.promotion.index',
    'redirect' => 'customer.promotion.index',
])->name('customer.promotion.store');
```
Controllers extract via `$this->_config = request('_config')` (see `UiController`, `PromotionController`).

### Key Middlewares
- **`web`** – Session, CSRF, encryption (customer & admin)
- **`api`** – Only SubstituteBindings + XraySlowRequest (no session/CSRF)
- **`customer`** – Redirect if no authenticated customer (Wallet context)
- **`admin`** – Bouncer role-based gate (Admin context)
- **`online`** – Track user activity in Wallet (optional)
- **`authuser`** – Custom auth for customers (see `AuthenticateUser` middleware)

---

## 3. Data Layer: MySQL + MongoDB + Redis Separation

### Databases
- **MySQL** (primary): Config, users, payments, promotions, game logs
- **MongoDB** (logs): Game events, detailed game logs (see `config/database.php` MongoDB section)
- **Redis**: Cache, sessions, queues, fanout (multi-DB per purpose)

### Redis DB Isolation (critical for agents)
See `config/database.php` `redis` key:
```php
'default'  => DB 0  // Cache
'session'  => DB 1  // Sessions
'queue'    => DB 2  // Queue jobs
'game'     => DB 3  // Game temp state
'gamelog'  => DB 0  // Game logs (separate Redis instance)
'fanout'   => DB 5  // WebSocket fanout (separate instance)
```
**Do NOT cross these boundaries** when accessing Redis; use named connection in queue config.

### Concord Proxies (Model Interception)
All models registered via ModuleServiceProvider become "Proxies" (Concord feature). Example: `ConfigProxy` → `Config` model. This allows runtime replacement for multi-tenancy. **Always resolve via container, not direct instantiation**.

---

## 4. Critical Developer Workflows

### Build & Assets
```bash
npm install              # Install dependencies
npm run dev              # One-time development build (Laravel Mix)
npm run watch            # Watch & recompile assets
npm run production       # Production build (minified CSS/JS)
```
Webpack config: `webpack.mix.js` (basic, compiles `resources/sass/app.scss` → `public/css/app.css`)

### Test & Quality
```bash
./vendor/bin/phpunit              # Run PHPUnit (tests/Unit + tests/Feature)
./vendor/bin/phpunit --filter=Lotto  # Run Lotto-focused tests
./vendor/bin/pint                  # Laravel Pint code style fixer
php artisan optimize:clear         # Clear all caches
```
Test setup: `phpunit.xml` uses SQLite `:memory:` for Feature tests.

### Database & Scheduling
```bash
php artisan migrate --force        # Apply migrations (runs nightly at 23:28 via Kernel::schedule)
php artisan seed                   # Seed database
php artisan horizon                # Monitor background jobs (Horizon dashboard)
php artisan schedule:run           # Run scheduled tasks (via cron)
```

### Debugging & Performance
- **XRay Slow Request Logger** (`config/xray.php`): Logs SQL, Redis, HTTP calls > threshold (default 100ms)
  - Trigger: `?xray=1` query or `X-Xray-Threshold` header
  - Output: `storage/logs/slow-requests.log` with top 3 slowest queries
- **LogFailedRequests** middleware exists (`app/Http/Middleware/LogFailedRequests.php`) but is currently disabled in `app/Http/Kernel.php` (`api` group commented out)
- **Payment-specific logs**: Separate channels for each payment provider (`config/logging.php`)

### Queue Management (Horizon)
Configured supervisors in `config/horizon.php`:
- `supervisor-topup` – Real-time topup processing (1 worker, 60s timeout)
- `supervisor-kbank` – Bank payment checks (1 worker)
- `supervisor-daily` – Cashback, IC, batch jobs (2 workers, auto-scale)

Jobs defined in `app/Console/Kernel::schedule()` – e.g., `payment:get kbank` every minute.

---

## 5. Integration Points & External Dependencies

### Payment Gateways
Configured in `config/app.php` (env vars only):
- **Pompay** – `pompay_url_payment`, `pompay_clientId`, `pompay_clientSecret`
- **HengPay, LuckyPay, PapayaPay, Superrich, EzPay, CommsPay** – Similar pattern
- Callbacks routed to `Gametech\Admin\Http\Controllers\[PaymentName]Controller@callback`

### Broadcasting & WebSockets
- **Driver**: Laravel WebSockets (`config/websockets.php`) or Pusher
- **Channels**: `config('app.name').'_members.{id}'`, `config('app.name').'_events'`, `dashboard.summary.{webCode}` (see `routes/channels.php`)
- **Use**: Real-time member notifications, admin dashboard updates
- **Port**: 6001 (default), configured in `.env` via `PUSHER_PORT`

### Game API Integration
External game providers connected via `Gametech\API` package:
- **Models**: GameLogProxy, GameProxy, GameUserProxy (stored in MySQL + MongoDB)
- **Seamless Mode**: If `config.seamless = 'Y'`, games run in-house. Else, redirect to external game platform.
- **Game State**: Stored in Redis (`redis.game` DB) for active sessions

### Monitoring & Observability
- **Octane** – Server daemon (Laravel Octane config in `config/octane.php`)
- **Horizon** – Queue monitoring dashboard
- **Debugbar** – Development profiler (disabled in production, see `config/debugbar.php`)

---

## 6. Conventions & Patterns

### Service Provider Pattern
Each module registers:
1. **ModuleServiceProvider** (Concord) – Models only
2. **[Feature]ServiceProvider** (optional) – Routes, views, events
3. **CoreServiceProvider** – Singleton `Core` service, facades, exception handler

Example: `Gametech\Wallet\Providers\WalletServiceProvider` loads routes from `routes.php` in `boot()`.

### Admin Menu Creation Pattern (Lotto & Admin Modules)
When adding a new admin menu item (e.g., for Lotto submodules), follow this standardized pattern:

**Golden Reference (must match):**
- `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/index.blade.php`
- `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/create.blade.php`
- `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/table.blade.php`
- `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/addedit.blade.php`
- `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/groups/datatables_actions.blade.php`
- `packages/Gametech/Lotto/src/Http/Controllers/Admin/LotteryGroupController.php`
- `packages/Gametech/Lotto/src/DataTables/LotteryGroupDataTable.php`
- `packages/Gametech/Lotto/src/Transformers/LotteryGroupTransformer.php`

**1. Model & Proxy Classes**
- Create model: `packages/Gametech/[Module]/src/Models/[ModelName].php`
- Create proxy: `packages/Gametech/[Module]/src/Models/[ModelName]Proxy.php`
- Register in `packages/Gametech/[Module]/src/Providers/ModuleServiceProvider.php` under `$models = [...]`

**2. DataTable & Transformer**
- Create DataTable: `packages/Gametech/[Module]/src/DataTables/[ModelName]DataTable.php`
  - Extends `Yajra\DataTables\Services\DataTable`
  - Implements `dataTable()`, `query()`, `html()` methods
  - Uses `setTransformer(new [ModelName]Transformer())`
- Create Transformer: `packages/Gametech/[Module]/src/Transformers/[ModelName]Transformer.php`
  - Extends `League\Fractal\TransformerAbstract`
  - Implements `transform()` method returning array with field formatting, action buttons

**3. Controller**
- Create controller: `packages/Gametech/[Module]/src/Http/Controllers/Admin/[ModelName]Controller.php`
  - Extends `Gametech\Admin\Http\Controllers\AppBaseController`
  - Constructor: `$this->_config = (array) request('_config', [])`
  - Methods: `index()`, `create()`, `edit()`, `update()`, `loadData()`
  - Use `$dataTable->render($this->_config['view'])` in index
  - Return JSON responses with `$this->sendResponse()` / `$this->sendError()`

**4. Routes**
- Add routes in `packages/Gametech/[Module]/src/Routes/admin.php`
- Follow pattern: Each action (index, create, edit, update) has a named route
- Use `->defaults('_config', ['view' => 'admin::module.[module].[entity].index'])`
- Group under middleware: `['web', 'admin', 'auth', '2fa']`

**5. Views**
- Create folder: `packages/Gametech/[Module]/src/Resources/views/admin/module/[module]/[entity]/`
- Files:
  - `index.blade.php` – Include `create` + `table` + `addedit` partials (same structure as `groups/index.blade.php`)
  - `create.blade.php` – Top-right add button calling `addModal()`
  - `table.blade.php` – DataTable rendering + `admin::layouts.datatables_css/js`
  - `addedit.blade.php` – Vue modal form with `addModal()`, `editModal()`, `addEditSubmit()`, `loadData()`
  - `datatables_actions.blade.php` – Row action button (at minimum edit via `editModal(id)`)

**6. Menu Configuration**
- Add menu entries in `packages/Gametech/[Module]/src/Config/admin-menu.php`
- Parent menu (if new category): `'key' => '[module]'`, `'route' => 'admin.[module].[primary_entity].index'`, `'sort' => [number]`
- Submenu: `'key' => '[module].[entity]'`, `'route' => 'admin.[module].[entity].index'`, `'sort' => [sub_sort]`
- All use `'icon-class' => 'fa-[icon]'`, `'badge' => 0`, `'badge-color' => 'badge-primary'`, `'status' => 1`

**Example (Lotto Groups Menu)**:
```
Model: LotteryGroup + LotteryGroupProxy
DataTable: LotteryGroupDataTable + LotteryGroupTransformer
Controller: LotteryGroupController (index, create, edit, update, loadData)
Routes: /admin/lotto/groups (GET, POST create/edit/update/loaddata)
Views: admin/module/lotto/groups/{index, table, addedit, datatables_actions}.blade.php
Menu: admin-menu.php entries for 'lotto' + 'lotto.groups'
```

### Config-Driven Routing
Routes reference `_config` array for view + action metadata. Never hardcode views; always use:
```php
->defaults('_config', ['view' => 'namespace::view.path', 'redirect' => 'route.name'])
```

### LadaCache (Query Cache)
Config model uses `LadaCacheTrait` to cache queries. **Observe changes**:
```php
ConfigProxy::observe(ConfigObserver::class); // Invalidates cache on config update
```

### Code Style
- **Indent**: 4 spaces (`.editorconfig`)
- **PHP**: PSR-4 autoload via composer.json
- **Locales**: Thai (`th`) default, English (`en`) fallback
- **Timezone**: `Asia/Bangkok` by default

### Helpers & Services
- **Core Service** – Singleton in container (`app('core')`). Access config, profile, menus, notices via methods like `$core->getConfigData()`, `$core->getProfile()`
- **Helper Files**: Autoloaded from `packages/Gametech/Core/src/Http/helpers.php` + Admin helpers
- **Facades**: `Core::class` accessible as `CoreFacade` or service resolution

---

## 7. Lotto Module (Domain-Specific)

### Module Location
`packages/Gametech/Lotto/` – Complete admin domain for lottery management including groups, markets, draws, tickets, rate plans, and member permissions.

### Core Lotto Models (Registered as Proxies)
- **LotteryGroup** – Lottery groups (e.g., Thai Lottery, Provincial Lottery)
- **LotteryMarket** – Markets within groups (e.g., 2-digit, 3-digit, Hanoi)
- **LottoRatePlan** – Rate/odds configuration per market
- **LottoRatePlanItem** – Per-bet-type odds rows under a rate plan
- **LottoDraw** – Draw schedules (date, time, market assignments)
- **LottoDrawBetSetting** – Draw-level bet setting snapshot/override
- **LottoMarketBetSetting** – Default market betting constraints
- **LottoNumberExposure** – Aggregated number exposure records for risk/reporting
- **LottoTicket** – Member betting tickets (collection of bets)
- **LottoTicketItem** – Individual bets within a ticket
- **LottoNumberBlock** – Blocked/restricted numbers per market
- **MemberLottoMarketPolicy** – Market-level permission snapshot per member (policy-driven inheritance)
- **MemberLottoPermission** – Member visibility and play rights per market
- **MemberLottoSetting** – Member-specific min/max bets per market/type

### Lotto Admin Routes Structure
File: `packages/Gametech/Lotto/src/Routes/admin.php`
- Base route: `/admin/lotto/`
- Grouped under domain `config('app.admin_url').'.'.(config('app.admin_domain_url') ?: config('app.domain_url'))`
- Middleware: `['web', 'admin', 'auth', '2fa']`
- All routes use `->defaults('_config', [...])` pattern

File: `packages/Gametech/Lotto/src/Routes/api.php`
- Member API base route: `/api/lotto/*`
- Middleware: `['api', 'authuser:customer']`
- Route file is loaded in `packages/Gametech/Lotto/src/Providers/LottoServiceProvider.php`

### Lotto Admin Menu Configuration
File: `packages/Gametech/Lotto/src/Config/admin-menu.php`
Parent menu (sort 87):
- Key: `lotto`, Route: `admin.lotto.groups.index`, Icon: `fa-ticket`

Submenus (sort 1-11):
1. **Groups** (กลุ่มหวย) – `admin.lotto.groups.index`
2. **Markets** (รายการหวย) – `admin.lotto.markets.index`
3. **Rate Plans** (อัตราจ่าย) – `admin.lotto.rate_plans.index`
4. **Default Settings** (ค่าพื้นฐาน) – `admin.lotto.default_settings.index`
5. **Member Permissions** (สิทธิ์การเล่น) – `admin.lotto.member_permissions.index`
6. **Member Rate Plans** (อัตราจ่ายสมาชิก) – `admin.lotto.member_rate_plans.index`
7. **Draws** (งวดหวย) – `admin.lotto.draws.index`
8. **Number Blocks** (เลขอั้น) – `admin.lotto.number_blocks.index`
9. **Tickets** (รายการแทง) – `admin.lotto.tickets.index`
10. **Exposure Report** (รายงาน Exposure) – `admin.lotto.reports.exposure`
11. **Revenue Report** (รายงานรายได้) – `admin.lotto.reports.revenue`

### Lotto View Structure
File path: `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/`
- `groups/` – Group management (index, table, addedit, datatables_actions)
- `markets/` – Market management (index, table, addedit, datatables_actions)
- `rate_plans/` – Rate plan management
- `default_settings/` – Default betting limits and settings
- `draws/` – Draw schedule management
- `number_blocks/` – Number blocking configuration
- `tickets/` – Ticket view and management
- `member_permissions/` – Member access permissions
- `member_rate_plans/` – Member-specific rate configurations
- `exposure_report/` – Exposure report screens
- `revenue_report/` – Revenue report screens
- `_shared/` – Shared components used across Lotto views

### Lotto-Specific Data Tables & Transformers
Located in:
- `packages/Gametech/Lotto/src/DataTables/` – DataTable classes
- `packages/Gametech/Lotto/src/Transformers/` – Transformer classes

Available:
- LotteryGroupDataTable + LotteryGroupTransformer
- LotteryMarketDataTable + LotteryMarketTransformer
- LottoRatePlanDataTable + LottoRatePlanTransformer
- LottoDrawDataTable + LottoDrawTransformer
- LottoMarketBetSettingDataTable + LottoMarketBetSettingTransformer
- MemberLottoSettingDataTable + MemberLottoSettingTransformer
- MemberLottoPermissionDataTable + MemberLottoPermissionTransformer
- LottoNumberBlockDataTable + LottoNumberBlockTransformer
- LottoTicketDataTable + LottoTicketTransformer
- LottoExposureReportDataTable + LottoExposureReportTransformer
- LottoRevenueReportDataTable + LottoRevenueReportTransformer

### Lotto Admin Controllers
File: `packages/Gametech/Lotto/src/Http/Controllers/Admin/`
- **LotteryGroupController** – Manage lottery groups (CRUD + loadData + applyRollout + searchMembers)
- **LotteryMarketController** – Manage markets within groups (CRUD + loadData + applyRollout + searchMembers)
- **LottoRatePlanController** – Manage rate plans (CRUD + loadData)
- **LottoMarketBetSettingController** – Manage default settings (CRUD + status toggle)
- **LottoDrawController** – Manage draws (CRUD + loadData + open + close + settle)
- **LottoNumberBlockController** – Manage number blocks (CRUD + loadData)
- **LottoTicketController** – View tickets (index + loadData; read-only)
- **MemberLottoPermissionController** – Manage member market permissions (CRUD + allow/deny toggle)
- **MemberLottoSettingController** – Manage member rate plans (CRUD + loadData)
- **LottoExposureReportController** – Exposure report from `lotto_number_exposures` (read-only, filterable)
- **LottoRevenueReportController** – Revenue report aggregated per draw (read-only)
- **SectionController** – Handle redirect/static fallback section only

### Lotto Unit Tests
File: `tests/Unit/Lotto/` – 141 tests, 598 assertions (all passing)
- **BetTypeTest** – BetType enum (all(), label(), distinct constants, snake_case)
- **DrawStatusFlowTest** – Draw lifecycle state transitions
- **LottoAclCoverageTest** – ACL entries cover all lotto action routes
- **LottoAdminModulePatternCompletionTest** – member_permissions and exposure_report are real modules
- **LottoAdminRolloutScaffoldTest** – rollout/search-members routes + views wired
- **LottoApiRouteScaffoldTest** – API routes loaded via LottoServiceProvider
- **LottoConcordProxyAuditTest** – No direct model instantiation, all Proxies exist and registered
- **LottoConcurrencyGuardTest** – DB::transaction + lockForUpdate present in all critical paths
- **MemberMarketPolicyServiceTest** – isValidRolloutMode accepts/rejects values correctly
- **SettlementEdgeCasesTest** – isWinningBet edge cases (leading zeros, TOD_3 permutations, run boundaries)
- **SettlementReconciliationTest** – win-amount formula, reconciliation totals, net revenue math
- **ExposureRaceConditionTest** – exposure limit invariants + race-condition stale-read scenario
- **SettlementServiceTest** – normalizeResultNumber, isWinningBet, describeResultNumber
- **ToggleFieldGuardTest** – allowlist field guard + boolean resolver

### Lotto Routes Pattern
Each entity follows:
```php
Route::get('[entity]', '[Controller]@index')
    ->defaults('_config', ['view' => 'admin::module.lotto.[entity].index'])
    ->name('admin.lotto.[entity].index');

Route::post('[entity]/create', '[Controller]@create')
    ->name('admin.lotto.[entity].create');

Route::post('[entity]/edit', '[Controller]@edit')
    ->name('admin.lotto.[entity].edit');

Route::post('[entity]/update', '[Controller]@update')
    ->name('admin.lotto.[entity].update');

Route::post('[entity]/loaddata', '[Controller]@loadData')
    ->name('admin.lotto.[entity].loaddata');

// Optional rollout action for existing members (groups/markets)
Route::post('[entity]/apply-rollout', '[Controller]@applyRollout')
    ->name('admin.lotto.[entity].apply_rollout');

// Optional member lookup for rollout selected mode
Route::post('[entity]/search-members', '[Controller]@searchMembers')
    ->name('admin.lotto.[entity].search_members');
```

### Lotto Member Policy Rollout (Policy C Baseline)
- Group/Market supports `rollout_mode` = `new_only` / `all` / `selected` (`lotto_groups`, `lotto_markets`)
- New member policy snapshot is bootstrapped from event `member.created.after` in `LottoServiceProvider`
- Existing members are updated explicitly by admin rollout action (`applyRollout`) or batch command
- Groups/Markets admin views support row-selection batch rollout actions (selected rows -> apply to `all` or `selected` members)
- Batch backfill command: `php artisan lotto:policy-bootstrap-members --chunk=500`
- Betting permission check reads `member_lotto_market_policies` first when policy rows exist; legacy `member_lotto_permissions` remains fallback

---

## 8. Deployment & Environment Configuration

### Key `.env` Variables
```bash
APP_NAME, APP_ENV, APP_DEBUG, APP_KEY
DB_*, MONGO_DB_*              # Database connections
REDIS_*, REDIS_SESSION_DB, REDIS_QUEUE_DB  # Redis multi-DB
APP_API_URL                   # API subdomain prefix (used by `config/gametech.php` + `routes/api.php`)
BROADCAST_DRIVER, PUSHER_*    # WebSocket config
APP_ADMIN_URL, APP_USER_URL, APP_ADMIN_DOMAIN_URL, APP_USER_DOMAIN_URL  # Multi-subdomain
PAYMENT_API_URL, PAYMENT_PARTNER_ID, PAYMENT_SECRET_KEY  # Payment provider credentials
```

### Server Setup (Caddyfile)
Current `Caddyfile` uses FrankenPHP with a site block on **:7001** and `php_server` `try_files {path} /index.php`; global config also sets `http_port 7002`. The configured root is deployment-specific (`/home/www/core/kick789/public`).

### Web Server Entry Points
- **`public/index.php`** – Main entry point (Laravel)
- **`public/opcache.php`** – OPcache info (development only)
- **`public/tester.php`, `testera.php`** – Internal test endpoints (remove in production)

---

## 9. When to NOT Make Changes

- ❌ **Don't edit `.env` files** – Use environment variables only
- ❌ **Don't modify Proxy model mappings** – Concord handles this
- ❌ **Don't bypass middleware** – Use `withoutMiddleware()` only in testing
- ❌ **Don't add routes directly in RouteServiceProvider** – Use module ServiceProviders
- ❌ **Don't cache sensitive config** – Payment credentials must remain dynamic
- ❌ **Don't share Redis connections across DB purposes** – Use named connections

---

## 10. File Index for Common Tasks

| Task | Key Files |
|------|-----------|
| Add payment gateway | `config/app.php` (env var) + `Gametech/Payment/src/Http/Controllers/[Gateway]Controller.php` |
| Add wallet feature | `packages/Gametech/Wallet/src/Http/Routes/route_member.php` + `Controllers/` |
| Add promotion type | `packages/Gametech/Promotion/src/` + `config` via Core |
| Add API endpoint | `routes/api.php` or module API route file |
| Debug slow request | Check `storage/logs/slow-requests.log` (XRay output) |
| Monitor jobs | Visit `/horizon` dashboard (Admin context) |
| View customer data | `Gametech\Member\Models\Member` (+ related Proxy models) |
| Modify admin UI | `packages/Gametech/Admin/src/Resources/views/` |
| Add/modify Lotto admin feature | `packages/Gametech/Lotto/src/Routes/admin.php` + `packages/Gametech/Lotto/src/Http/Controllers/Admin/` |
| Extend Lotto domain models | `packages/Gametech/Lotto/src/Models/` + `packages/Gametech/Lotto/src/Providers/ModuleServiceProvider.php` |
| Trace Core singleton | `packages/Gametech/Core/src/Core.php` + `Facades/Core.php` |
| Add Lotto admin menu item | `packages/Gametech/Lotto/src/Config/admin-menu.php` + Create model, DataTable, Transformer, Controller, Routes, Views (see Admin Menu Creation Pattern) |
| Lotto model references | `packages/Gametech/Lotto/src/Models/` (LotteryGroup, LotteryMarket, LottoRatePlan, LottoDraw, LottoTicket, etc.) |
| Lotto DataTables | `packages/Gametech/Lotto/src/DataTables/` (LotteryGroupDataTable, LotteryMarketDataTable, LottoRatePlanDataTable, etc.) |
| Lotto admin views | `packages/Gametech/Lotto/src/Resources/views/admin/module/lotto/` (groups, markets, rate_plans, draws, tickets, member_permissions, etc.) |

---

## 11. Quick Start for Agents

1. **Identify Context**: Is request to `admin.*`, `wallet.*`, or `/api/*`? (See `AppServiceProvider::isApiContext()`)
2. **Find Module**: Locate relevant package in `packages/Gametech/[Module]/`
3. **Load Routes**: Check if module loads routes in its ServiceProvider `boot()`
4. **Check Middleware**: Verify auth + context-specific middleware applies
5. **Trace Models**: Use Proxy classes; resolve via container, not direct class instantiation
6. **Test**: Use `phpunit.xml` config; isolate in Feature/Unit tests
7. **Profile**: Add `?xray=1` to slow endpoints; check `storage/logs/slow-requests.log`

---

**Generated**: 2026-03-20  
**Last Updated**: 2026-03-21 – Corrected wallet `/api/*` auth nuance (push guarded, track stateful-only), documented current API-context detection (`api/*`/middleware), switched test commands to PHPUnit binary, expanded Lotto model/view inventory (RatePlanItem/DrawBetSetting/MarketBetSetting/NumberExposure + report view folders), and aligned Caddyfile notes with current :7001 + global `http_port` configuration  
**Framework**: Laravel 8 + Concord 1.8 + Custom Gametech Modules
