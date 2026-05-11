> สถานะ: ACTIVE
> วันที่: 2026-05-11
> โดเมน/เรื่อง: policy / member-lotto-market-blacklist
> แทนแผนเก่า: -
> อ้างอิง: BOA-244

# Member Lotto Market Policy Blacklist — Code Audit

- **Status**: ACTIVE (PR-01 audit phase)
- **Date**: 2026-05-11
- **Domain**: Lotto — member market permissions
- **Linear Project**: [1168lot: Convert member lotto market policies to blacklist](https://linear.app/boatjunior/project/1168lot-convert-member-lotto-market-policies-to-blacklist-269c4fe3e430)
- **Linear Issue**: [BOA-244 PR-01 Audit](https://linear.app/boatjunior/issue/BOA-244/pr-01-audit-current-whitelist-policy-usage-and-runtime)
- **Git Branch**: `b0855626503/boa-244-pr-01-audit-current-whitelist-policy-usage-and-runtime`
- **Package Scope**: `packages/Gametech/Lotto` (active), `packages/Gametech/Lottobk` (not Composer-autoloaded in this snapshot; verify local `composer.json`, package registration, and manual includes before ignoring)

---

## Source of Truth Note

This audit was reviewed against the GitHub PR snapshot. Before PR-02 implementation, the agent must re-run all grep/search patterns against the local working tree. Local codebase wins over GitHub if there is drift.

---

## 1. Summary

`member_lotto_market_policies` currently operates as a **whitelist** table: a member needs a row with `is_allowed = true` to place a bet on a market. The system creates these rows en masse via bootstrap/rollout commands and a `member.created.after` event listener. The FrontendApi does NOT filter/hide markets by policy — it shows all enabled markets regardless. The only enforcement point is `BetService::validateMemberPermission()` which blocks betting if no `is_allowed = true` row exists.

**The Lottobk variant** (`packages/Gametech/Lottobk`) shares the `Gametech\Lotto` namespace but is NOT registered in `composer.json` autoload in this snapshot. Before treating it as dead code in PR-02+, verify local `composer.json`, package registration, and any manual includes. All confirmed active code lives in `packages/Gametech/Lotto`.

---

## 2. Current Behavior (Whitelist Model)

### 2.1 Enforcement Flow

```
Member places bet
  → BetService::placeBet()
    → validateMemberPermission()
      → MemberLottoMarketPolicy::where('member_id', X)
          ->where('market_id', Y)
          ->where('is_allowed', true)    ← REQUIRED to bet
          ->exists()
      → if false: throw Exception('Member does not have permission to bet')
      → if true: continue to wallet debit
```

### 2.2 Market Visibility (FrontendApi)

`marketsLatestByGroup` in `FrontendApi/LottoController.php:73` queries:
```php
LotteryMarket::query()->where('is_enabled', true)
```
**No member_id filter, no policy join.** All enabled markets visible to all members regardless of policy.

### 2.3 Policy Row Creation Triggers

| Trigger | What it does | is_allowed value |
|---------|-------------|-----------------|
| `member.created.after` event → `bootstrapForMember()` | Creates row for new member × all enabled markets | Based on `rollout_mode` (usually `true`) |
| `lotto:policy-bootstrap-members` → `bootstrapAllMembers()` | Creates rows for ALL members × all enabled markets | Based on `rollout_mode` (usually `true`) |
| `lotto:policy-rollout-markets` → `rolloutMarkets()` | Creates rows for members × selected markets | Based on `rollout_mode` or `--force-allow` |
| Admin `LotteryMarketController@applyRollout` → `applyMarketRollout()` | Group/market admin panel rollout | `forceAllow=true` → `is_allowed=true` |
| Admin `LotteryGroupController@applyRollout` → `applyGroupRollout()` | Group admin panel rollout | `forceAllow=true` → `is_allowed=true` |
| Admin `MemberLottoPermissionController@create` | Manual permission creation | Admin chooses (default `false`) |
| `lotto:migrate-legacy-permissions` | One-time migration from `member_lotto_permissions` | Mirrors legacy `is_allowed` |

### 2.4 Rollout Mode Logic

`MemberMarketPolicyService::isAllowedByMode()` (line 368):
```php
private function isAllowedByMode(string $mode): bool
{
    return in_array($mode, [self::ROLLOUT_NEW_ONLY, self::ROLLOUT_ALL], true);
}
```
- `new_only` → `is_allowed = true`
- `all` → `is_allowed = true`
- `selected` → `is_allowed = false` (note: this mode means "member must be explicitly selected," so default is deny)

`rollout_mode` is read from `$market->rollout_mode ?? $group->rollout_mode` with fallback chain market → group → default `new_only`.

---

## 3. Runtime Map

### 3.1 Points that CREATE policy rows (WRITE)

| # | File | Method | Calls | is_allowed |
|---|------|--------|-------|-----------|
| W1 | `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php:35` | `bootstrapForMember(int $memberId)` | `LottoServiceProvider.php:197` (event listener) | `isAllowedByMode(rollout_mode)` |
| W2 | `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php:46` | `bootstrapAllMembers(int $chunkSize)` | `BootstrapMemberMarketPoliciesCommand.php:23` | `isAllowedByMode(rollout_mode)` |
| W3 | `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php:75` | `applyGroupRollout(int $groupId, ...)` | Admin controller (Lottobk only, dead code) | `forceAllow=true` |
| W4 | `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php:97` | `applyMarketRollout(int $marketId, ...)` | Admin controller (Lottobk only, dead code) | `forceAllow=true` |
| W5 | `packages/Gametech/Lotto/src/Services/MemberMarketPolicyService.php:119` | `rolloutMarkets(...)` | `RolloutMemberMarketPoliciesCommand.php:139` | `isAllowedByMode(rollout_mode)` or `--force-allow` |
| W6 | `packages/Gametech/Lotto/src/Http/Controllers/Admin/MemberLottoPermissionController.php:90` | `create()` | Admin panel manual create | Admin chooses (default `false`) |
| W7 | `packages/Gametech/Lotto/src/Http/Controllers/Admin/MemberLottoPermissionController.php:159` | `update()` | Admin panel manual update | Admin chooses |
| W8 | `packages/Gametech/Lotto/src/Console/Commands/MigrateLegacyLottoPermissionsCommand.php:66` | `handle()` | `lotto:migrate-legacy-permissions` | Mirrors legacy value |

### 3.2 Points that READ policy rows (is_allowed enforcement)

| # | File | Line | Query | Effect |
|---|------|------|-------|--------|
| R1 | `packages/Gametech/Lotto/src/Services/BetService.php` | 220-224 | `MemberLottoMarketPolicy::where('member_id',X)->where('market_id',Y)->where('is_allowed',true)->exists()` | **Blocks bet** if no row exists |
| R2 | `packages/Gametech/Lotto/src/DataTables/MemberLottoPermissionDataTable.php` | 23-26 | `MemberLottoMarketPolicy::newQuery()->select('member_lotto_market_policies.*')->with(...)` | Admin datatable listing |
| R3 | `packages/Gametech/Lotto/src/Http/Controllers/Admin/MemberLottoPermissionController.php:57` | 57 | `MemberLottoMarketPolicy::find($id)` | Admin load single row |

### 3.3 Points that use `is_allowed = true` specifically

| # | File | Line | Context |
|---|------|------|---------|
| A1 | `BetService.php` | 223 | `.where('is_allowed', true)` — whitelist check |
| A2 | `MemberLottoPermissionTransformer.php` | 13-14 | UI toggle: `$model->is_allowed ? 0 : 1` |
| A3 | `MemberLottoPermissionTransformer.php` | 22-24 | UI label: `$model->is_allowed ? 'อนุญาต' : 'ปิด'` |

### 3.4 Points that use `is_allowed = false` specifically

| # | File | Line | Context |
|---|------|------|---------|
| F1 | `MemberMarketPolicyService.php` | 316 | `'is_allowed' => $isAllowed` — can be `false` when mode is `selected` |
| F2 | `MemberLottoPermissionController.php` | 94,163 | Admin can set `is_allowed = false` |
| F3 | `MemberLottoPermissionController.php:108` | 108 | `ToggleFieldGuard::resolveField` allows toggling `is_allowed` |

### 3.5 Points that hide/show market based on policy

**NONE found.** The FrontendApi `marketsLatestByGroup` method shows all enabled markets to all members without any policy filtering. Market visibility is controlled only by `is_enabled` on the group and market.

### 3.6 Bootstrap / Rollout Callers

| Caller | File | Method Called | Trigger |
|--------|------|--------------|---------|
| `LottoServiceProvider::boot()` | `packages/Gametech/Lotto/src/Providers/LottoServiceProvider.php:197` | `bootstrapForMember()` | `member.created.after` event |
| `BootstrapMemberMarketPoliciesCommand` | `packages/Gametech/Lotto/src/Console/Commands/BootstrapMemberMarketPoliciesCommand.php:23` | `bootstrapAllMembers()` | `lotto:policy-bootstrap-members` artisan command |
| `RolloutMemberMarketPoliciesCommand` | `packages/Gametech/Lotto/src/Console/Commands/RolloutMemberMarketPoliciesCommand.php:139` | `rolloutMarkets()` | `lotto:policy-rollout-markets` artisan command |
| `MigrateLegacyLottoPermissionsCommand` | `packages/Gametech/Lotto/src/Console/Commands/MigrateLegacyLottoPermissionsCommand.php:66` | `DB::upsert()` directly | `lotto:migrate-legacy-permissions` artisan command |

---

## 4. Affected Files (Complete Inventory)

### 4.1 Active Package: `packages/Gametech/Lotto`

| File | Role | Impact |
|------|------|--------|
| `src/Services/MemberMarketPolicyService.php` | **Core policy engine** — creates, syncs, resolves modes | HIGH — primary target for blacklist conversion |
| `src/Services/BetService.php` | **Enforcement** — `validateMemberPermission()` checks `is_allowed = true` | HIGH — must flip to deny-list check |
| `src/Providers/LottoServiceProvider.php` | **Event listener** — `member.created.after` → `bootstrapForMember()` | HIGH — must stop creating allow rows for new members |
| `src/Console/Commands/BootstrapMemberMarketPoliciesCommand.php` | Mass bootstrap command | HIGH — must stop or repurpose |
| `src/Console/Commands/RolloutMemberMarketPoliciesCommand.php` | Rollout command with scope/mode | HIGH — must stop or repurpose |
| `src/Console/Commands/MigrateLegacyLottoPermissionsCommand.php` | One-time legacy migration | MEDIUM — review if still needed |
| `src/Models/MemberLottoMarketPolicy.php` | Eloquent model | MEDIUM — field/cast unchanged |
| `src/Models/LotteryGroup.php` | Group model — has `rollout_mode` not in fillable | MEDIUM — `rollout_mode` column exists in DB but not mass-assignable |
| `src/Models/LotteryMarket.php` | Market model — has `rollout_mode` not in fillable | MEDIUM — same as above |
| `src/Database/Migrations/2026_03_21_000001_add_rollout_policy_and_member_market_policies.php` | Table creation migration | LOW — historical, do not modify |
| `src/Database/Migrations/2026_03_18_100003_create_member_lotto_permissions.php` | Legacy table creation | LOW — historical |
| `src/Database/Migrations/2026_03_23_150000_drop_deprecated_lotto_member_override_tables.php` | Drop deprecated tables | LOW — historical |
| `src/Http/Controllers/Admin/MemberLottoPermissionController.php` | Admin CRUD for individual policies | MEDIUM — UI labels need update, logic stays |
| `src/Http/Controllers/Admin/LotteryMarketController.php` | Main admin market CRUD | LOW — does NOT interact with rollout/policy currently |
| `src/Http/Controllers/Admin/LotteryGroupController.php` | Main admin group CRUD | LOW — does NOT interact with rollout/policy currently |
| `src/DataTables/MemberLottoPermissionDataTable.php` | Admin datatable | LOW — UI only |
| `src/Transformers/MemberLottoPermissionTransformer.php` | UI toggle/label for `is_allowed` | MEDIUM — labels: "อนุญาต/ปิด" vs "บล็อก/ปกติ" |
| `src/Observers/LottoAuditObserver.php` | Audit logging | LOW — `member_lotto_market_policies` is in skip-list (line 71) |
| `src/Resources/views/admin/module/lotto/member_permissions/addedit.blade.php` | Admin form UI | LOW — checkbox label may need update |

### 4.2 Not Autoloaded: `packages/Gametech/Lottobk`

These files are NOT registered in Composer autoload in this snapshot. They exist on disk for reference but are NOT confirmed to be executed in production. Before treating this directory as dead code in PR-02+, verify local `composer.json`, package registration, and any manual includes. If confirmed dead, all files in this directory can be treated as out-of-scope for this migration.

- `src/Services/MemberMarketPolicyService.php` (simpler variant, no `rolloutMarkets` method)
- `src/Services/BetService.php` (simpler variant with legacy MemberLottoPermission fallback)
- `src/Providers/LottoServiceProvider.php` (simpler variant)
- `src/Http/Controllers/Admin/LotteryMarketController.php` (has `applyRollout` and `rollout_mode` in form)
- `src/Http/Controllers/Admin/LotteryGroupController.php` (same, with `applyRollout`)
- `src/Http/Controllers/Admin/MemberLottoPermissionController.php` (same as active)
- `src/Models/MemberLottoMarketPolicy.php`, `src/Models/MemberLottoPermission.php`, etc.
- All other Lottobk files

### 4.3 FrontendApi

**ZERO references** to member market policies found via the grep patterns listed in Section 11. This is a grep-based finding — no direct policy filter/join was found in `marketsLatestByGroup` or other FrontendApi query paths. Before concluding no FrontendApi changes are needed in PR-02, the implementer must verify local API payloads, transformers, and any indirect policy effects through joined queries or cached data.

### 4.4 Tests

| File | Coverage |
|------|----------|
| `tests/Feature/MemberMarketPolicyServiceBootstrapTest.php` | Bootstrap creates policies for enabled markets, updates existing |
| `tests/Feature/Lotto/MemberMarketPolicyRolloutCommandTest.php` | Rollout command: missing-only, resync, dry-run, idempotency |
| `tests/Unit/Lotto/MemberMarketPolicyServiceTest.php` | Rollout mode validation |
| `tests/Unit/Lotto/LottoConcurrencyGuardTest.php` | References `MemberLottoMarketPolicy` class name in content check |
| `tests/Unit/Lotto/LottoConcordProxyAuditTest.php` | References `MemberLottoMarketPolicy` in proxy audit |

---

## 5. Risk Analysis

### 5.1 High Risk

1. **Mass allow-row creation on new member registration**: Every new member gets `is_allowed = true` rows for ALL enabled markets via `member.created.after` event. If we stop this without handling the blacklist logic, all new members will be blocked from betting. **Mitigation**: Flip BetService to blacklist first (PR-02); only then remove the event listener (PR-03). Never remove the listener before flipping enforcement. The sequenced approach is safe because existing `is_allowed = true` rows are harmless no-ops under the new blacklist query — they don't block bets, and the blacklist only checks for `is_allowed = false`.

2. **Existing millions of allow rows**: Database likely contains millions of rows (members × markets). The blacklist model means "no row = allowed," making most existing rows redundant. **Mitigation**: Plan a phased cleanup migration — first mark existing `is_allowed = true` rows as legacy, then batch-delete.

3. **BetService enforcement point is the ONLY gate**: Only `BetService::validateMemberPermission()` enforces policy. FrontendApi shows all markets regardless. If BetService logic is wrong post-migration, members can either be wrongly blocked or wrongly allowed. **Mitigation**: Add integration test covering positive (no row → bet allowed) and negative (is_allowed=false → bet blocked) cases.

### 5.2 Medium Risk

1. **Admin UI confusion**: The admin panel still shows "อนุญาต/ปิด" (allow/deny) toggle. After migration, `is_allowed = false` means "blocked" (blacklist). UI labels must be updated to avoid operator confusion.

2. **Bootstrap/rollout commands**: If accidentally run post-migration, `lotto:policy-bootstrap-members` and `lotto:policy-rollout-markets` will re-create massive allow rows, undoing the blacklist migration. **Mitigation**: Either remove these commands or add a safety gate.

3. **rollout_mode column drift**: `rollout_mode` exists in DB but not in LotteryMarket/LotteryGroup `$fillable` in the active Lotto package. The column is effectively read-only from the UI perspective (only settable via DB directly or the dead Lottobk controllers). This column may need cleanup or explicit handling.

### 5.3 Low Risk

1. **LottoAuditObserver skips member_lotto_market_policies**: Policy changes are not audited. This may be intentional but is worth noting for compliance.

2. **Legacy migration command**: `lotto:migrate-legacy-permissions` is likely one-shot and can be deprecated.

---

## 6. Required Implementation Sequence

### PR-01 (this audit) — DONE
- [x] Complete runtime entrypoint audit
- [x] Identify all creation, reading, enforcement points
- [x] Document affected files and risk analysis

### PR-02 (BetService enforcement flip + safety gates) — IMPLEMENTED
- **Branch**: `boa-245-pr-02-convert-policy-runtime-to-default-allow-blacklist`
- **Linear**: BOA-245
- **Date**: 2026-05-12
- **Source of truth**: Local working tree (not GitHub PR #81 snapshot)

Changes:
- `BetService::validateMemberPermission()` flipped from whitelist (`is_allowed=true` required) to blacklist (`is_allowed=false` blocks)
- `MemberMarketPolicyService`: 5 mass-row methods (`bootstrapForMember`, `bootstrapAllMembers`, `applyGroupRollout`, `applyMarketRollout`, `rolloutMarkets`) converted to no-ops with `Log::warning()`
- `LottoServiceProvider`: `member.created.after` event listener disabled (commented out)
- `BootstrapMemberMarketPoliciesCommand`: exits with deprecation info, returns SUCCESS
- `RolloutMemberMarketPoliciesCommand`: exits with deprecation info, returns SUCCESS
- `MigrateLegacyLottoPermissionsCommand`: deprecation warning added, command still functional

Tests added:
- `tests/Feature/Lotto/BetServicePermissionTest.php` — 5 tests covering blacklist enforcement
- `tests/Feature/MemberMarketPolicyServiceBootstrapTest.php` — updated for no-op behavior
- `tests/Feature/Lotto/MemberMarketPolicyRolloutCommandTest.php` — updated for deprecated command

Rollback:
- Revert `BetService::validateMemberPermission()` to query `is_allowed=true`
- Uncomment event listener in `LottoServiceProvider`
- Restore `MemberMarketPolicyService` mass-row methods from git history
- Mass-bootstrap members if needed (may be slow)

Risk: Medium — enforcement is the only bet gate. Legacy allow rows are no-ops. New members default to allowed.

Not done (out of scope for PR-02):
- No deletion of legacy `is_allowed=true` rows
- No admin UI label changes
- No `rollout_mode` column cleanup
- No Lottobk changes

### PR-03 (Admin UI updates)
- Update `MemberLottoPermissionTransformer.php` labels: "อนุญาต/ปิด" → "บล็อก/ปกติ" (or similar)
- Update `addedit.blade.php` form labels
- Consider adding confirmation dialog for blocking

### PR-04 (Database cleanup)
- Plan migration to mark legacy `is_allowed = true` rows
- Batch-delete redundant rows (millions potentially)
- Clean up `rollout_mode` column from groups/markets if unused
- Production SQL verification

### PR-05 (Dead code removal — optional)
- Remove or archive `packages/Gametech/Lottobk`

---

## 7. SQL Verification Checklist (Production)

**Safety notes before running any of these queries on production:**

- Run `EXPLAIN` before each query on large tables (`member_lotto_market_policies`, `lotto_markets`, `lotto_groups`).
- Avoid running global `COUNT(DISTINCT ...) NOT IN (...)` during peak hours — use indexed/batched verification instead.
- For large-table analysis, batch by `member_id` range or use a temporary summary table rather than scanning the full table in a single query.
- Verify that indexes on `member_lotto_market_policies` cover `(is_allowed)`, `(member_id)`, and `(market_id)` before running these queries.

```sql
-- 1. Count total policies (lightweight, safe for any time)
SELECT COUNT(*) FROM member_lotto_market_policies;

-- 2. Distribution of is_allowed (lightweight, safe for any time)
SELECT is_allowed, COUNT(*) FROM member_lotto_market_policies GROUP BY is_allowed;

-- 3. Members with ONLY allow rows (no deny rows)
-- WARNING: Heavy query on large tables. Run EXPLAIN first.
-- Prefer batched approach: iterate by member_id range, 10k members at a time.
SELECT COUNT(DISTINCT member_id) 
FROM member_lotto_market_policies 
WHERE is_allowed = 1 
AND member_id NOT IN (
    SELECT DISTINCT member_id FROM member_lotto_market_policies WHERE is_allowed = 0
);

-- 4. Members blocked (is_allowed = false) — lightweight, safe for any time
SELECT COUNT(DISTINCT member_id) 
FROM member_lotto_market_policies 
WHERE is_allowed = 0;

-- 5. Markets with rollout_mode set (lightweight, safe for any time)
SELECT rollout_mode, COUNT(*) FROM lotto_markets GROUP BY rollout_mode;
SELECT rollout_mode, COUNT(*) FROM lotto_groups GROUP BY rollout_mode;

-- 6. Verify table indexes (lightweight, safe for any time)
SHOW INDEXES FROM member_lotto_market_policies;
```

---

## 8. Rollback Concerns

1. **Rollback requires re-creating allow rows**: If we delete mass allow rows and need to rollback to whitelist model, we must re-bootstrap all members. This operation could take significant time (member_count × market_count).

2. **Event listener removal is permanent**: Once we remove the `member.created.after` listener, new members registered during the blacklist window won't have policy rows. Rollback would require backfilling.

3. **Migration down()**: The existing migration's `down()` drops the table entirely. In a rollback scenario, a new migration must be carefully designed.

---

## 9. Test Plan for PR-02+

### New Tests Required

| Test | Scenario | Expected |
|------|----------|----------|
| `test_member_without_policy_row_can_bet` | No row in member_lotto_market_policies | Bet succeeds |
| `test_member_with_deny_policy_cannot_bet` | Row exists with `is_allowed = false` | Bet blocked with exception |
| `test_member_with_legacy_allow_row_can_bet` | Row exists with `is_allowed = true` (legacy) | Bet succeeds (no-op row) |
| `test_new_member_no_auto_bootstrap` | Member created after PR-03 | No policy rows auto-created |
| `test_bootstrap_command_safety_gate` | Run bootstrap after blacklist migration | Command warns/exits |
| `test_rollout_command_safety_gate` | Run rollout after blacklist migration | Command warns/exits |

### Existing Tests to Update

- `tests/Feature/MemberMarketPolicyServiceBootstrapTest.php` — may need signature updates
- `tests/Feature/Lotto/MemberMarketPolicyRolloutCommandTest.php` — may need safety gate tests

---

## 10. Open Questions

1. **Admin-only blocking**: Should the admin be the ONLY way to create deny rows, or should there be automated blocking triggers (e.g., fraud detection)?

2. **Legacy allow row cleanup**: Should we batch-delete all `is_allowed = true` rows, or keep them as no-op records for audit trail?

3. **Lottobk dead code**: Should we delete `packages/Gametech/Lottobk` in this project or save for a separate cleanup PR?

4. **rollout_mode column**: Should we drop `rollout_mode` from `lotto_groups` and `lotto_markets` as part of this migration? It's effectively unused in the active codebase.

---

## 11. Grep Patterns Used

```
member_lotto_market_policies
MemberLottoMarketPolicy
is_allowed
bootstrapForMember
bootstrapAllMembers
applyGroupRollout
applyMarketRollout
rolloutMarkets
MemberMarketPolicyService
rollout_mode
policy_version
affect_existing_members
isManagedByMarketPolicy
validateMemberPermission
```

## 12. Links

- **Linear Project**: https://linear.app/boatjunior/project/1168lot-convert-member-lotto-market-policies-to-blacklist-269c4fe3e430
- **Linear Issue BOA-244**: https://linear.app/boatjunior/issue/BOA-244/pr-01-audit-current-whitelist-policy-usage-and-runtime
