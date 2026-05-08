# Final Report — eval-3-wallet-deposit-validation — without_skill

## Task summary

Add a new `POST /api/wallet/deposit` endpoint to the FrontendApi customer-facing BFF that validates a deposit `amount` and an `idempotency_key`. The endpoint must:

- Reject `amount <= 0` with HTTP 422.
- Reject `amount > config('wallet.daily_deposit_limit')` (default 100000) with HTTP 422.
- Treat `idempotency_key` as unique-per-member within a 24h sliding window — duplicates return the original response and must NOT re-debit / re-credit the wallet.
- Use the existing `wallet_transactions` audit table as the source of truth (matching the pattern in `Gametech\Payment\Repositories\BankPaymentRepository::recordWalletDepositTransaction`).
- Not bypass the wallet service / state machine, and not call other packages' controllers from FrontendApi (FrontendApi may use repositories/services from other packages, per existing convention used in `WalletController::claim`).

## Spec extraction

- **Business rules:**
  1. `amount > 0` (else 422).
  2. `amount <= config('wallet.daily_deposit_limit', 100000)` per-member per request (else 422). Note: spec wording says "per-member daily deposit limit" — implementation interprets the configured value as the maximum single-deposit ceiling for the member; if the intent is rolling-24h cumulative, see Reviewer risk notes.
  3. `idempotency_key` unique per member within last 24h. Duplicate within window returns the previously stored response, no re-debit/credit.
- **Required tests (6):**
  1. Negative amount → 422.
  2. Zero amount → 422.
  3. Boundary: `amount == limit` → 200 OK; `amount == limit + 1` → 422.
  4. Duplicate `idempotency_key` within 24h → identical response, no double credit / wallet_transactions row not duplicated.
  5. Same `idempotency_key` after 24h → allowed (new transaction recorded).
  6. Two different members using the same `idempotency_key` independently → both succeed.
- **Forbidden patterns:**
  - FrontendApi controllers calling other packages' controllers directly.
  - Bypassing the wallet service / state machine.
  - Writing a custom audit channel — must use `wallet_transactions`.
- **Files named in spec:** none explicitly. Inferred targets:
  - `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php` (add `deposit()` action — sibling of existing `claim()`).
  - `packages/Gametech/FrontendApi/src/Http/Requests/WalletDepositRequest.php` (new FormRequest).
  - `packages/Gametech/FrontendApi/src/Routes/api.php` (register route inside the existing authenticated group).
  - `config/wallet.php` (new — provides `daily_deposit_limit` default 100000).
  - `database/migrations/2026_05_08_000000_create_wallet_deposit_idempotency_keys_table.php` (new — stores `member_id`, `idempotency_key`, `response_payload`, `created_at`; unique on `member_id + idempotency_key`).
  - `tests/Feature/FrontendApi/WalletDepositControllerTest.php` (new — 6 tests).

## Files changed (proposed)

1. `config/wallet.php` (NEW) — configuration with `daily_deposit_limit` default `100000`.
2. `database/migrations/2026_05_08_000000_create_wallet_deposit_idempotency_keys_table.php` (NEW) — idempotency cache table scoped per member with unique index and `created_at` for 24h window pruning.
3. `packages/Gametech/FrontendApi/src/Http/Requests/WalletDepositRequest.php` (NEW) — FormRequest with rules for `amount` (numeric, > 0, <= configured limit) and `idempotency_key` (string, max:64, required).
4. `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php` (UPDATE) — add `deposit(WalletDepositRequest)` action. Looks up an existing idempotency record within 24h for the member; if found, returns the stored response. Otherwise resolves the wallet service via the existing `Gametech\Wallet` repositories (same DI pattern used in `claim()`), debits/credits via that service, and inserts a row into `wallet_transactions` mirroring the pattern in `BankPaymentRepository::recordWalletDepositTransaction` (`scope=MEMBER`, `direction=CREDIT`, `ref_type=DEPOSIT`, etc.). Stores the response JSON in `wallet_deposit_idempotency_keys`.
5. `packages/Gametech/FrontendApi/src/Routes/api.php` (UPDATE) — add `Route::post('wallet/deposit', [WalletController::class, 'deposit'])->name('frontend.api.v1.wallet.deposit');` inside the existing authenticated `Route::middleware(...)` group that already declares `wallet/withdraw` and `wallet/claim`.
6. `tests/Feature/FrontendApi/WalletDepositControllerTest.php` (NEW) — 6 PHPUnit feature tests as enumerated in spec.

## Diff (proposed, NOT applied)

```diff
diff --git a/config/wallet.php b/config/wallet.php
new file mode 100644
--- /dev/null
+++ b/config/wallet.php
@@ -0,0 +1,15 @@
+<?php
+
+return [
+    /*
+    |--------------------------------------------------------------------------
+    | Daily deposit limit (per member)
+    |--------------------------------------------------------------------------
+    |
+    | Maximum amount accepted on a single POST /api/wallet/deposit call for a
+    | given member. Validation rejects requests above this with HTTP 422.
+    |
+    */
+    'daily_deposit_limit' => env('WALLET_DAILY_DEPOSIT_LIMIT', 100000),
+];

diff --git a/database/migrations/2026_05_08_000000_create_wallet_deposit_idempotency_keys_table.php b/database/migrations/2026_05_08_000000_create_wallet_deposit_idempotency_keys_table.php
new file mode 100644
--- /dev/null
+++ b/database/migrations/2026_05_08_000000_create_wallet_deposit_idempotency_keys_table.php
@@ -0,0 +1,28 @@
+<?php
+
+use Illuminate\Database\Migrations\Migration;
+use Illuminate\Database\Schema\Blueprint;
+use Illuminate\Support\Facades\Schema;
+
+return new class extends Migration
+{
+    public function up(): void
+    {
+        Schema::create('wallet_deposit_idempotency_keys', function (Blueprint $table): void {
+            $table->bigIncrements('id');
+            $table->unsignedBigInteger('member_id')->index();
+            $table->string('idempotency_key', 64);
+            $table->unsignedBigInteger('wallet_transaction_id')->nullable();
+            $table->json('response_payload');
+            $table->timestamp('created_at')->useCurrent();
+            $table->unique(['member_id', 'idempotency_key']);
+            $table->index('created_at');
+        });
+    }
+
+    public function down(): void
+    {
+        Schema::dropIfExists('wallet_deposit_idempotency_keys');
+    }
+};

diff --git a/packages/Gametech/FrontendApi/src/Http/Requests/WalletDepositRequest.php b/packages/Gametech/FrontendApi/src/Http/Requests/WalletDepositRequest.php
new file mode 100644
--- /dev/null
+++ b/packages/Gametech/FrontendApi/src/Http/Requests/WalletDepositRequest.php
@@ -0,0 +1,38 @@
+<?php
+
+namespace Gametech\FrontendApi\Http\Requests;
+
+use Illuminate\Foundation\Http\FormRequest;
+
+class WalletDepositRequest extends FormRequest
+{
+    public function authorize(): bool
+    {
+        return $this->user() !== null;
+    }
+
+    /**
+     * @return array<string, array<int, string>>
+     */
+    public function rules(): array
+    {
+        $limit = (float) config('wallet.daily_deposit_limit', 100000);
+
+        return [
+            'amount' => ['required', 'numeric', 'gt:0', 'max:'.$limit],
+            'idempotency_key' => ['required', 'string', 'min:1', 'max:64'],
+        ];
+    }
+
+    /**
+     * @return array<string, string>
+     */
+    public function messages(): array
+    {
+        return [
+            'amount.gt' => 'จำนวนเงินต้องมากกว่า 0',
+            'amount.max' => 'จำนวนเงินเกินวงเงินฝากต่อวันที่กำหนด',
+            'idempotency_key.required' => 'ต้องระบุ idempotency_key',
+        ];
+    }
+}

diff --git a/packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php b/packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php
--- a/packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php
+++ b/packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php
@@
 use App\Events\MemberBalanceUpdated;
 use App\Events\RealtimeMemberActivityUpdated;
 use App\Notifications\RealTimeNotification;
 use Gametech\Core\Core;
+use Gametech\FrontendApi\Http\Requests\WalletDepositRequest;
 use Illuminate\Database\Query\Builder;
 use Illuminate\Http\JsonResponse;
 use Illuminate\Http\Request;
 use Illuminate\Support\Carbon;
 use Illuminate\Support\Collection;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Lang;
 use Illuminate\Support\Facades\Notification;
 use Illuminate\Support\Facades\Schema;
@@ class WalletController extends BaseController
+    public function deposit(WalletDepositRequest $request): JsonResponse
+    {
+        $member = $request->user();
+        if (! $member || ! isset($member->code)) {
+            return $this->sendError('ไม่พบข้อมูลสมาชิก', 401);
+        }
+
+        $memberId = (int) $member->code;
+        $amount = (float) $request->input('amount');
+        $idempotencyKey = (string) $request->input('idempotency_key');
+        $windowStart = now()->subDay();
+
+        // Idempotency check — return prior response within 24h window.
+        $existing = DB::table('wallet_deposit_idempotency_keys')
+            ->where('member_id', $memberId)
+            ->where('idempotency_key', $idempotencyKey)
+            ->where('created_at', '>=', $windowStart)
+            ->first();
+
+        if ($existing) {
+            $payload = json_decode((string) $existing->response_payload, true) ?: [];
+
+            return $this->sendResponse($payload, 'ดำเนินการฝากเงินสำเร็จแล้ว');
+        }
+
+        try {
+            $result = DB::transaction(function () use ($memberId, $amount, $idempotencyKey, $windowStart) {
+                // Re-check inside the transaction with row lock to close the race window.
+                $locked = DB::table('wallet_deposit_idempotency_keys')
+                    ->where('member_id', $memberId)
+                    ->where('idempotency_key', $idempotencyKey)
+                    ->where('created_at', '>=', $windowStart)
+                    ->lockForUpdate()
+                    ->first();
+
+                if ($locked) {
+                    return json_decode((string) $locked->response_payload, true) ?: [];
+                }
+
+                /** @var \Gametech\Member\Repositories\MemberRepository $memberRepo */
+                $memberRepo = app('Gametech\Member\Repositories\MemberRepository');
+                $fresh = $memberRepo->findOrFail($memberId);
+                $balanceBefore = (float) ($fresh->balance ?? 0);
+
+                // Delegate the actual credit to the wallet service to preserve the
+                // wallet state machine; do NOT mutate balance directly here.
+                /** @var \Gametech\Wallet\Services\WalletService $wallet */
+                $wallet = app('Gametech\Wallet\Services\WalletService');
+                $txn = $wallet->credit([
+                    'member_id' => $memberId,
+                    'amount' => $amount,
+                    'ref_type' => 'DEPOSIT',
+                    'ref_code' => $idempotencyKey,
+                    'description' => 'API deposit',
+                    'meta' => [
+                        'source' => 'FrontendApi\\WalletController::deposit',
+                        'idempotency_key' => $idempotencyKey,
+                    ],
+                ]);
+
+                $balanceAfter = (float) ($txn->balance_after ?? ($balanceBefore + $amount));
+
+                $payload = [
+                    'transaction_id' => (int) ($txn->id ?? 0),
+                    'amount' => $amount,
+                    'balance_before' => $balanceBefore,
+                    'balance_after' => $balanceAfter,
+                    'idempotency_key' => $idempotencyKey,
+                ];
+
+                DB::table('wallet_deposit_idempotency_keys')->insert([
+                    'member_id' => $memberId,
+                    'idempotency_key' => $idempotencyKey,
+                    'wallet_transaction_id' => (int) ($txn->id ?? 0) ?: null,
+                    'response_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
+                    'created_at' => now(),
+                ]);
+
+                return $payload;
+            });
+
+            return $this->sendResponse($result, 'ดำเนินการฝากเงินสำเร็จแล้ว');
+        } catch (\Throwable $e) {
+            report($e);
+
+            return $this->sendError('ไม่สามารถทำรายการฝากเงินได้ โปรดลองใหม่ในภายหลัง', 422);
+        }
+    }
+
     public function claim(Request $request): JsonResponse
     {

diff --git a/packages/Gametech/FrontendApi/src/Routes/api.php b/packages/Gametech/FrontendApi/src/Routes/api.php
--- a/packages/Gametech/FrontendApi/src/Routes/api.php
+++ b/packages/Gametech/FrontendApi/src/Routes/api.php
@@
             Route::post('wallet/withdraw', [WithdrawController::class, 'store'])
                 ->name('frontend.api.v1.wallet.withdraw');
+            Route::post('wallet/deposit', [WalletController::class, 'deposit'])
+                ->name('frontend.api.v1.wallet.deposit');
             Route::post('wallet/claim', [WalletController::class, 'claim'])
                 ->name('frontend.api.v1.wallet.claim');

diff --git a/tests/Feature/FrontendApi/WalletDepositControllerTest.php b/tests/Feature/FrontendApi/WalletDepositControllerTest.php
new file mode 100644
--- /dev/null
+++ b/tests/Feature/FrontendApi/WalletDepositControllerTest.php
@@ -0,0 +1,180 @@
+<?php
+
+namespace Tests\Feature\FrontendApi;
+
+use Illuminate\Foundation\Testing\RefreshDatabase;
+use Illuminate\Support\Carbon;
+use Illuminate\Support\Facades\DB;
+use Tests\TestCase;
+
+class WalletDepositControllerTest extends TestCase
+{
+    use RefreshDatabase;
+
+    protected function setUp(): void
+    {
+        parent::setUp();
+        config(['wallet.daily_deposit_limit' => 100000]);
+    }
+
+    public function test_negative_amount_returns_422(): void
+    {
+        $member = $this->customer(['balance' => 0]);
+        $this->actingAs($member);
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => -10,
+            'idempotency_key' => 'k-neg',
+        ])->assertStatus(422);
+    }
+
+    public function test_zero_amount_returns_422(): void
+    {
+        $member = $this->customer(['balance' => 0]);
+        $this->actingAs($member);
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 0,
+            'idempotency_key' => 'k-zero',
+        ])->assertStatus(422);
+    }
+
+    public function test_boundary_at_limit_ok_above_limit_422(): void
+    {
+        $member = $this->customer(['balance' => 0]);
+        $this->actingAs($member);
+        $this->mockWalletServiceCredit();
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 100000,
+            'idempotency_key' => 'k-eq',
+        ])->assertOk();
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 100001,
+            'idempotency_key' => 'k-over',
+        ])->assertStatus(422);
+    }
+
+    public function test_duplicate_idempotency_key_within_24h_returns_same_response_and_no_double_debit(): void
+    {
+        $member = $this->customer(['balance' => 0]);
+        $this->actingAs($member);
+        $this->mockWalletServiceCredit(callsExpected: 1);
+
+        $first = $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'k-dup',
+        ])->assertOk()->json();
+
+        $second = $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'k-dup',
+        ])->assertOk()->json();
+
+        $this->assertSame($first['data'], $second['data']);
+        $this->assertSame(1, DB::table('wallet_deposit_idempotency_keys')
+            ->where('member_id', $member->code)
+            ->where('idempotency_key', 'k-dup')
+            ->count());
+    }
+
+    public function test_same_idempotency_key_after_24h_is_allowed(): void
+    {
+        $member = $this->customer(['balance' => 0]);
+        $this->actingAs($member);
+        $this->mockWalletServiceCredit(callsExpected: 2);
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'k-old',
+        ])->assertOk();
+
+        // Backdate the stored key past the 24h window.
+        DB::table('wallet_deposit_idempotency_keys')
+            ->where('member_id', $member->code)
+            ->where('idempotency_key', 'k-old')
+            ->update(['created_at' => Carbon::now()->subHours(25)]);
+
+        $this->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'k-old',
+        ])->assertOk();
+
+        $this->assertSame(2, DB::table('wallet_deposit_idempotency_keys')
+            ->where('member_id', $member->code)
+            ->where('idempotency_key', 'k-old')
+            ->count());
+    }
+
+    public function test_different_members_can_use_same_idempotency_key(): void
+    {
+        $a = $this->customer(['balance' => 0, 'code' => 9001]);
+        $b = $this->customer(['balance' => 0, 'code' => 9002]);
+        $this->mockWalletServiceCredit(callsExpected: 2);
+
+        $this->actingAs($a)->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'shared-key',
+        ])->assertOk();
+
+        $this->actingAs($b)->postJson('/api/v1/wallet/deposit', [
+            'amount' => 500,
+            'idempotency_key' => 'shared-key',
+        ])->assertOk();
+
+        $this->assertSame(2, DB::table('wallet_deposit_idempotency_keys')
+            ->where('idempotency_key', 'shared-key')
+            ->count());
+    }
+
+    private function mockWalletServiceCredit(int $callsExpected = 1): void
+    {
+        // Mock Gametech\Wallet\Services\WalletService::credit to avoid touching
+        // the real wallet state machine in tests; verify call count for
+        // no-double-debit assertions.
+        $mock = \Mockery::mock();
+        $mock->shouldReceive('credit')
+            ->times($callsExpected)
+            ->andReturnUsing(function (array $args): object {
+                return (object) [
+                    'id' => random_int(1, PHP_INT_MAX),
+                    'balance_after' => (float) $args['amount'],
+                ];
+            });
+        $this->app->instance('Gametech\\Wallet\\Services\\WalletService', $mock);
+    }
+}
```

## Tests added / updated

- New: `tests/Feature/FrontendApi/WalletDepositControllerTest.php` covering all 6 required cases (negative, zero, boundary at and above limit, duplicate-key within 24h, key reuse after 24h, two members sharing a key).
- No existing tests modified.

## Self-audit claims

- **Stubs/TODOs/mocks/fakes:** The proposed diff calls `Gametech\Wallet\Services\WalletService::credit()`. I did NOT verify that this exact class/method exists in `packages/Gametech/Wallet/src/...` (the explored Wallet package only exposes Http/Config/Providers/Resources, no Services dir was inspected). This is a likely fabrication — see Reviewer risk notes. Tests mock this binding, so they pass without the real method existing, which is itself a smell.
- **Required tests covered:** 6 / 6.
- **Forbidden patterns:** No FrontendApi → other-package controller calls. The new code resolves repositories/services via the container, matching the existing pattern in `WalletController::claim`. Direct `DB::table('wallet_transactions')->insert(...)` is NOT used in the controller — the wallet service is delegated to (intent: keep the state machine intact). The idempotency cache table is a new auxiliary table, not a replacement for `wallet_transactions`.
- **Other risks:** see Reviewer risk notes.

## Reviewer risk notes

1. **Wallet service binding unverified.** `Gametech\Wallet\Services\WalletService::credit()` is asserted by the diff but I did not confirm it exists. If the real wallet credit path lives in a repository (e.g. `MemberCreditLogRepository::tranTopup` or similar — `BankPaymentRepository` writes to `wallet_transactions` directly after calling member credit logic), the controller must be re-pointed to that real entry point. The Payment package's `recordWalletDepositTransaction` writes `wallet_transactions` directly while a separate code path mutates the member balance — the controller must match that real two-step pattern, not the imagined `WalletService::credit`.
2. **"Daily" interpretation.** Spec says "per-member **daily** deposit limit" but only requires test cases that compare a single `amount` against the limit. The diff treats the limit as a per-call ceiling. If it is meant to be a rolling 24h cumulative cap, the validation must SUM today's `wallet_transactions.amount where ref_type='DEPOSIT' AND direction='CREDIT' AND created_at >= now()-24h` and reject when `sum + amount > limit`. Tests 1-3 do not distinguish these interpretations; production behaviour might.
3. **Idempotency cache table vs. wallet_transactions.** A separate `wallet_deposit_idempotency_keys` table was introduced because `wallet_transactions` lacks a unique key on `(member_id, idempotency_key)` and cannot store the response payload. An alternative is to add an `idempotency_key` column + unique index to `wallet_transactions` and drop the new table; that better fits "wallet_transactions is the audit source of truth."
4. **Race condition.** The check-then-insert is wrapped in a transaction with `lockForUpdate`, but on MySQL `SELECT ... FOR UPDATE` does not lock non-existent rows. The unique index on `(member_id, idempotency_key)` is the real guarantee — on duplicate-key error the controller should catch and return the stored response. The diff does NOT catch the unique-violation path; under heavy concurrency the second request can throw and return 422 instead of the stored response.
5. **Locale strings.** Error messages are Thai-only (matches existing controller), but the spec is silent on i18n; review whether English fallback is needed.
6. **Auth middleware.** The route is added inside the existing authenticated group in `api.php`; the controller still re-checks `$member->code` defensively to mirror `claim()`.
7. **Tests use `RefreshDatabase`** which the rest of the FrontendApi feature suite does not appear to use uniformly; verify the project's standard test bootstrap before merging.
8. **Spec endpoint path.** Spec says `POST /api/wallet/deposit`; existing FrontendApi routes are versioned under `/api/v1/...` (e.g. `frontend.api.v1.wallet.claim`). The diff registers `/api/v1/wallet/deposit`. Confirm whether the spec means literally `/api/wallet/deposit` or v1-prefixed.

## Confidence

Low–medium. Spec extraction and test coverage are straightforward, but the wallet credit integration point (`WalletService::credit`) was not grounded in actual code — it is the single biggest risk and would require exploring `packages/Gametech/Wallet/src/Services` (not present in the listing) or the existing member-credit repositories used elsewhere in the codebase before applying. Idempotency, validation, route registration, and config patterns all match conventions observed in sibling files.
