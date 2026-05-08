# Final Report — eval-1-yeekee-default-cap — with_skill

## Task summary
Hotfix BOA-235: change `YeekeeShootingRewardService::normalizeRewardPolicy()` so the default for `max_rewards_per_member_per_round` is `1` (not `count(reward_positions)`), guaranteeing at most one yeekee shooting reward per member per round unless the admin explicitly configures a higher cap, with three regression tests.

## Spec extraction
- Business rules (verbatim quotes from task):
  - "สมาชิก 1 คนต้องได้รางวัลยิงเลขสูงสุด 1 รางวัลต่อรอบหวยยี่กี่ (default behavior)."
  - "default ของ `max_rewards_per_member_per_round` ใช้ `count(reward_positions)` เป็น fallback ทำให้ถ้า admin ไม่ได้ set ค่า สมาชิกที่ยิงติดทุก position ได้รางวัลทุกอัน"
  - "default = 1 (ห้ามใช้ count(reward_positions))"
  - "ถ้า explicit config set → ใช้ค่านั้น (clamp >= 1)"
- Required tests (numbered 1-3):
  1. ไม่ตั้ง max + สมาชิกเดียวยิงติด 2 positions ในรอบเดียว → จ่าย 1 รางวัลเท่านั้น
  2. ไม่ตั้ง max + คนละ member ยิงคนละ position → จ่ายแต่ละคน
  3. ตั้ง explicit `max_rewards_per_member_per_round=2` → จ่าย 2 รางวัล
- Forbidden patterns:
  - ห้ามเปลี่ยน Admin UI / DB schema / migration / model
  - ห้ามกระทบ behavior อื่นนอก default cap (e.g. `min_bet_amount` from PR #73)
  - ห้าม bundle refactor / drive-by changes
- Files named in spec:
  - `packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php`
  - `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php`

## Important grounding finding (Phase 2)

**The fix described in this task has already been merged.** Per `git log`:

- Commit `782760ba` ("Fix yeekee shooting reward default cap to one per round", dated 2026-05-08, today) already changed line 354 of `YeekeeShootingRewardService.php` from
  `max(1, (int) ($rewardConfig['max_rewards_per_member_per_round'] ?? max(1, count($rewardPositions))))`
  to
  `max(1, (int) ($rewardConfig['max_rewards_per_member_per_round'] ?? 1))`
  and added the three required regression tests in `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php`:
  - `test_default_caps_same_member_to_single_reward_per_round` (spec test #1)
  - `test_default_does_not_block_different_members_in_same_round` (spec test #2)
  - `test_explicit_max_rewards_per_member_per_round_two_allows_two_rewards` (spec test #3)
- It also added `'max_rewards_per_member_per_round' => 3` to the pre-existing `test_multiple_reward_positions_pay_from_existing_reward_config_contract` test, because that test relies on three positions paying out for one member and would otherwise regress under the new default.

Per the repo's Hard Rules ("If code and docs mismatch, report it before changing behavior") I am reporting this rather than re-applying or rewriting the fix. The diff below is what *would* be applied if the fix had not already landed, reconstructed from the actual merged commit so reviewers can compare exactly.

## Files changed (proposed)
- `packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php:354` — replace the `count($rewardPositions)` fallback with a literal `1`, so the default cap is one reward per member per round regardless of how many `reward_positions` the policy declares. The outer `max(1, …)` is preserved so explicit config values are still clamped to `>= 1`.
- `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php` — three new regression tests (one per spec test), plus a one-line update to `test_multiple_reward_positions_pay_from_existing_reward_config_contract` to set `max_rewards_per_member_per_round => 3` (otherwise that test would now fail under the new default of 1, which would be a drive-by behavior regression — the test originally relied on the implicit `count(positions)` default).

## Diff (proposed, NOT applied — already present in tree as commit 782760ba)
```diff
diff --git a/packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php b/packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php
--- a/packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php
+++ b/packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php
@@ -351,7 +351,7 @@ class YeekeeShootingRewardService
             'reward_positions' => $rewardPositions,
             'min_bet_amount' => max(0.0, round((float) ($rewardConfig['min_bet_amount'] ?? 0), 2)),
             'reward_scope' => $rewardScope,
-            'max_rewards_per_member_per_round' => max(1, (int) ($rewardConfig['max_rewards_per_member_per_round'] ?? max(1, count($rewardPositions)))),
+            'max_rewards_per_member_per_round' => max(1, (int) ($rewardConfig['max_rewards_per_member_per_round'] ?? 1)),
             'max_rewards_per_member_per_day' => max(0, (int) ($rewardConfig['max_rewards_per_member_per_day'] ?? 0)),
             'currency' => strtoupper(trim((string) ($rewardConfig['currency'] ?? 'THB'))) ?: 'THB',
         ];
diff --git a/tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php b/tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php
--- a/tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php
+++ b/tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php
@@ -86,6 +86,7 @@ class YeekeeShootingRewardServiceTest extends TestCase
         $this->seedBaseData(snapshot: [
             'reward_enabled' => true,
             'reward_config' => $this->rewardConfig([
+                'max_rewards_per_member_per_round' => 3,
                 'reward_positions' => [
                     ['position' => 1, 'credit_amount' => 10],
                     ['position' => 16, 'credit_amount' => 20],
@@ -106,6 +107,85 @@ class YeekeeShootingRewardServiceTest extends TestCase
         $this->assertSame(60.0, (float) DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->sum('amount'));
     }
 
+    public function test_default_caps_same_member_to_single_reward_per_round(): void
+    {
+        $this->seedBaseData(snapshot: [
+            'reward_enabled' => true,
+            'reward_config' => $this->rewardConfig([
+                'reward_positions' => [
+                    ['position' => 1, 'credit_amount' => 10],
+                    ['position' => 16, 'credit_amount' => 20],
+                ],
+            ]),
+        ]);
+        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
+        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
+        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);
+
+        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());
+
+        $this->assertSame(1, (int) $result['paid_count']);
+        $this->assertSame(1, (int) $result['skipped_count']);
+        $this->assertSame(1, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
+        $this->assertSame(1, DB::table('yeekee_shoot_reward_logs')->where('status', 'paid')->count());
+        $this->assertSame(0, DB::table('yeekee_shoot_reward_logs')->where('status', 'pending')->count());
+        $this->assertSame(1, DB::table('yeekee_shoot_reward_logs')->where('member_id', 7001)->count());
+    }
+
+    public function test_default_does_not_block_different_members_in_same_round(): void
+    {
+        $this->seedBaseData(snapshot: [
+            'reward_enabled' => true,
+            'reward_config' => $this->rewardConfig([
+                'reward_positions' => [
+                    ['position' => 1, 'credit_amount' => 10],
+                    ['position' => 16, 'credit_amount' => 20],
+                ],
+            ]),
+        ]);
+        DB::table('members')->insert([
+            'code' => 7002,
+            'balance' => 1000,
+            'date_update' => now(),
+        ]);
+        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
+        $this->seedShoot(id: 4016, position: 16, memberId: 7002);
+        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150, id: 9001);
+        $this->seedTicket(memberId: 7002, drawId: 3003, amount: 150, id: 9002);
+
+        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());
+
+        $this->assertSame(2, (int) $result['paid_count']);
+        $this->assertSame(0, (int) $result['skipped_count']);
+        $this->assertSame(2, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
+        $this->assertSame(1, DB::table('wallet_transactions')->where('member_id', 7001)->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
+        $this->assertSame(1, DB::table('wallet_transactions')->where('member_id', 7002)->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
+    }
+
+    public function test_explicit_max_rewards_per_member_per_round_two_allows_two_rewards(): void
+    {
+        $this->seedBaseData(snapshot: [
+            'reward_enabled' => true,
+            'reward_config' => $this->rewardConfig([
+                'max_rewards_per_member_per_round' => 2,
+                'reward_positions' => [
+                    ['position' => 1, 'credit_amount' => 10],
+                    ['position' => 16, 'credit_amount' => 20],
+                ],
+            ]),
+        ]);
+        $this->seedShoot(id: 4001, position: 1, memberId: 7001);
+        $this->seedShoot(id: 4016, position: 16, memberId: 7001);
+        $this->seedTicket(memberId: 7001, drawId: 3003, amount: 150);
+
+        $result = app(YeekeeShootingRewardService::class)->applyForRound($this->round(), $this->draw());
+
+        $this->assertSame(2, (int) $result['paid_count']);
+        $this->assertSame(0, (int) $result['skipped_count']);
+        $this->assertSame(2, DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->count());
+        $this->assertSame(30.0, (float) DB::table('wallet_transactions')->where('ref_type', 'YEEKEE_SHOOT_REWARD')->sum('amount'));
+    }
+
     public function test_missing_position_skips_only_that_position(): void
     {
         $this->seedBaseData(snapshot: [
```

## Tests added / updated
- `YeekeeShootingRewardServiceTest::test_default_caps_same_member_to_single_reward_per_round` — spec rule #1: one member, two hit positions, default cap → exactly one reward paid, one skipped, one wallet transaction.
- `YeekeeShootingRewardServiceTest::test_default_does_not_block_different_members_in_same_round` — spec rule #2: two distinct members each hit one position, default cap → both paid, two wallet transactions, one per member.
- `YeekeeShootingRewardServiceTest::test_explicit_max_rewards_per_member_per_round_two_allows_two_rewards` — spec rule #3: explicit `max_rewards_per_member_per_round = 2`, one member two positions → both rewards paid, sum 30.
- `YeekeeShootingRewardServiceTest::test_multiple_reward_positions_pay_from_existing_reward_config_contract` — updated to declare `max_rewards_per_member_per_round => 3` so it remains green under the new default. This is a test-only change required by the spec change; it preserves the original assertion (three positions all pay for one member).

## Self-audit claims
- Stubs/TODOs/mocks/fakes in my diff: 0 — the diff is a one-line literal replacement (`count($rewardPositions)` → `1`) plus straightforward feature tests using existing helpers (`seedBaseData`, `rewardConfig`, `seedShoot`, `seedTicket`, `round`, `draw`).
- Required tests covered: 3 / 3.
- Forbidden patterns grep result: clean. The diff does not touch any migration, model, or admin UI file. Only `min_bet_amount` enforcement is unchanged (line 352, untouched). No drive-by refactor.
- Dead config check: pass. `max_rewards_per_member_per_round` is read in `normalizeRewardPolicy()` (line 354) and enforced in `resolveScopeDuplicateReason()` (line 392, `>= (int) $policy['max_rewards_per_member_per_round']` → returns `'max_rewards_per_member_per_round_reached'` which short-circuits `applyRewardPosition` via `skipPosition`). Read → applied → tested end-to-end.
- Migration audit: N/A — no migrations touched.
- Silent-fallback grep: clean / justified. The only fallback in the touched line is the `?? 1` null-coalesce, which is the spec-prescribed default. Outer `max(1, …)` clamps explicit values to >= 1 per spec ("clamp >= 1"). No new `catch`, no swallowed exceptions.
- Call sites of modified methods checked: `normalizeRewardPolicy()` is called only from `applyForRound()` at line 74 (same file). The returned policy keys consumed downstream: `reward_positions` (line 75, 88), `min_bet_amount` (line 142), `max_rewards_per_member_per_round` (line 392), `max_rewards_per_member_per_day` (line 396), `reward_scope` (line 397, 404), `currency` (line 128), `policy_hash` (line 129). All keys still present and shape-compatible after the change. No external callers (private method).
- Sibling tests read: `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php` (full file read; new tests follow existing seed/assert conventions).

## Reviewer risk notes
- **Idempotent task / already-merged state.** A strict reviewer would note that the requested change is already on `main` (commit 782760ba). Re-issuing it as a new PR would be a no-op or a merge conflict. The honest outcome is to report this back to the user rather than open a duplicate PR.
- **Policy hash drift.** Changing the default value of `max_rewards_per_member_per_round` changes the computed `policy_hash` for any round whose snapshot did not pin this field, which means idempotency keys (`YEEKEE_SHOOT_REWARD:%d:%d:%d:%d:%s`) for in-flight or replayed rounds will differ from any hashes computed under the previous default. This is acceptable for a hotfix but worth a release note. Not addressed by the diff.
- **Snapshot rounds in flight.** Yeekee rounds whose `config_snapshot_json` already serialized an explicit `max_rewards_per_member_per_round` value are unaffected. Rounds that omitted the key and were already partially settled under the previous default could see partial members capped at 1 going forward. Out of scope for this hotfix per the task constraints, but a reviewer may ask.
- **Tests not executed in this dry run.** This output is text-only per the task's "You will NOT modify source files" constraint, so `php artisan test --compact --filter=YeekeeShootingRewardServiceTest` was not run from this session. The tests were verified to already be present in the working tree and were authored alongside the merged fix.

## Confidence
High — the spec change is one line, the contract surface (`max_rewards_per_member_per_round`) has a single read site and a single enforcement site (both confirmed at `file:line`), and the three required tests already exist verbatim in the tree. The main caveat is that this task is requesting work that has already been merged on `main` today.
