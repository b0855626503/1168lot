> สถานะ: SUPERSEDED
> วันที่: 2026-03-27
> โดเมน/เรื่อง: Lotto / PR Step By Step
> แทนแผนเก่า: ถูกแทนโดย docs/04_PLANS/2026-03-27_lotto-global-config-migration.md

# Lotto PR Step By Step Plan V3

## Patch Summary

This patch resolves final ambiguity in:

1. Deployment constraint scope
2. Backfill ambiguity handling

---

## 1. Deployment Constraint (Clarified Scope)

### REPLACE เดิม:

```text
Partial deployment is INVALID
```

### WITH:

```text
Partial deployment is INVALID FOR PR-08 + PR-09 ONLY.

Other PRs (PR-01 to PR-07, PR-10, PR-11) may be deployed independently.
```

---

## 2. Backfill Requirement (Ambiguity Handling Required)

### ADD ต่อท้าย PR-02:

```text
Ambiguity Report MUST be reviewed manually.

Even if payout coverage = 100%:

- All ambiguity rows MUST be reviewed
- Any incorrect selection MUST be corrected before cutover

Cutover is BLOCKED if:

- ambiguity rows are not reviewed
- ambiguity resolution is incomplete
```

---

## Final Enforcement Add-on

```text
Coverage = 100% is NOT sufficient.

Ambiguity MUST be explicitly resolved.
```

---
