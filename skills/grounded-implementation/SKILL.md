---
name: grounded-implementation
description: Use this skill for ANY implementation, bug fix, refactor, or "make this correct" task in the 1168lot Laravel codebase — whenever the user asks to "fix", "implement", "add", "change", "patch", "review and patch", "follow this plan", "continue PR", "finish TODO", "address review", or pastes a Linear/PR spec. Forces a 5-phase workflow (GROUND → MAP → PLAN → IMPLEMENT → SELF-AUDIT) that grounds every change in real code, reads sibling tests as contracts, locates all call sites before changing behavior, prevents stub/placeholder/hallucinated APIs, audits dead config and migration safety, and runs a reviewer-style self-audit against the most common blocker patterns observed in this repo's PR history. Use this even for "small" fixes and continuation work — past PRs that looked small ping-ponged with reviewers 3+ rounds because of skipped grounding.
---

# Grounded Implementation Workflow

## Why this skill exists

PRs in this repo ping-pong with reviewers because agents skip grounding: they assume signatures, miss required tests listed in the spec, leave config fields resolved-but-unused, and add stub/TODO code that masquerades as a real implementation. Each rejection burns a full review cycle.

This skill forces the work through five phases. Don't skip any. The audit at the end mirrors what the reviewer will check — passing it locally is what stops the ping-pong.

## When to apply

Apply for **every** implementation task in `/home/boat/projects/1168lot`, including:
- "แก้ตรงนี้ให้หน่อย" / "ทำตามแผนนี้"
- Pasted Linear ticket or PR description
- Bug fix that looks one-line
- Anything touching: wallet, lotto settlement, dashboard risk, yeekee, auto-result, payment, realtime, migrations

Skip only for: pure questions, code reading, status checks, no-edit explorations.

---

## Phase 1 — GROUND (extract the spec verbatim)

**Goal: lock down what success looks like before touching code.**

1. Read the request as given. If a Linear/PR/issue link is provided, fetch it (`gh pr view <n> --repo <repo> --json body,comments`). Don't summarize from memory.
2. Extract these into a working note (in your head or a scratchpad — do NOT write a markdown file unless asked):
   - **Business rules** — the exact wording of every rule, default, threshold, edge case. Quote them.
   - **Required tests** — every test scenario the spec lists. Number them.
   - **Forbidden patterns** — anything the spec says "ห้าม", "never", "must not", "removed after BOA-XXX". List them.
   - **Files to inspect** — explicit file paths the spec names.
   - **Config keys / contracts** — every key name mentioned, including alternate names (e.g. `reward_config.enabled` AND `reward_enabled`).
3. If the spec is ambiguous on any of the above, **ask the user before proceeding**. A wrong assumption here costs a review cycle.

**Anti-pattern to avoid:** skimming the ticket and starting to code. Reviewers cite spec wording verbatim when rejecting — you need that wording in front of you.

---

## Phase 2 — MAP (every symbol must exist in real code)

**Goal: zero hallucinated APIs. Every symbol the spec mentions must be located in the actual codebase.**

For each symbol/method/class/config key from Phase 1:

1. Use Grep / Read to find it. Record `file:line` for each.
2. If a symbol the spec references **does not exist** in the codebase: STOP. Report the mismatch to the user (per `CLAUDE.md`: "code ไม่ตรง doc → report ก่อนแก้"). Do not invent it.
3. For methods you'll call: open the actual definition. Confirm signature, return type, side effects.

   > **Method/class names in this repo are not reliable contracts.** Read the implementation before assuming behavior. Naming drift is common in a Laravel codebase this size — a service called `RewardCalculator` may not actually calculate rewards, and a `policy` field may not actually be enforced.

4. For Eloquent models / DB columns: confirm the column exists in the migration AND the cast/`$fillable`. Use `database-schema` or read the migration.
5. For config keys: confirm where they're read AND where they're written. A field that's only read but never written by any normal flow is a red flag (see Phase 5 — dead config audit).
6. **Read sibling tests before implementing.** Existing tests are part of the contract — often more authoritative than docs in this repo. For yeekee edge cases, dashboard snapshot assumptions, wallet transaction statuses, and settlement invariants, the tests encode invariants that are not written down anywhere else. Open at least the test file directly covering the class you're changing, and any test referenced from the spec.
7. **Locate all call sites of any method/contract you plan to modify.** Before changing a signature, return shape, default value, nullable behavior, or fallback path: grep for every caller. A spec-correct change that breaks an unexamined caller is the #1 way to ship a regression. If a caller exists in a domain you don't fully understand, surface it to the user before proceeding.
8. **Inspect git history when behavior looks surprising.** If a piece of code looks inconsistent, redundant, or "wrong" — run `git log -p --follow <file>` or `git blame` on the relevant lines before changing it. Many odd-looking branches in this repo (lotto settlement guards, wallet status fallbacks, dashboard scope filters) exist because of a production incident or a deliberate rollback. Removing them blindly re-introduces the original bug. If the history references a past incident or PR that explains the behavior, link to it in your plan.

**Self-check before leaving Phase 2:** can you point to a `file:line` for every symbol in your plan, every existing test that covers it, and every caller of methods you're modifying? If no, go back.

---

## Phase 3 — PLAN (write the checklist, get approval if scope is non-trivial)

**Goal: a checklist where every spec rule maps to a concrete code change AND a test.**

Produce (in chat, concise):

```
Files to change:
- path/to/File.php — reason
- tests/.../FooTest.php — reason

Spec rules → enforcement:
- Rule 1 ("default = 1, never count(positions)") → File.php:123, line will become `max(1, $configured ?? 1)`
- Rule 2 (...) → ...

Required tests → test methods:
- Spec test #1 ("member ยิงติด 2 positions → จ่าย 1") → test_member_two_positions_pays_one()
- Spec test #2 → ...

Forbidden patterns → verification:
- "no reference to lotto-risk-current-backfill in rollback" → grep -r "lotto-risk-current-backfill" docs/ tests/ → expect 0 hits

Out of scope (explicit non-goals):
- ...
```

For multi-file or risk-domain changes (wallet/settlement/migration), confirm with the user before implementing. For obvious 1-line fixes the user already described, skip approval and proceed.

**No drive-by changes — non-negotiable.** Bundling unrelated cleanup, refactors, renames, or "while I'm here" fixes into this PR is forbidden unless the user explicitly requested them. If you spot something worth fixing, note it for the user separately — do not bundle it. Reviewers reject bundled PRs because the diff stops being reviewable.

---

## Phase 4 — IMPLEMENT (strictly per plan)

1. Make exactly the changes in the plan. No more, no less.
2. Follow existing code style in sibling files. Use Pint formatting (`vendor/bin/pint --dirty --format agent` at the end).
3. Use `php artisan make:*` for new files (per Laravel rules in CLAUDE.md).
4. **Do not write `// TODO`, `// FIXME`, empty method bodies, or `return null;` placeholders.** If a method needs to exist but has no real implementation yet, that means the plan was wrong — go back to Phase 3 and split the work or talk to the user.
5. For each new field in a config/policy contract: trace it from where it's read to where it's *enforced*. If `resolveX()` returns a value that the caller ignores, the contract is broken (PR #65 blocker pattern).

---

## Phase 5 — SELF-AUDIT (run this before reporting done)

This is the highest-leverage phase. Run through every item. Each one corresponds to a real reviewer rejection in this repo's history.

### A. Spec coverage

- [ ] Re-read the original spec. For each numbered test scenario: does a test method exist for it? Count them. (PR #63 was rejected for skipping 1 of 8 required tests.)
- [ ] For every business rule quoted in Phase 1: which line enforces it? Can you point to it?
- [ ] For every alternate config key name (e.g. `enabled` vs `reward_enabled`): is each one handled? (PR #73 blocker.)

### B. No stubs / placeholders / hallucinations

Run these greps on your diff (or on changed files):

```bash
grep -nE "TODO|FIXME|XXX|placeholder|stub" <changed files>
grep -nE "mock|fake|dummy|temp" <changed files>
grep -nE "return null;\s*//|return \[\];\s*//" <changed files>
```

For every match in your own added code: justify it or remove it. The user has been burned by mock/stub/fake files masquerading as real implementations — this is non-negotiable. Some agents avoid `TODO` but use names like `tempReward()`, `dummyHandler()`, or `fakePolicy()` to slip placeholder logic past review. These count too.

### C. Contract enforcement (resolved-but-ignored fields)

For each field your code newly reads from config/policy/request:
- [ ] Is it actually used downstream, or just resolved into a variable that's discarded?
- [ ] If it has a default, does the default match the spec? (PR #74 blocker: default was `count(positions)`, spec said `1`.)
- [ ] If it has alternate names: are all of them handled? Tested?

### C2. Dead config audit (set-but-not-loaded / loaded-but-not-set)

For each config field your code newly reads OR writes:
- [ ] Can this field actually be **set** by a normal admin/runtime flow? (Form, seeder, env, migration default — point to the place.)
- [ ] Is it **persisted** correctly? (Column exists, cast is right, not stripped by `$guarded`/`$hidden`.)
- [ ] Is it **loaded** at the right moment in the request lifecycle? (Not lost across cache layers, not overwritten by a later normalize step.)
- [ ] Is it **enforced** end-to-end? (Read → applied to behavior, not just stored in a property and forgotten.)

A field that fails any of these four steps is dead config — it looks like a knob but does nothing. This repo has a history of config drift; reviewers will catch it. Map every new field through all four stages before declaring done.

### D. Architecture / forbidden patterns

- [ ] Grep the diff for any forbidden command/path/pattern listed in Phase 1. Expect 0 hits.
- [ ] Does the change cross a package boundary that's forbidden? (`FrontendApi` must not call other packages' controllers per CLAUDE.md.)
- [ ] Does it touch `wallet_transactions` directly without going through the audit-of-truth pattern?
- [ ] Does it bypass the lotto state machine?

### C3. Silent-fallback audit

Silent fallbacks mask bugs and are a common reviewer rejection. They look like "robust" code but they swallow signal that downstream code (and ops) needs.

Run on changed files:

```bash
grep -nE "catch \(.*\)\s*\{|return \[\]|return collect\(\)|return null;|\?\? \[\]|\?\? null" <changed files>
```

For every match in your added code:
- [ ] Is the fallback **intentional** and documented (in code comment or PR body) with the failure mode it's covering?
- [ ] Does it **log** the failure with enough context (ids, payload keys, exception class) for ops to trace it?
- [ ] Is there a test that proves the fallback path runs only when expected, not as a generic catch-all?

A bare `catch (\Throwable $e) { return []; }` with no log and no test is a reviewer blocker. Fix or justify before declaring done.

### D2. Migration safety (if the diff includes any migration)

Migrations in this repo touch high-risk tables — `lotto_dashboard_risk_*`, `wallet_transactions`, `lotto_*`. A bad migration is the worst kind of regression because it isn't easily revertable in production.

- [ ] **Rollback safety** — `down()` actually reverses `up()` cleanly, or rollback is explicitly documented as not supported with rationale.
- [ ] **No destructive drops without backup/migration note** — dropping a column or table on a populated table requires explicit user confirmation and a documented data-preservation step. Never drop silently.
- [ ] **Indexes reviewed** — added indexes for new query patterns; removed indexes only after confirming no caller relies on them. For large tables, consider `algorithm=inplace` / `lock=none` (MySQL) or document the lock implication.
- [ ] **Large-table impact considered** — if the table is > ~100k rows in production, an `ALTER TABLE` may lock for minutes. Document the impact, batch the change, or schedule with the user.
- [ ] **Dialect compatibility** — guard `information_schema` lookups for non-MySQL test drivers (PR #73 had this blocker).
- [ ] **Default values** — new NOT NULL columns must have a sensible default or be added in two phases (nullable → backfill → NOT NULL).

If anything in this checklist is uncertain, escalate to the user before running the migration anywhere shared.

### E. Side-effect safety

- [ ] If you reused a shared query builder / base scope: did you `(clone $base)` before mutating? (PR #72 blocker.)
- [ ] Added `whereNotNull` / status guards where the spec implies (e.g. `member_id`, `status='active'`)? (PR #72 blocker.)
- [ ] No new static-property mutations (Octane rule from CLAUDE.md).

### F. Tests actually pass

- [ ] Run the affected tests with a filter:
  ```
  php artisan test --compact --filter=<test or class>
  ```
- [ ] Don't claim done if any failed. Don't `--no-verify` past hooks.

### G. Docs / memory consistency (per repo Hard Rules)

- [ ] If behavior changed: which doc page mentions this behavior? Update it (or report the mismatch).
- [ ] If you added a new contract/config: is it discoverable from `docs/internal/01_SYSTEM/system_map.md` or the relevant domain note?

---

## Reporting back

Use this structure when reporting completion:

```
Done — <one-line summary>

Files:
- path:line — what
- ...

Spec coverage:
- Rule 1 → enforced at file:line, tested in TestClass::test_method
- Rule 2 → ...

Self-audit:
- Stubs/TODOs in my diff: 0
- Required tests covered: 8/8
- Forbidden patterns: grep clean
- Tests run: <command>, all passing

Out of scope (noted, not bundled):
- ...
```

Concise. Reviewer-friendly. The structure mirrors what they'll check anyway.

---

## When things go sideways

- **Spec contradicts code reality:** stop and report. Don't guess the user's intent.
- **A symbol from the spec doesn't exist:** stop and report. Maybe it was renamed; the user needs to know.
- **You realize mid-implementation that the plan was wrong:** stop, update the plan in chat, get re-confirmation. Do not silently expand scope.
- **A test is hard to write:** that's usually a sign the design is wrong, not that the test should be skipped. Ask.

---

## Quick mental checklist (for inline work without full ceremony)

For genuinely tiny changes (1-3 lines, no new logic), the full 5 phases are overkill. Compress to:

1. **Locate** — grep / read the exact line(s).
2. **Confirm spec match** — the change matches the spec wording.
3. **Test** — add or update a test, run it.
4. **Audit** — no TODO/stub, contract still enforced, no drive-by.
5. **Report** — one-line summary + file:line.

### Always escalate to the full workflow if the change touches ANY of these

In this repo, "1-3 line fix" is rarely actually 1-3 lines once the full impact is understood. The following triggers force the full 5-phase workflow regardless of how small the diff looks:

- **Defaults or fallbacks** — changing a default value, fallback path, or normalize step (PR #74 was a default-value change that ping-ponged.)
- **Config keys / policy contracts** — anything in `*_config`, `*_policy`, or that resolves a contract.
- **Query scopes / shared query builders** — base queries used by multiple callers (PR #72 missed `clone`).
- **Settlement, risk, wallet, lotto state machine** — domain-critical paths.
- **Shared helpers / traits / base classes** — by definition affect callers you haven't read.
- **Shared enums / constants / status maps** — changing a value or adding a case fans out across every match/switch/comparison in the codebase. Always grep all usages first.
- **Migrations** — see D2 above. Always full workflow.
- **Anything that crosses a package boundary** — `FrontendApi` ↔ other packages.

If you find yourself reaching for Quick mode on any of these, stop and run the full workflow. Most blocker PRs in this repo started with "แก้นิดเดียว".

If during the compressed flow you find the change is bigger than expected, escalate immediately.
