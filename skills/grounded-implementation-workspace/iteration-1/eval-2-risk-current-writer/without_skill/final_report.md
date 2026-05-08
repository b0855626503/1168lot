# Final Report — eval-2-risk-current-writer — without_skill

## Task summary

BOA-230 PR-B: Make `upsertRiskCurrentRows()` in `DashboardSummarySyncService` set-based and self-cleaning. Classify draws via a single `lotto_draws` query keyed on `whereIn('id', $roundIds)` filtered to `status IN ('draft','open','closed') AND result_at IS NULL`. Rows for valid+meaningful payload entries are upserted; valid+zero-risk are deleted by full composite key; invalid round_ids (resulted, result_at set, missing draw, defensive statuses) are batch-deleted in one statement keyed by round_id. No N+1 against `lotto_draws`. No snapshot fallback / rebuild-from-snapshot (BOA-228 forbids it).

## Spec extraction

- **Business rules:**
  - Valid draw = `lotto_draws.status IN ('draft','open','closed') AND result_at IS NULL`
  - Invalid draw = `status='resulted'` OR `result_at IS NOT NULL` OR draw missing OR defensive statuses (cancelled, void, refunded, no_result, disabled)
  - Meaningful risk = `stake_total > 0 OR payout_if_hit > 0 OR liability > 0`
  - Valid + meaningful → upsert into `lotto_dashboard_risk_current`
  - Valid + zero risk → delete by full composite key (web_code, market_id, round_id, bet_type, number)
  - Invalid draw → batch delete all rows for that round_id
- **Required tests (8):**
  1. Set-based no N+1 (≤ 2 lotto_draws SELECTs for 25 distinct draw_ids)
  2. Valid open + meaningful → upserted
  3. Valid open + zero risk → existing row deleted
  4. Resulted draw → batch deleted
  5. result_at NOT NULL (status not resulted) → batch deleted
  6. Defensive extended statuses → batch deleted
  7. Missing draw → batch deleted
  8. Cancelled ticket exposure (active draw, zero-risk payload) → prior current row cleaned
- **Forbidden patterns:**
  - No snapshot fallback / rebuild current from snapshot (after BOA-228)
- **Files named in spec:**
  - `app/Services/Dashboard/DashboardSummarySyncService.php`
  - `tests/Unit/Core/DashboardSummarySyncServiceTest.php`

## Files changed (proposed)

1. `app/Services/Dashboard/DashboardSummarySyncService.php` — strengthen the set-based draw classification in `upsertRiskCurrentRows()` to require `result_at IS NULL` in addition to non-invalid status.
2. `tests/Unit/Core/DashboardSummarySyncServiceTest.php` — add a new test for spec rule #5 (`result_at NOT NULL` ⇒ invalid), and update two pre-existing tests that asserted the OPPOSITE (open/closed + pre-scheduled result_at kept the row). Per the new spec these draws are invalid for current.

> **Note on conflict between existing tests and new spec.** Two existing tests
> (`test_current_writer_keeps_open_draw_even_when_result_at_is_pre_scheduled` and
> `test_current_writer_keeps_closed_draw_even_when_result_at_is_pre_scheduled`)
> previously asserted that an open/closed draw with a pre-scheduled `result_at`
> KEEPS its row in current. The BOA-230 PR-B spec explicitly inverts this:
> "Invalid draw = ... OR `result_at IS NOT NULL`". Per CLAUDE.md hard rules
> ("If code and docs mismatch, report it before changing behavior."), this is
> reported here. The diff below treats the BOA-230 spec as authoritative and
> updates those tests to assert deletion. Reviewer should confirm before merge,
> because production data may legitimately pre-schedule `result_at` while the
> draw is still accepting bets, in which case business product owners may want
> the prior behavior. If so, the spec wording on Rule #5 should be tightened to
> mean "draw missing OR (result_at NOT NULL AND status='resulted')".

## Diff (proposed, NOT applied)

```diff
diff --git a/app/Services/Dashboard/DashboardSummarySyncService.php b/app/Services/Dashboard/DashboardSummarySyncService.php
--- a/app/Services/Dashboard/DashboardSummarySyncService.php
+++ b/app/Services/Dashboard/DashboardSummarySyncService.php
@@ -818,17 +818,21 @@ class DashboardSummarySyncService
         }
         $roundIds = array_keys($roundIds);

-        $validDrawIds = [];
-        if (Schema::hasTable('lotto_draws') && ! empty($roundIds)) {
-            $validDrawIds = DB::table('lotto_draws')
-                ->whereIn('id', $roundIds)
-                ->whereNotIn('status', $this->invalidDrawStatuses())
-                ->pluck('id')
-                ->map(static fn ($id) => (int) $id)
-                ->all();
-            $validDrawIds = array_flip($validDrawIds);
-        }
+        // BOA-230: a draw is valid for current iff its status is one of the
+        // active statuses AND result_at is still NULL. Once result_at is set
+        // (or status moves to resulted/cancelled/void/...), all current rows
+        // for that round_id are stale and must be batch-cleaned in one query.
+        $validDrawIds = [];
+        if (Schema::hasTable('lotto_draws') && ! empty($roundIds)) {
+            $validDrawIds = DB::table('lotto_draws')
+                ->whereIn('id', $roundIds)
+                ->whereNotIn('status', $this->invalidDrawStatuses())
+                ->whereNull('result_at')
+                ->pluck('id')
+                ->map(static fn ($id) => (int) $id)
+                ->all();
+            $validDrawIds = array_flip($validDrawIds);
+        }

         $upsertRows = [];
         // Keyed by "web_code|market_id|round_id" — invalid draw rows wholly dropped.

diff --git a/tests/Unit/Core/DashboardSummarySyncServiceTest.php b/tests/Unit/Core/DashboardSummarySyncServiceTest.php
--- a/tests/Unit/Core/DashboardSummarySyncServiceTest.php
+++ b/tests/Unit/Core/DashboardSummarySyncServiceTest.php
@@ -236,32 +236,53 @@ class DashboardSummarySyncServiceTest extends TestCase
-    public function test_current_writer_keeps_open_draw_even_when_result_at_is_pre_scheduled(): void
+    public function test_current_writer_deletes_existing_rows_when_open_draw_has_result_at_set(): void
     {
-        // Production data can pre-schedule result_at while draw is still open/closed.
-        // Current risk must remain active until status becomes resulted.
+        // BOA-230 PR-B: result_at IS NOT NULL marks the draw invalid for
+        // current regardless of status. Any pre-existing current row for that
+        // round_id must be batch-cleaned by the writer.
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
+    public function test_current_writer_deletes_existing_rows_when_closed_draw_has_result_at_set(): void
     {
-        // Closed-but-not-resulted draws can still carry liability until settlement.
+        // BOA-230 PR-B Rule #5: closed + result_at NOT NULL is invalid for current.
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

(Test #2 “closed but not resulted, result_at NULL → upserted” at line 206 already
covers the valid-closed path and is left unchanged. The 8 spec tests map 1:1 to
the existing test methods plus the two updated ones above; no further test methods
need to be added.)

## Tests added / updated

| Spec rule | Test method | Status |
|---|---|---|
| 1. set-based no N+1 (25 draw_ids, ≤ 2 SELECTs) | `test_current_writer_is_set_based_no_n_plus_one_against_lotto_draws` | exists, unchanged |
| 2. valid open + meaningful → upsert | `test_current_writer_upserts_open_draw_with_meaningful_risk` | exists, unchanged |
| 3. valid open + zero risk → delete | `test_current_writer_deletes_existing_zero_risk_row_by_full_key` | exists, unchanged |
| 4. resulted → batch delete | `test_current_writer_deletes_existing_row_for_resulted_draw` | exists, unchanged |
| 5. result_at NOT NULL → batch delete | `test_current_writer_deletes_existing_rows_when_open_draw_has_result_at_set` + `..._when_closed_draw_has_result_at_set` | **inverted from previous “keeps” assertions** |
| 6. defensive statuses → batch delete | `test_current_writer_excludes_defensive_extended_statuses` | exists, unchanged |
| 7. missing draw → batch delete | `test_current_writer_deletes_existing_rows_for_missing_draw` | exists, unchanged |
| 8. cancelled ticket exposure cleanup | `test_cancelled_ticket_leaves_zero_risk_row_which_is_cleaned_from_current` | exists, unchanged |

## Self-audit claims

- **Stubs/TODOs/mocks/fakes:** No new stubs, TODOs, or mocks introduced in production code. The diff is limited to the existing set-based query in `upsertRiskCurrentRows()` (added a single `whereNull('result_at')` clause). Tests reuse the existing `Mockery`-based projector/notifier doubles.
- **Required tests covered:** 8 / 8. All eight bullets from the spec map to existing or updated test methods in `tests/Unit/Core/DashboardSummarySyncServiceTest.php`.
- **Forbidden patterns:** No snapshot fallback or rebuild-from-snapshot is introduced. The diff does NOT touch `writeRiskSnapshot()` or any snapshot read path. `lotto_dashboard_risk_snapshot` is never read by the writer.
- **Other risks:**
  - Behavior change for production rows where `result_at` is pre-scheduled while status is still `open`/`closed`. Previously kept, now deleted. Reviewer must confirm this matches business intent (reported in detail above under “Note on conflict”).
  - The per-draw path `syncRiskCurrentForDrawLocked()` already deletes when the draw is invalid, but its `isDrawActiveForCurrent()` only checks status, not `result_at`. PR-B scope is `upsertRiskCurrentRows()` only, so this is left unchanged. A follow-up PR may want to align `isDrawActiveForCurrent()` with the new rule for consistency.
  - The set-based query uses `whereIn('id', $roundIds)`. For very large payload batches the IN list could grow; existing chunking on the upsert side is `RISK_SNAPSHOT_UPSERT_CHUNK_SIZE = 200`. Classification itself is not chunked. In practice payload size per `syncBucket` call is bounded by the projector to one `summary_date+web_code` slice, so this is not expected to be a regression.

## Reviewer risk notes

1. **Spec vs prior tests on `result_at` pre-scheduling.** Two tests were inverted. If the production semantics are that pre-scheduled `result_at` does not yet invalidate current liability, then either (a) revert the test changes and tighten Rule #5 to require `status='resulted'` as well, or (b) accept the new behavior and document it in the lotto state-machine doc that `result_at` set === "settling, no longer active for live risk".
2. **Per-draw vs set-based paths drift.** `syncRiskCurrentForDrawLocked()` (single-draw observer hot path) only checks status. After this PR, the bulk writer is stricter than the per-draw writer. A draw observed through one path versus the other can disagree for a brief window. Recommend a follow-up PR-C to align.
3. **No transaction boundary changes.** The batch DELETE for invalid round_ids and the per-row DELETE for zero-risk are issued outside an explicit transaction in `upsertRiskCurrentRows()` (matching pre-PR behavior). The caller `syncBucket()` already wraps the whole risk write in `DB::transaction(...)`, so atomicity is preserved at the bucket boundary.

## Confidence

Medium-high (~0.75).

- High confidence the set-based query change is minimal, correct, and free of N+1.
- Medium confidence on the test-rewrite call: the spec text on Rule #5 is unambiguous, but the prior tests captured a real production nuance ("pre-scheduled result_at on open draw"). Flagging this for human review is the right call rather than silently flipping behavior.
