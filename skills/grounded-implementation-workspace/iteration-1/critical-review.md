# Iteration 1 — Critical re-read for iteration-2

Not "is the skill good?" — it shipped. This is "where does it pay too much, get over-eager, or lose ROI as base models improve?"

## Verbosity tax (real but bounded)

**Token cost vs without_skill:**
- eval-1: with 76k / without 78k — basically equal on a tiny fix
- eval-2: with 99k / without 87k — +14% for a refactor
- eval-3: with 86k / without 78k — +10% even though with_skill produced no diff

**Latency cost:**
- with_skill 30–100% slower. Phase 2 grounding is the dominant cost (+150s on eval-2).

**Where the verbosity goes:**
- Self-audit checklist lists every section even when 4/8 are N/A (eval-1: dead config N/A, migration N/A, etc.)
- Spec extraction repeats spec verbatim verbatim — useful for reviewer context, but for tiny fixes it dominates the report
- Phase 2 step lists 8 sub-checks even when most don't apply

**Action for v2 of skill:**
- Self-audit: collapse N/A items into one line ("Migration / dead-config / silent-fallback: N/A — not touched")
- Quick Mode: add explicit phrase "if 5 of the 8 audit items are N/A, you're probably in Quick Mode territory — escalate only on the trigger list"

## Over-strict / false-positive risks

### Risk 1 — STOP rule is binary

`eval-3 with_skill` refused entirely because endpoint/config/idempotency don't exist. Correct outcome here. But what if the user wanted:
- A spike / proof-of-concept design proposal
- A "show me what this would look like, knowing infra is missing"
- A handoff doc for a future sprint

The current SKILL says STOP and report. There's no "STOP-but-also-sketch-a-grounded-design" mode. with_skill *did* produce two grounded options in the report, which is good. So the current behavior is actually fine — but worth surfacing in v2 as an explicit pattern: "STOP for code; OK to sketch options grounded in real conventions."

### Risk 2 — Scope creep through "consistency" framing

`eval-2 with_skill` proposed updating `isDrawActiveForCurrent` because it would silently disagree with the bulk path. Defensible — but technically expands PR-B scope. If a strict reviewer enforces "PR-B touches `upsertRiskCurrentRows` only", this could be a blocker by itself.

The skill says "no drive-by changes" but doesn't address "consistency-required changes." The Phase 2 call-site analysis can surface drift; Phase 4 ("strictly per plan") doesn't tell agent how to handle drift.

**Action for v2:** add a one-liner to Phase 4 — if call-site analysis surfaces a drift that's *required for correctness*, the agent must flag it explicitly to the user before bundling it; otherwise default is to leave it out.

### Risk 3 — "Method/class names not reliable" can over-fire

The strong wording about "naming drift" might push the agent to over-investigate well-named classes in cases where the naming *is* reliable. None of the 3 eval cases showed this, but on a long task it could produce 2–3× the necessary file reads. Watch for it in iteration-2.

## Where the skill's marginal value actually lives

Re-reading without_skill reports, the base model already does:
- Spec extraction with bullet points
- Self-audit-style risk notes
- Reality-checks ("HEAD already contains this fix")
- Even some hallucination self-flagging (eval-3 without_skill openly stated "I did NOT verify this exists")

The skill's *unique* value showed up in 3 specific places:
1. **Phase 2 STOP** — refusing to write fabricated code (eval-3)
2. **Phase 2 step 7 call-site analysis** — caught silent drift (eval-2)
3. **Phase 5 C2/C3** — articulated *why* the fabricated diff is wrong (dead config + silent fallback grep)

Everything else is being absorbed by base-model improvements. This is the most important finding from iteration-1.

## Implications for iteration-2

User is right that iteration-2 should pivot from "correctness baseline" to "ambiguity stress test." Concrete cases that force the skill's unique behaviors:

1. **Renamed symbol** — spec references `WalletService::deposit()` but actual class is `WalletDepositService` (or similar real renaming). Tests whether agent surfaces rename vs blindly uses the spec name. Phase 2 step 8 (git history) should fire.
2. **Stale doc** — domain doc claims behavior X, code does Y, tests assert Y. Tests Phase 2 step 6 (sibling tests as contract).
3. **Partially migrated config** — `config/foo.php` has key but no read site, AND member column has the value but isn't loaded. Tests Phase 5 C2 dead-config audit end-to-end, not just the easy cases.
4. **Deprecated fallback in middle of refactor** — code has `if ($legacy) { /* old path */ } else { /* new path */ }`, spec describes only new path, but old path still runs in production. Tests "git history check" + reluctance to remove without evidence.
5. **Spec contradicts implementation under deliberate intent** — variant of eval-2 conflict, but the implementation is the "right" one and spec is wrong. Tests whether agent recommends overriding spec, not just flagging it.
6. **Hidden invariant in tests only** — no doc, no comment, but a test asserts a behavior that any change would break. Phase 2 step 6 must fire to catch this.

For each, the *right* outcome is a specific reviewer-ready report, not a diff. That's the new evaluation lens for v2.

## Iteration-2 baseline check

When base models keep improving, run iteration-2 with two extra configs to validate the skill is still earning its tokens:

- **with_skill (current)** — full v1
- **without_skill** — same as today
- **with_minimal_skill** — only Phase 1 GROUND + Phase 5 SELF-AUDIT (drop Phases 2–4)
- **without_skill_but_pushy_prompt** — a 5-line prompt addendum that says "ground every symbol, don't fabricate, list call sites" — no full SKILL

If full-SKILL is not meaningfully better than minimal-SKILL or pushy-prompt, the skill is over-engineered and should be trimmed.

## What to ship now

- `grounded-implementation.skill` is shipped to `skills/dist/`
- Use it as-is for production
- iteration-2 happens when: (a) you observe a real reviewer rejection that the skill should have caught, OR (b) base model upgrades and you want to confirm the skill still earns its keep
