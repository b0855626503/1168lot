# Final Report — eval-2-risk-current-writer — with_skill

## Task summary

Refactor `DashboardSummarySyncService::upsertRiskCurrentRows()` to be set-based and self-cleaning per BOA-230 PR-B, with 8 named test scenarios. **Output is a proposed diff only — no source files modified.**

**Critical finding (Phase 2 — GROUND/MAP):** The work is largely already done. Commit `f86065dd [PR-B][BOA-230] Make lotto risk current writer correct and self-cleaning` (Wed May 6 17:56:26 2026 +0700) and follow-up `8ad169bc [PR-B][BOA-230] Add cancelled-ticket zero-risk test (reviewer fix)` already landed on `main`. Current code at `app/Services/Dashboard/DashboardSummarySyncService.php:782-907` is set-based, classifies via one `whereIn('id', $roundIds)->whereNotIn('status', $invalidDrawStatuses())` SELECT, batch-deletes invalid-draw rows via `whereIn('round_id', $invalidRoundIds)`, and per-key-deletes zero-risk rows.

**The remaining gap is exactly one rule from the spec, and it is a hard contradiction with two existing tests authored on the same PR.** Per `CLAUDE.md` Hard Rules ("If code and docs mismatch, report it before changing behavior"), this is reported below before proposing any change.

## Spec extraction

### Business rules (verbatim)
- **Valid draw**: `lotto_draws.status IN ('draft','open','closed') AND result_at IS NULL`
- **Invalid draw**: `status='resulted' OR result_at IS NOT NULL OR draw missing OR defensive statuses (cancelled, void, refunded, no_result, disabled)`
- **Meaningful risk**: `stake_total > 0 OR payout_if_hit > 0 OR liability > 0`
- Valid + meaningful → upsert into `lotto_dashboard_risk_current`
- Valid + zero risk → delete existing row by full composite key `(web_code, market_id, round_id, bet_type, number)`
- Invalid draw → batch delete all current rows for that `round_id`

### Required tests (numbered 1–8)
1. Set-based no N+1 vs `lotto_draws` (seed 25 distinct `draw_id`s, assert ≤ 2 SELECT queries against `lotto_draws`).
2. Valid open draw + meaningful risk → upserted.
3. Valid open draw + zero risk → existing row deleted by composite key.
4. Resulted draw → batch deleted.
5. Draw with `result_at NOT NULL` (any status) → batch deleted.
6. Defensive extended statuses (cancelled/void/refunded/no_result/disabled) → batch deleted.
7. Missing draw row in `lotto_draws` → batch deleted.
8. Cancelled ticket exposure (active draw, ticket status=cancelled, prior current row → cleaned).

### Forbidden patterns
- ห้ามใช้ snapshot fallback / rebuild current from snapshot (forbidden after BOA-228).
- ห้าม recommend `dashboard:lotto-risk-current-backfill` as rollback path.

### Files named in spec
- `app/Services/Dashboard/DashboardSummarySyncService.php`
- `tests/Unit/Core/DashboardSummarySyncServiceTest.php`

## ⚠️ Spec-vs-code conflict (must be resolved by user before merging)

Spec rule "Invalid draw = `result_at IS NOT NULL`" **directly contradicts** two existing tests authored on the same BOA-230 PR-B branch:

- `tests/Unit/Core/DashboardSummarySyncServiceTest.php:236-251` — `test_current_writer_keeps_open_draw_even_when_result_at_is_pre_scheduled`. Asserts: `status='open', result_at='2026-05-05 12:00:00'` → row IS upserted (count=1).
- `tests/Unit/Core/DashboardSummarySyncServiceTest.php:253-267` — `test_current_writer_keeps_closed_draw_even_when_result_at_is_pre_scheduled`. Asserts: `status='closed', result_at='2026-05-05 12:00:00'` → row IS upserted (count=1).

Both tests carry explanatory comments encoding a deliberate production invariant:

> "Production data can pre-schedule `result_at` while draw is still open/closed. Current risk must remain active until status becomes resulted."
> "Closed-but-not-resulted draws can still carry liability until settlement."

**This is a real domain rule, not a test mistake.** In production, `result_at` is set at scheduling time (the time the draw will be drawn), not at the time the result is recorded; the lifecycle gate is `status='resulted'`. Treating any non-null `result_at` as "invalid" would prematurely wipe live current rows for every open/closed draw whose draw time was pre-scheduled — a regression with broad blast radius.

**Recommendation:** confirm with the user which invariant is correct. Two viable resolutions:

- **(A) Honor the existing production invariant.** Treat the spec rule as imprecise wording: "invalid = `status IN ('resulted', ...)`" (keep current behavior). Close the spec gap by documenting that `result_at` is *not* a lifecycle signal in this codebase. **Required test #5 then has no test counterpart and should be re-scoped to "Resulted draw deletes regardless of result_at value", which is already covered by `test_current_writer_deletes_existing_row_for_resulted_draw`.**
- **(B) Honor the spec literally.** Add `result_at IS NULL` filter to `upsertRiskCurrentRows()` AND remove/invert the two existing tests. This re-introduces the regression those tests were written to prevent — must be explicitly approved by the user, with a domain owner sign-off, since it changes a documented production invariant. Also need to update `isDrawActiveForCurrent()` (line 931-940) to match, otherwise the per-draw path (`syncRiskCurrentForDraw`) and the bucket path (`syncBucket → upsertRiskCurrentRows`) will disagree — silent bug.

The proposed diff below assumes resolution **(B)** strictly per spec (since the task literally said "make this match the spec"), but flags resolution **(A)** as the recommended one.

## Files changed (proposed)

- `app/Services/Dashboard/DashboardSummarySyncService.php` — add `result_at IS NULL` to set-based draw classification AND to `isDrawActiveForCurrent` (single-draw path). Keep `invalidDrawStatuses()` unchanged.
- `tests/Unit/Core/DashboardSummarySyncServiceTest.php` — invert/replace the two `keeps_*_when_result_at_is_pre_scheduled` tests to assert deletion (matches spec rule #5). Add a focused test #5 ("draw with `result_at NOT NULL` of any non-invalid status → deleted").

No other call sites of `upsertRiskCurrentRows` need changes — both callers (line 226 inside `syncRiskCurrentForDrawLocked`, line 490 inside `syncBucket`) pass arrays through the same gate.

## Diff (proposed, NOT applied)

```diff
diff --git a/app/Services/Dashboard/DashboardSummarySyncService.php b/app/Services/Dashboard/DashboardSummarySyncService.php
--- a/app/Services/Dashboard/DashboardSummarySyncService.php
+++ b/app/Services/Dashboard/DashboardSummarySyncService.php
@@ -818,12 +818,15 @@ class DashboardSummarySyncService
         }
         $roundIds = array_keys($roundIds);

         $validDrawIds = [];
         if (Schema::hasTable('lotto_draws') && ! empty($roundIds)) {
             $validDrawIds = DB::table('lotto_draws')
                 ->whereIn('id', $roundIds)
                 ->whereNotIn('status', $this->invalidDrawStatuses())
+                // BOA-230 spec: a draw with result_at populated is no longer
+                // accruing live liability — treat as invalid for current.
+                ->whereNull('result_at')
                 ->pluck('id')
                 ->map(static fn ($id) => (int) $id)
                 ->all();
             $validDrawIds = array_flip($validDrawIds);
         }
@@ -929,17 +932,21 @@ class DashboardSummarySyncService
     }

     private function isDrawActiveForCurrent(?object $draw): bool
     {
         if ($draw === null) {
             return false;
         }

         $status = strtolower((string) ($draw->status ?? ''));
+        if ($status === '' || in_array($status, $this->invalidDrawStatuses(), true)) {
+            return false;
+        }

-        return $status !== '' && ! in_array($status, $this->invalidDrawStatuses(), true);
+        // Mirror upsertRiskCurrentRows() so per-draw and bucket paths agree.
+        return $draw->result_at === null;
     }

diff --git a/tests/Unit/Core/DashboardSummarySyncServiceTest.php b/tests/Unit/Core/DashboardSummarySyncServiceTest.php
--- a/tests/Unit/Core/DashboardSummarySyncServiceTest.php
+++ b/tests/Unit/Core/DashboardSummarySyncServiceTest.php
@@ -234,42 +234,40 @@ class DashboardSummarySyncServiceTest extends TestCase
         $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
     }

-    public function test_current_writer_keeps_open_draw_even_when_result_at_is_pre_scheduled(): void
+    public function test_current_writer_deletes_existing_row_when_open_draw_has_result_at_set(): void
     {
-        // Production data can pre-schedule result_at while draw is still open/closed.
-        // Current risk must remain active until status becomes resulted.
+        // BOA-230 spec rule: result_at IS NOT NULL ⇒ invalid for current,
+        // regardless of status. Pre-existing row must be batch-deleted.
         $this->createTestTables();
         $this->seedDraw(10, ['status' => 'open', 'result_at' => '2026-05-05 12:00:00']);
+        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));

         $service = $this->makeService(
             $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
             $this->mockNotifier(),
         );

         $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

-        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
+        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
     }

-    public function test_current_writer_keeps_closed_draw_even_when_result_at_is_pre_scheduled(): void
+    public function test_current_writer_deletes_existing_row_when_closed_draw_has_result_at_set(): void
     {
-        // Closed-but-not-resulted draws can still carry liability until settlement.
+        // BOA-230 spec rule: result_at IS NOT NULL ⇒ invalid for current.
         $this->createTestTables();
         $this->seedDraw(10, ['status' => 'closed', 'result_at' => '2026-05-05 12:00:00']);
+        DB::table('lotto_dashboard_risk_current')->insert($this->existingCurrentRowFor(10));

         $service = $this->makeService(
             $this->mockProjectorWithPayload($this->dailyPayload(), $this->lottoPayloadMeaningfulRisk()),
             $this->mockNotifier(),
         );

         $service->syncBucket('2026-05-06', 'main', ['lotto_risk'], 'scheduled');

-        $this->assertSame(1, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
+        $this->assertSame(0, DB::table('lotto_dashboard_risk_current')->where('round_id', 10)->count());
     }
```

## Tests added / updated

For **each of the 8 required tests**, the existing test that satisfies it (or the proposed change):

| # | Spec rule | Test method | Status |
|---|---|---|---|
| 1 | Set-based no N+1 (≤ 2 SELECTs vs `lotto_draws` for 25 draws) | `DashboardSummarySyncServiceTest::test_current_writer_is_set_based_no_n_plus_one_against_lotto_draws` (line 334) | **already exists**, asserts `assertLessThanOrEqual(2, $drawSelects)` over 25 seeded draws |
| 2 | Valid open + meaningful → upserted | `test_current_writer_upserts_open_draw_with_meaningful_risk` (line 192) | already exists |
| 3 | Valid open + zero risk → row deleted by composite key | `test_current_writer_deletes_existing_zero_risk_row_by_full_key` (line 291) | already exists |
| 4 | Resulted draw → batch deleted | `test_current_writer_deletes_existing_row_for_resulted_draw` (line 220) | already exists |
| 5 | `result_at NOT NULL` (any status) → batch deleted | `test_current_writer_deletes_existing_row_when_open_draw_has_result_at_set` + `_when_closed_draw_has_result_at_set` | **proposed (new)** — replaces two existing inverted tests; see conflict section above |
| 6 | Defensive statuses → batch deleted | `test_current_writer_excludes_defensive_extended_statuses` (line 269) | already exists |
| 7 | Missing draw → batch deleted | `test_current_writer_deletes_existing_rows_for_missing_draw` (line 318) | already exists |
| 8 | Cancelled ticket exposure → cleaned | `test_cancelled_ticket_leaves_zero_risk_row_which_is_cleaned_from_current` (line 379) | already exists |

## Self-audit claims

- **Stubs/TODOs/mocks/fakes in my diff:** 0. No new code added — only a 3-line filter (`->whereNull('result_at')`), one helper rewrite (`isDrawActiveForCurrent`), and test inversions.
- **Required tests covered:** 8/8 (with caveat on #5 — see conflict section).
- **Forbidden patterns grep result:** Clean for the diff. The codebase still ships `BackfillLottoRiskCurrentCommand` (`app/Console/Commands/BackfillLottoRiskCurrentCommand.php:17`) and its tests — these are pre-existing utilities not referenced as a rollback path in the diff or in any updated comments.
- **Dead config check:** N/A — no config keys read or written by the diff.
- **Migration audit:** N/A — no migrations.
- **Silent-fallback grep:** Clean for the diff. The `whereNull('result_at')` is an explicit invariant filter, not a swallow-and-continue fallback. `isDrawActiveForCurrent()` returns `false` only when the draw object is null/has invalid status/has `result_at` set — those branches are exactly what the spec specifies as invalid.
- **Call sites of modified methods checked:**
  - `upsertRiskCurrentRows` — 2 callers: `syncRiskCurrentForDrawLocked()` at line 226 and `syncBucket()` at line 490. Both pass `$riskRows` arrays through the same gate, so the `result_at` filter applies uniformly.
  - `isDrawActiveForCurrent` — 1 caller: `syncRiskCurrentForDrawLocked()` at line 178. Caller already does correct delete-on-invalid behavior; tightening the predicate strictly increases the delete domain.
  - `syncBucket` — 4 callers in app code (`SyncDashboardSummaryBucket` job, `BackfillLottoRiskCurrentCommand`, `BackfillDashboardSummaryCommand`, `BackfillLottoDashboardSummaryCommand`). None affected by this change beyond the new invariant.
- **Sibling tests read:** `tests/Unit/Core/DashboardSummarySyncServiceTest.php` (full file, 1037 lines), `tests/Feature/Lotto/BackfillLottoRiskCurrentCommandTest.php` (signature-level only — confirms backfill command still exists but is not in the rollback path of the proposed diff).
- **Git history checked for surprising behavior:** Yes. `git log` on the file shows the BOA-230 PR-B work landed in commit `f86065dd` and the cancelled-ticket reviewer fix in `8ad169bc`. The two `keeps_*_when_result_at_is_pre_scheduled` tests were authored on the same PR with explanatory comments asserting the production invariant ("result_at can be pre-scheduled while status is still open/closed"). This is the strongest signal that the spec rule on `result_at` is at odds with deliberate prior intent.

## Reviewer risk notes

A strict reviewer will flag the following on the proposed diff:

1. **Production invariant inversion without domain owner sign-off.** The proposed change reverses an explicitly-documented invariant from the same BOA-230 ticket. A reviewer will require either (a) the BOA-230 acceptance criteria updated to reflect the new rule, or (b) the diff reverted to keep the existing behavior. Recommend resolving with the user/domain owner before applying.
2. **Reviewer will likely ask for an integration test against the real `lotto_draws` enum migration.** The unit test scaffold uses `Schema::create('lotto_draws', …)` with an unconstrained `string('status')` column. A separate test should confirm the production enum matches the assumptions in `invalidDrawStatuses()`.
3. **`isDrawActiveForCurrent` change subtly alters single-draw `syncRiskCurrentForDraw` semantics** for any draw that has `result_at` set but is still `open`/`closed`. The existing test `test_sync_risk_current_for_draw_upserts_only_target_draw_rows` (line 446) seeds open draws with `result_at=null`, so it still passes — but a reviewer should be asked whether single-draw callers (e.g. `SyncLottoRiskCurrentForDrawJob` triggered on draw events) need a specific test for the new gate.
4. **No log line for "draw classified invalid because result_at is set".** When a row gets deleted, ops will see it in `rows_deleted` aggregate but not the cause. Consider adding a structured log breakdown for the spec rule (especially during rollout) or at minimum a one-time INFO when this branch fires.
5. **The proposed diff does not modify `BackfillLottoRiskCurrentCommand`.** If the backfill command still classifies draws independently (it should be cross-checked), it could write back rows that `upsertRiskCurrentRows` will then delete on the next sync — a tight churn. Cross-checked: `BackfillLottoRiskCurrentCommand` does its own classification — likely needs a follow-up to align, but explicitly out of scope for PR-B per the spec. **Flag for follow-up ticket.**

## Confidence

**Medium.** The mechanical change is small and isolated. Confidence is bounded by the unresolved spec-vs-existing-test conflict on rule #5 — that conflict is the substantive risk, not the code change. If the user confirms resolution **(A)** (honor the production invariant), 7/8 spec tests are already met by the current code and only a documentation-style spec correction is needed. If the user confirms resolution **(B)** (honor the spec literally), the proposed diff above implements it but reverses a deliberate production invariant and warrants a domain-owner review.
