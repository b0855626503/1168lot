> สถานะ: PENDING
> วันที่: 2026-03-27
> โดเมน/เรื่อง: Lotto / Global Config Migration
> แทนแผนเก่า: docs/internal/05_ARCHIVE/lotto-global-config-migration-plan-v2.md

# Lotto Global Config Migration Plan

## Overview

This document defines the **final execution plan (ambiguity-free)** for Lotto global config migration.

All wording has been normalized to eliminate interpretation risk.

---

## Critical Enforcement

```text
PR-08 (Runtime Switch) and PR-09 (Admin/ACL/Route Sync) MUST be deployed together.
Partial deployment is INVALID.
```

---

## PR-01 Schema

Add column:

* payout → lotto_market_bet_settings
* payout → lotto_draw_bet_settings

---

## PR-02 Backfill

Deterministic rule:

```text
1. filter active records only
2. order by:
   - updated_at DESC
   - id DESC
3. select FIRST record
```

Generate report:

* success
* ambiguity
* missing

```text
Cutover is BLOCKED if payout coverage < 100%
```

---

## PR-03 Resolver

Create:

* LottoConfigResolver

Methods:

* resolveDrawSnapshot
* resolveMarketSetting
* resolvePayout
* resolveLimits

(No runtime usage yet)

---

## PR-04 Snapshot

DrawService must snapshot ALL fields:

```text
- payout
- min_bet
- max_bet
- max_per_number
```

---

## PR-05 Shadow Mode (Dual Read)

Run both:

* legacy resolver
* global resolver

Compare EXACT fields:

```text
- payout
- min_bet
- max_bet
- max_per_number
```

Log mismatch for each field separately.

---

## PR-06 Permission Migration

Migrate:

* MemberLottoPermission → member_lotto_market_policies

Runtime rules:

```text
- Use member_lotto_market_policies ONLY
- No fallback to legacy tables
```

Update:

* controllers
* datatables
* views

---

## PR-07 Audit Gate

ALL conditions must pass:

```text
- payout coverage = 100%
- mismatch = 0
- no member depends on override
```

---

## PR-08 + PR-09 (ATOMIC DEPLOY)

### MUST deploy together

Includes:

* BetService runtime switch
* Admin routes update
* Admin menu update
* ACL config update
* Controllers + views sync

---

## Validation Order (FINAL)

```text
1. load draw
2. validate draw is open
3. check permission (allow/deny)
4. validate market active
5. validate bet type
6. validate number block
7. validate min_bet / max_bet
8. validate max_per_number
9. validate exposure
10. persist ticket
```

---

## PR-10 API Alignment

* Remove member override fields
* Keep response structure stable

---

## PR-11 Cleanup

Remove explicitly:

```text
- MemberLottoPermission
- LottoRatePlan
- LottoRatePlanItem
- any LottoRatePlan* related logic
```

Drop deprecated tables in final phase only.

---

## Deployment Rule

```text
- PR-08 + PR-09 MUST be atomic
- Audit Gate MUST pass before switch
- No partial rollout allowed
```

---

## Final Condition

```text
If ANY runtime path still depends on:

- member override
- LottoRatePlan*
- fallback config chain

→ deployment MUST be blocked
```

---
