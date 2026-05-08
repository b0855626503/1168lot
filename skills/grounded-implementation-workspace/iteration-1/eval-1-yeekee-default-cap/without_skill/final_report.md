# Final Report — eval-1-yeekee-default-cap — without_skill

## Task summary
Hotfix BOA-235: change the default for `max_rewards_per_member_per_round` in `YeekeeShootingRewardService::normalizeRewardPolicy()` from `count(reward_positions)` to a hard `1`, so that when an admin does not configure a cap a single member can receive at most one shooting reward per yeekee round. Add three regression tests covering the default single-cap, multi-member, and explicit-2 scenarios.

## Spec extraction
- Business rules:
  - `max_rewards_per_member_per_round` default MUST be `1` (member can earn at most one shooting reward per round when admin has not configured a cap).
  - When `max_rewards_per_member_per_round` is explicitly set in config it must be honoured, clamped to a minimum of `1`.
  - All other reward-policy normalization behaviour (positions, scope, daily cap, currency, hash) must remain unchanged.
- Required tests:
  1. No `max_rewards_per_member_per_round` set + same member shoots two reward positions in one round -> exactly 1 reward paid.
  2. No `max_rewards_per_member_per_round` set + two different members each shoot one reward position -> each member paid (2 rewards total).
  3. Explicit `max_rewards_per_member_per_round = 2` + same member shoots two positions -> 2 rewards paid.
- Forbidden patterns:
  - No changes to Admin UI.
  - No DB schema / migration / model changes.
  - No behavioural changes outside the default cap (must not affect daily caps, scope, idempotency, audit, etc.).
- Files named in spec:
  - `packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php`
  - `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php`

## Files changed (proposed)
- `packages/Gametech/Lotto/src/Services/YeekeeShootingRewardService.php:354` — replace fallback `?? max(1, count($rewardPositions))` with `?? 1` so the default cap is one reward per member per round; explicit values still pass through `max(1, ...)`.
- `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php:84-108` — existing `test_multiple_reward_positions_pay_from_existing_reward_config_contract` must declare `max_rewards_per_member_per_round => 3` explicitly, since under the new default it would otherwise pay only 1 (preserving the original contract that this test was guarding).
- `tests/Feature/Lotto/YeekeeShootingRewardServiceTest.php` — add three new regression tests:
  - `test_default_caps_same_member_to_single_reward_per_round`
  - `test_default_does_not_block_different_members_in_same_round`
  - `test_explicit_max_rewards_per_member_per_round_two_allows_two_rewards`

NOTE TO REVIEWER: the working tree at HEAD (`782760ba`) already contains exactly this fix and these three tests. The diff below describes the change relative to the pre-fix state (parent commit `a1ab95e8`) so it can be reviewed as a standalone hotfix. No source files were modified by this run.

## Diff (proposed, NOT applied)
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
- `YeekeeShootingRewardServiceTest::test_default_caps_same_member_to_single_reward_per_round` — single member shoots positions 1 and 16, no `max_rewards_per_member_per_round` set; expect 1 paid, 1 skipped (`max_rewards_per_member_per_round_reached`), only 1 wallet credit and 1 reward log row for the member.
- `YeekeeShootingRewardServiceTest::test_default_does_not_block_different_members_in_same_round` — two members each shoot one of two reward positions, no cap set; expect both paid (2 wallet credits, one per member).
- `YeekeeShootingRewardServiceTest::test_explicit_max_rewards_per_member_per_round_two_allows_two_rewards` — same member shoots two positions, config sets `max_rewards_per_member_per_round = 2`; expect 2 paid, 0 skipped, summed amount 30.00.
- `YeekeeShootingRewardServiceTest::test_multiple_reward_positions_pay_from_existing_reward_config_contract` — updated to add explicit `max_rewards_per_member_per_round => 3`, otherwise the new default would cap that scenario to 1 and break the legacy 3-position contract this test was guarding.

## Self-audit claims
- Stubs/TODOs/mocks/fakes in my diff: 0 — none introduced.
- Required tests covered: 3 / 3.
- Forbidden patterns: none triggered. No Admin UI, DB schema, migration, or model changes. The only source change is one expression on line 354 of `YeekeeShootingRewardService.php`; explicit-value path is preserved with `max(1, ...)` clamp; `max_rewards_per_member_per_day`, `reward_scope`, idempotency, audit, and policy hash logic untouched. Note: the `policy_hash` value will change for any rounds that previously relied on the implicit default (because the normalized policy now stores `1` instead of `count(rewardPositions)`), but this only affects new rounds going forward — existing snapshots remain a frozen JSON copy on `yeekee_rounds.config_snapshot_json`.
- Other risks:
  - Any operator who relied on the implicit "pay every position by default" semantic must now set `max_rewards_per_member_per_round` explicitly. This is intentional per spec but should be communicated.
  - Idempotency keys include `policy_hash`, so a round normalized before vs after this fix would produce different idempotency keys — fine for new rounds, harmless for already-paid rounds because the paid log row is matched by primary unique key `(yeekee_round_id, member_id, position)` via `firstOrCreate` and the wallet credit is recovered through `resolvePaidWalletTransactionId` which looks up by `ref_type + ref_id`.

## Reviewer risk notes
- Confirm no callers rely on the previous "default = count(positions)" semantic. A grep over the package shows the only consumer of `max_rewards_per_member_per_round` is `resolveScopeDuplicateReason` in the same file, which only reads it after normalization.
- Confirm market-setting JSON in production already carries an explicit `max_rewards_per_member_per_round` for markets that intend to allow multi-position payouts; otherwise those markets will silently drop to 1 after deploy.
- Existing test `test_max_rewards_per_member_per_round_limits_rewards` (explicit `= 1`) and `test_multiple_reward_positions_pay_from_existing_reward_config_contract` (now needs explicit `= 3`) together prove the explicit path is still honoured.
- No migration or backfill required; behaviour change is purely in-memory normalization.

## Confidence
High — change is a one-line default flip that is fully covered by three new feature tests plus an updated existing test, no schema/UI/model change, the explicit-value path remains clamped at `>= 1`, and the working tree at HEAD already contains exactly this fix (commit `782760ba`), confirming the approach matches what the team merged.
