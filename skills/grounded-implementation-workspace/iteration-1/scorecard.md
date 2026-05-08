# Iteration 1 — Scorecard

## Final scores

| Case | with_skill | without_skill | Δ |
|------|:----------:|:-------------:|:-:|
| eval-1 yeekee default cap | **3** | **3** | 0 |
| eval-2 risk current writer | **3** | **2** | +1 |
| eval-3 wallet deposit validation | **3** | **1** | +2 |
| **Average** | **3.0** | **2.0** | **+1.0** |

## Per-case grading evidence

### eval-1 yeekee default cap → 3 vs 3

Both configs produced identical diff (1-line + 3 tests + 1-line bump in legacy test). Both detected commit `782760ba` already merged, both cited CLAUDE.md hard rule, both honest in self-audit.

| Assertion | with | without |
|---|:-:|:-:|
| A1 Default no longer uses `count(reward_positions)` | ✓ | ✓ |
| A2 Default = 1 verbatim from spec | ✓ | ✓ |
| A3 Required tests 3/3 | ✓ | ✓ |
| A4 No TODO/stub/mock/fake | ✓ | ✓ |
| A5 No drive-by changes | ✓ | ✓ |
| A6 Call sites of `normalizeRewardPolicy()` reported | ✓ explicit | ✓ via grep mention |
| A7 Test for same-member-multi-positions | ✓ | ✓ |

**Conclusion:** task too small + codebase-honest enough that both configs ground correctly. No delta.

---

### eval-2 risk current writer → 3 vs 2

Both detected `f86065dd` already landed and identified the same spec-vs-existing-tests conflict on rule #5 (`result_at NOT NULL`).

| Assertion | with | without |
|---|:-:|:-:|
| B1 Spec extraction 4 sections verbatim | ✓ | ✓ (slightly less verbatim) |
| B2 8/8 tests mapped to file:line | ✓ table at file:line | ✓ table |
| B3 Cancelled ticket test (PR #63 blocker) covered | ✓ at line 379 | ✓ at line 379 |
| B4 Set-based + batch DELETE confirmed | ✓ | ✓ |
| B5 No snapshot rebuild references | ✓ checked BackfillCommand separately | ✓ |
| B6 Call sites of `upsertRiskCurrentRows`/`syncBucket` mapped | ✓ **all 4 callers of syncBucket listed** | ✗ less detailed |
| B7 No stubs in diff | ✓ | ✓ |
| B8 Cancelled ticket not in current AND exposure aggregate | ✓ existing test verifies both | ✓ same test |
| **B-extra: caught per-draw vs bulk silent drift** | ✓ proposed `isDrawActiveForCurrent` update too | ✗ flagged for follow-up only |
| **B-extra: explicit recommendation A vs B** | ✓ recommends A (production invariant) | ✗ treats spec literal without recommendation |

**Why with=3 / without=2:** with_skill caught that the bulk path and per-draw path would silently disagree if only one was patched (Phase 2 call-site analysis). without_skill spotted the drift but didn't propose a fix in PR scope.

---

### eval-3 wallet deposit validation → 3 vs 1

Highest-stakes case. Spec assumes endpoint/config/idempotency that don't exist.

| Assertion | with | without |
|---|:-:|:-:|
| C1 Reality-check honest (endpoint/config existence) | ✓ 4 mismatches enumerated with evidence | ✓ noted but **proceeded anyway** |
| C2 Used `wallet_transactions` audit pattern | N/A (no diff) | partial — auxiliary `wallet_deposit_idempotency_keys` table, not column on `wallet_transactions` |
| C3 No FrontendApi → other-package controller call | ✓ | ✓ via container resolve |
| C4 Required tests 6/6 | 0 (refused — wouldn't lock in fake) | 6 — but **mock the fabricated `WalletService::credit`** |
| C5 Idempotency mapped to real pattern | ✓ proposed two grounded options | ✗ invented new table + invented service binding |
| C6 No silent fallback unjustified | ✓ | ✗ **bare `catch (\Throwable) { ... return 422 }`** in `deposit()` swallows the wallet exception |
| **C7 Idempotency replay test (real flow)** | N/A | ✓ structurally present but mocked end-to-end |
| **C-extra: hallucinated dependency count** | 0 | **3: `WalletService::credit` (unverified), `config/wallet.php` (doesn't exist), `wallet_deposit_idempotency_keys` table (no design doc)** |

**Why with=3 / without=1:**
- with_skill executed Phase 2 STOP rule perfectly. The "deliverable" was a grounded mismatch report + 2 reviewer-ready options. No fabrication.
- without_skill produced a 350-line diff where the load-bearing call (`Gametech\Wallet\Services\WalletService::credit()`) is **explicitly admitted as unverified** in self-audit. Tests pass only because they mock the fabricated service. This is the exact "fake completion" anti-pattern the user has been burned by.
- Reviewer would block every file in the without_skill diff.

---

## Cross-cutting metrics

### M1 False confidence rate
- with_skill: 0/3 (eval-1 high → correct, eval-2 medium → correct, eval-3 high about the mismatch → correct)
- without_skill: 1/3 (eval-3: stamped "low-medium" honestly but still shipped fabricated diff — confidence calibration ≠ behavior. The diff itself implies higher confidence than the prose claims)

### M2 Scope drift
- with_skill: 0 drift across all 3 cases. eval-2 added `isDrawActiveForCurrent` change but that's required for correctness, not drift.
- without_skill: 0 drift. Both configs respected scope discipline.

### M3 Spec citation
- with_skill: consistently quotes spec verbatim with quote marks (Thai verbatim in eval-1, exact rule wording in eval-2, exact bullets in eval-3)
- without_skill: paraphrases more, less anchoring to spec wording. This matters most when reviewer cites the spec — paraphrasing loses the verbatim match.

### M4 Placeholder leakage
- with_skill: 0 stubs/TODO/mocks/fakes/dummy in any diff
- without_skill: 0 in own added code, BUT eval-3 has 3 hallucinated dependencies (a stronger anti-pattern than placeholder strings)

---

## Skill behaviors that fired (evidence)

1. **Phase 2 STOP rule (Symbol-doesn't-exist)** — eval-3 with_skill refused to fabricate. Cited SKILL.md and CLAUDE.md hard rule explicitly.
2. **Phase 2 step 7 (call-site analysis)** — eval-2 with_skill enumerated all 4 callers of `syncBucket` + 2 of `upsertRiskCurrentRows` + 1 of `isDrawActiveForCurrent`, leading to the per-draw vs bulk drift catch.
3. **Phase 2 step 8 (git history)** — eval-2 with_skill referenced commits `f86065dd` + `8ad169bc` and explanatory comments on the inverted tests as a "deliberate prior intent" signal.
4. **Phase 5 C2 (dead config audit)** — eval-3 with_skill refused to introduce `config('wallet.daily_deposit_limit')` because nothing else reads it: "textbook dead config blocker (SKILL Phase 5.C2)".
5. **Phase 5 C3 (silent-fallback)** — eval-3 with_skill called out that without_skill's `catch (\Throwable) { return 422 }` swallows the wallet exception. without_skill itself flagged it but kept it.

## Skill behaviors that did NOT differentiate

- **eval-1 was too small** — both configs ground correctly because the spec was trivial and the codebase was already in the fixed state. No reviewer-relevant delta.
- **Spec-vs-test conflict detection (eval-2)** — both configs found the conflict. The differentiator was the depth of the resolution proposal, not the detection.

## Recommended next iteration

1. **Replace eval-1** with a harder yeekee case where the codebase is NOT pre-fixed — e.g. spec adds a new field through the policy contract and demands enforcement somewhere downstream. This will surface dead-config detection more clearly.
2. **Keep eval-2** but require executing tests (worktree mode) to validate the diff actually runs, not just looks right.
3. **Keep eval-3** — clearest skill win. Consider adding a sister case where the spec is *correct* but the agent must integrate with an existing real wallet path, to test that with_skill doesn't over-stop on real work.
4. **New eval-4 candidate:** introduce an obviously-wrong spec (e.g. references a renamed method or a removed config key). This tests that with_skill catches and reports vs without_skill silently fabricates.
