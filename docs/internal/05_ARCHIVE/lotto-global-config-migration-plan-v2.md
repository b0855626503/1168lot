# Lotto Global Config Migration Plan V2 (Archive)

## Overview

This document defines the **final, decision-locked migration plan** for transitioning `packages/Gametech/Lotto` to a **global configuration model**.

This version resolves all prior ambiguities and MUST be followed strictly during implementation.

---

## Locked Decisions

### 1. Permission Model

```
Member permission is retained as ALLOW/DENY ONLY.
All member-specific betting configurations are removed.
```

### 2. Config Model

* payout → global only
* min_bet → global only
* max_bet → global only
* max_per_number → global only

### 3. Snapshot Policy

```
Global config changes apply to NEW draws only.
Existing draws use snapshot at open time.
```

### 4. Cutover Strategy

```
Two-step Soft Cutover (MANDATORY)
- dual-read (shadow)
- audit gate
- runtime switch
- cleanup
```

---

## Target Architecture

### Global Config

```id="lq9yce"
LotteryMarketBetSetting
- payout
- min_bet
- max_bet
- max_per_number
```

### Draw Snapshot

```id="21mx5y"
LottoDrawBetSetting
- payout
- min_bet
- max_bet
- max_per_number
```

### Permission

```id="6m8z1b"
MemberLottoPermission
- allow (boolean)
```

---

## Forbidden Patterns

The following MUST NOT exist in runtime:

* member payout override
* member min/max override
* member rate plan usage
* fallback chain (member → group → default)
* any call to:

  * MemberLottoSetting
  * LottoRatePlan*
  * member-based config resolver

---

## Mandatory Phase: Payout Migration (Critical Gate)

### Problem

Current system resolves payout from:

* rate plans
* member override

Target system requires:

* payout in global config + snapshot

### Tasks

1. Add column:

  * `payout` → `lotto_market_bet_settings`
  * `payout` → `lotto_draw_bet_settings`

2. Backfill payout

Source priority (LOCKED):

```
member override > rate plan > default
```

3. Generate ambiguity report

Cases:

* no payout found
* multiple conflicting values

4. Block cutover if:

* payout coverage < 100%

### Acceptance Criteria

* BetService reads payout ONLY from:

  * draw snapshot OR
  * global setting (during snapshot)
* No rate plan usage remains

---

## Phase Plan

---

## Phase 0 — Audit & Baseline

* audit all member override usage
* map config resolution
* baseline tests must pass

---

## Phase 1 — Schema Preparation

* add payout fields
* ensure snapshot schema complete
* mark legacy tables as deprecated

DO NOT:

* drop any table

---

## Phase 2 — Global Resolver

Create:

* resolveMarketSetting()
* resolveDrawSnapshot()

Refactor read paths:

* BetService
* DrawService
* ExposureService

---

## Phase 3 — Draw Snapshot Alignment

* snapshot payout/min/max/max_per_number on draw open
* BetService must use snapshot only

---

## Phase 4 — Bet Flow Rewrite

Validation order:

1. permission allow
2. draw open
3. market active
4. bet type valid
5. number block
6. min/max (snapshot)
7. max_per_number (snapshot)
8. exposure
9. persist

Preserve:

* DB::transaction
* lockForUpdate

---

## Phase 5 — Admin / ACL / Route Sync (Atomic Release)

### MUST be deployed together

* routes
* admin menu
* ACL
* controllers
* views

### Changes

REMOVE:

* member_rate_plans
* member override UI

KEEP:

* member permission (allow/deny)

---

## Phase 6 — API Alignment

* remove member-specific config fields
* keep payload shape stable
* update messaging

---

## Phase 7 — Cutover (MANDATORY FLOW)

### Step 1 — Dual Read (Shadow Mode)

* run:

  * legacy resolver
  * global resolver
* compare:

  * payout
  * min/max
  * max_per_number
* log mismatch

---

### Step 2 — Audit Gate

Switch allowed only if:

* payout coverage = 100%
* mismatch = 0 (or approved threshold)
* no member depends on override

---

### Step 3 — Runtime Switch

* enable global-only logic
* disable legacy path (not removed)

---

### Step 4 — Cleanup Release

* remove legacy code
* drop deprecated tables

---

## Bootstrap Behavior (Locked)

Current:

* member.created.after bootstraps config

New:

```
Keep bootstrap ONLY for allow/deny permission.
Remove any betting config assignment.
```

---

## Dependency Constraints

The following MUST be updated in SAME release:

* admin routes
* admin menu
* ACL config
* controllers
* frontend bindings

Partial deployment is NOT allowed.

---

## Test Plan

### Required Coverage

#### Config

* global config applies to all users
* snapshot correctness

#### Bet

* min/max validation
* max_per_number
* blocked number

#### Exposure

* boundary
* concurrency
* race condition

#### Settlement

* payout correctness
* reconciliation

#### Admin

* member override UI removed
* permission UI works

#### API

* no contract break

---

## Rollout Strategy

### Release 1

* schema + payout backfill + resolver

### Release 2

* dual-read shadow

### Release 3

* runtime switch

### Release 4

* cleanup

---

## Rollback Plan

* revert runtime switch
* keep legacy data intact
* schema remains backward-compatible

---

## Definition of Done

* global config only
* payout from snapshot/global only
* no member override logic
* permission = allow/deny only
* tests pass
* production stable

---

## Final Enforcement Rule

If any part of the system still depends on:

* member override
* rate plan
* fallback config

→ Migration is NOT complete and must NOT be released.

---
