# Data Model & Schema — 2026-06-24

> **Perspective**: 💾 Data — schemas, relationships, migrations, indexes, access patterns

---

## Database Landscape

- **Engine**: MariaDB, database `1168lot_wallet`
- **Tables**: 170+
- **Model location**: `packages/Gametech/*/src/Models/` (not `app/Models/`)
- **Model count**: Lotto ~45, Payment ~30, Core ~25, Member ~20, API ~20, Game ~14

---

## wallet_transactions — Financial Source of Truth

### Schema
```
id              BIGINT UNSIGNED PK AUTO_INCREMENT
member_id       BIGINT UNSIGNED NOT NULL
scope           VARCHAR(32) NOT NULL (MEMBER, GAME, PROMOTION)
direction       VARCHAR(16) NOT NULL (CREDIT, DEBIT)
amount          DECIMAL(15,2) NOT NULL
balance_before  DECIMAL(15,2) NOT NULL
balance_after   DECIMAL(15,2) NOT NULL
ref_type        VARCHAR(32) NOT NULL
ref_id          BIGINT UNSIGNED NULL (soft-reference)
ref_code        VARCHAR(64) NULL
group_code      VARCHAR(64) NULL
related_txn_id  BIGINT UNSIGNED NULL
status          VARCHAR(16) DEFAULT 'SUCCESS'
meta            LONGTEXT NULL (JSON)
created_at, updated_at TIMESTAMP
```

### Key Indexes (18 total)
| Index | Columns | Purpose |
|-------|---------|---------|
| wallet_txn_member_dir_ref_unique (UNIQUE) | `(member_id, direction, ref_type, ref_id)` | Idempotency guard |
| wallet_member_scope_created_idx | `(member_id, scope, created_at)` | Member timeline |
| wallet_ref_type_id_idx | `(ref_type, ref_id)` | Source record lookup |

### Design Decisions
- **Soft-reference pattern**: `(ref_type, ref_id)` instead of FKs — flexibility vs integrity
- **Balance derived**: `balance_before/after` stored per row, NOT cached separately
- **DECIMAL(15,2)**: Supports up to 999,999,999,999.99
- **No database-level FKs**: All referential integrity at application level

---

## Lotto Core Data Chain

```
lottery_groups ──< lotto_markets ──< lotto_draws ──< lotto_tickets ──< lotto_ticket_items
                     │                    │
                     ├── lotto_market_bet_settings (defaults)
                     ├── member_lotto_market_policies (access control)
                     ├── lotto_market_contents (CMS per locale)
                     └── lotto_result_sources (fetch configs)
                                          │
                     lotto_draws ─────────┤
                     ├── lotto_draw_bet_settings (snapshot at open)
                     ├── lotto_number_exposures (per-number stakes)
                     └── lotto_number_blocks (forbidden numbers)
```

### lotto_tickets
- Status: `active → resulted | cancelled`
- Amount columns: `total_amount`, `total_bet_amount`, `total_discount_amount`, `total_net_amount`, `total_win_amount` — all DECIMAL(12,2)
- Key indexes: `(member_id, draw_id)`, `(draw_id, status)`, `(status, bet_confirmed_at, id)`

### lotto_ticket_items
- Snapshot pattern: `_at_time` suffix freezes payout/discount/package at bet moment
- `calculated_values_at_bet_time`: JSON snapshot of ALL computed values for audit
- Key index: `(ticket_id, bet_type, number)` for exposure aggregation

### lotto_draws
- 31 columns including full result lifecycle tracking
- Result columns: `result_number` (LONGTEXT, cast array), `result_hash` (VARCHAR 128), `result_raw_payload_json`, `result_normalized_payload_json`
- Fetch tracking: `result_fetch_status`, `result_fetch_attempts`, `result_fetch_error`
- Status: `draft → open → closed → resulted`

---

## Bet Settings: 3-Tier Override

| Tier | Table | Purpose | Immutable? |
|------|-------|---------|------------|
| 1 | `lotto_market_bet_settings` | Market defaults | No — template |
| 2 | `lotto_draw_bet_settings` | Per-draw snapshot | ✅ Yes after open |
| 3 | `lotto_group_package_bet_settings` | Package overrides | No — promotional |

- Draw snapshot copied at open time via `DrawService::snapshotBetSettings`
- Package overrides: only `payout`, `discount_percent`, `is_enabled` (no bet limits)
- `$timestamps = false` on `lotto_draw_bet_settings` model

---

## Settlement & Result Data

### settlement_batches
- `idempotency_key` VARCHAR(255) UNIQUE → prevents duplicate settlement
- Modes: `settlement`, `backfill`, `replay`, `result_correction`
- Status: `pending → settled | failed | voided`

### lotto_winnings
- UNIQUE: `(draw_id, bet_item_id)` → one winning per item per draw
- 13 indexes including: draw+user, draw+bet_type, draw+number, status
- Void tracking: `voided_by_correction_id` FK to `lotto_result_corrections`

### lotto_result_archives (Append-Only Mirror)
- Separate from operational `lotto_draws.result_number`
- Tracks corrections via `correction_count`, `previous_result_set`
- Log table: `lotto_result_archive_logs` — `action` (create/update/delete/replay), `run_id` for grouping

---

## Payment Data Model

### Three Payment Record Types

| Table | Columns | Purpose | Idempotency |
|-------|---------|---------|-------------|
| `bank_payment` | 60+ | Primary deposit records | `tx_hash` UNIQUE |
| `payments_waiting` | 23 | Intermediate deposit staging | `credit_before/after` tracking |
| `withdraws` | 43 | Payout records | Status + pending check |

### bank_payment
- `status` INT: 0=waiting, 1=complete, 2=reject, 3=clearout
- `deposit_status` VARCHAR(20): NEW → STARTED → DEPOSITED → FINALIZED
- `tx_hash` VARCHAR(32) UNIQUE — deduplication
- `member_topup` FK to members.code — who the deposit is for

### WithdrawSeamless Dynamic Table Routing
- Runtime table switch: `seamless='Y'` → `withdraws`, else → `withdraws_seamless`
- Config-driven deployment pattern in `WithdrawSeamless.php:28-40`

---

## Redis Data Models

| Connection | Purpose | Evidence |
|---|---|---|
| `game` | User game status caching (`user_game_status:{userId}`) | `SeamlessRepository.php` |
| `gamelog` | Game log pipeline | `NewCommonFlowRedisController.php` |
| `session` | Marketing click dedup | `MarketingController.php` |

- Distributed locks: `Redis::set($key, timestamp, 'EX', $ttl, 'NX')` — `BrowserFetchDispatchService.php:117`
- Relay streams: `LotteryRelayStream` — Redis-based data relay
- Cache TTL: game status 600s (10 min)

---

## Schema Evolution Debt

| # | Issue | Detail |
|---|-------|--------|
| 1 | Dual auth columns | `members.user_pass` (plaintext VARCHAR 20) + `members.password` (hashed VARCHAR 191) |
| 2 | Dual credit logs | `members_credit_log` (34 cols, legacy) + `wallet_transactions` (modern) |
| 3 | Inconsistent PK naming | Older: `code`, Newer: `id` |
| 4 | Inconsistent timestamps | Older: `date_create`/`date_update`, Newer: `created_at`/`updated_at` |
| 5 | BankPayment manual mapping | `CREATED_AT = 'date_create'`, `UPDATED_AT = 'date_update'` |
| 6 | Backup tables present | `lotto_result_sources_bk`, `members_ic_bk`, `members_log_bk`, `temp_index` |
| 7 | Per-provider user tables | 16+ `users_*` tables — legacy game provider integration |
| 8 | No soft deletes anywhere | `enable ENUM('Y','N')` pattern instead |
| 9 | No database-level FKs | All integrity at application level |
| 10 | `configs` table: 80+ columns | Any new config needs schema migration |
| 11 | `tx_hash` VARCHAR(32) vs `txid` VARCHAR(100) | Inconsistent identifier sizing |

---

## Risk Dashboard Data

Three-layer risk system:

| Layer | Table | Update Frequency | Purpose |
|-------|-------|-----------------|---------|
| Live | `lotto_dashboard_risk_current` | Every bet | Current exposure per number |
| Snapshots | `lotto_dashboard_risk_snapshot` | Periodic | Time-series tracking |
| Aggregate | `lotto_dashboard_risk_aggregates` | Daily | Cross-market rollup |

- `liability = payout_if_hit - stake_total` — house exposure if number wins
- 6-column composite index on snapshot for filtered queries

---

## Dashboard OLAP

### dashboard_summary_daily
- 61 columns — wide flat denormalized daily aggregations
- All amounts: DECIMAL(18,2) precision
- Partitions: `summary_date` + `web_code`
- Sections: registration, deposit, withdraw, bonus, lotto (sales/payout/refund/net), staff adjustments
- Advanced metrics: first_deposit_count, repeat_deposit_count, register_confirmed_count
- `metric_version` for schema evolution
