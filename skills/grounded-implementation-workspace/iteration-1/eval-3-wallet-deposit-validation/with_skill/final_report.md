# Final Report — eval-3-wallet-deposit-validation — with_skill

## Task summary

Spec asked to add validation to `POST /api/wallet/deposit` accepting `{amount, idempotency_key}` with three rules (positive amount, daily-limit cap from `config('wallet.daily_deposit_limit', 100000)`, idempotency-key dedup within 24h) and six tests.

After Phase 2 (MAP) grounding, the spec's three core assumptions — the endpoint, the config key, and any idempotency-key infrastructure — **do not exist in this codebase**. Per `skills/grounded-implementation/SKILL.md` ("If a symbol the spec references does not exist in the codebase: STOP. Report the mismatch.") and `CLAUDE.md` ("If code and docs mismatch, report it before changing behavior."), no source files were modified. This report documents the reality and proposes a minimal, grounded path forward, but does not fabricate a fake endpoint, fake config, or fake idempotency table to "satisfy" the spec.

## Spec extraction

- Business rules (verbatim):
  1. "`amount` must be > 0 (reject negative or zero with 422)"
  2. "`amount` must be ≤ configured per-member daily deposit limit (config key: `wallet.daily_deposit_limit`, default 100000)"
  3. "`idempotency_key` must be unique per member within last 24h — duplicates within window return the previous response without re-debiting"
- Required tests (numbered 1-6):
  1. Negative amount → 422
  2. Zero amount → 422
  3. Boundary: amount == limit → OK; amount == limit+1 → 422
  4. Duplicate idempotency_key within 24h → returns same response, no double-debit
  5. Same idempotency_key after 24h → allowed
  6. Different members can use the same idempotency_key independently
- Forbidden patterns / architectural constraints:
  - "Use existing `wallet_transactions` audit pattern (it's the financial source of truth — never bypass)"
  - "Don't bypass the wallet service / state machine"
  - "`FrontendApi` must not call other packages' controllers directly"
  - "ห้าม bundle refactor"
- Files named in spec: none — spec explicitly says "discover the real codebase first".

## Reality-check findings

- **Does the deposit endpoint exist? Where?** **No.** There is no `POST /api/wallet/deposit` (or `POST /api/v1/wallet/deposit`).
  - `packages/Gametech/FrontendApi/src/Routes/api.php:123-155` registers the actual `wallet/*` and `*/deposit/*` routes. Inventory:
    - `POST wallet/withdraw` → `WithdrawController@store`
    - `POST wallet/claim` → `WalletController@claim` (claim bonus into main balance — internal transfer only, no money in)
    - `GET  wallet/transactions` → `WalletController@transactions` (read-only history)
    - `GET  deposit/channels`, `POST deposit/loadbank`, `POST deposit/loadbank/random` → `DepositController` (channel/bank lookup only, no deposit creation)
    - `POST smkpay/deposit/create`, `POST deeppay/deposit/create` → provider-specific deposit kickoff (SmkPay/DeepPay)
  - `WalletController` (`packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`) defines NO `deposit` method. Its public methods are `claim`, `transactions`.
  - `DepositController` (same dir) has only `channels`, `loadBank`, `loadRandomBank` — pure lookup, no money movement.
  - **In this codebase the customer-side "deposit" flow is provider-mediated** (SmkPay / DeepPay / slip / bank-transfer with admin verification), not a single unified `POST /api/wallet/deposit`.

- **Does config `wallet.daily_deposit_limit` exist? Where read?** **No.**
  - `config/wallet.php` does not exist.
  - Grep across `config/` for `daily_deposit_limit` / `deposit_limit` / `deposit_max` returns 0 hits.
  - Existing per-member daily caps that DO exist follow a different pattern: e.g. withdrawal uses `members.maxwithdraw_day` (per-member column) with fallback to `config.maxwithdraw_day` (legacy `core()->getConfigData()` runtime config), see `WithdrawController@store:51`. There is no analogue for deposit caps in the codebase.

- **Does the wallet service expose an idempotency mechanism already?** **No.**
  - Grep for `idempotency` across the repo returns hits only in: yeekee shooting reward service, lotto winning settlement, marketing campaign dashboard, payment-provider generator, wallet-ledger plan doc (`docs/04_PLANS/2026-03-21_wallet-ledger-implementation.md`), and a few tests. There is no `idempotency_keys` table, no `WalletService::depositWithIdempotency()`, no shared idempotency middleware, and no `idempotency_key` column on `wallet_transactions` queryable from controllers.
  - `wallet_transactions` is referenced as the audit-of-truth, but no `CREATE TABLE wallet_transactions` migration is present in `database/migrations/` — schema appears to be created out-of-band (likely a base wallet schema package), confirmed by the migration name `2026_04_25_130000_add_missing_columns_from_base_wallet_schema.php`.

- **Mismatches between spec and codebase:**
  1. Endpoint `POST /api/wallet/deposit` does not exist and is **architecturally inconsistent** with how this app actually accepts money (provider-mediated flows + admin slip verification).
  2. Config key `wallet.daily_deposit_limit` does not exist; the equivalent pattern in this codebase is `members.<col>_day` with `config.<key>_day` fallback via `core()->getConfigData()` (runtime DB config), not Laravel `config/*.php`.
  3. No idempotency infrastructure exists at all — neither table, column, service, nor middleware. Building it is far larger than a "validation" task.
  4. There is no canonical "wallet deposit service" that takes an `amount` and credits a member; the actual deposit completion path goes through provider observers (e.g. `BankPaymentObserver`, `WithdrawObserver`) and admin approval flows.

## Files changed (proposed)

**None.** No source file modification is proposed in this report, because the spec's preconditions are not satisfied by the codebase and the SKILL workflow requires reporting (not inventing) when symbols don't exist.

A grounded, *minimal* proposal — to be implemented only after the user clarifies — would be one of:

- **Option A (most likely intent):** Add the three validations to the **existing** provider deposit endpoints (`SmkPayController@deposit`, `DeepPayController@deposit`) which ARE the real "create a deposit intent" endpoints. This requires the user to confirm:
  - which existing config / member column should hold the daily cap (recommend mirroring the `maxwithdraw_day` / `members.maxwithdraw_day` pattern → new `members.maxdeposit_day` + `config.maxdeposit_day`),
  - whether idempotency should be (a) request-hash based via a new `wallet_deposit_idempotency` table or (b) leverage existing per-provider `transaction_id` / `txid` uniqueness already present in payment provider tables.
- **Option B:** If the spec intends a NEW unified endpoint, that is a feature, not a validation task. It crosses the wallet-service / state-machine boundary and needs a design doc and user sign-off before any code lands.

## Diff (proposed, NOT applied)

```diff
(no diff — see "Reality-check findings" above; the endpoint, config, and idempotency
machinery the spec assumes do not exist in this codebase. Proceeding would require
either fabricating those pieces or redirecting the work to one of the existing
deposit flows. Both require user confirmation per SKILL.md Phase 1/2 rules and per
CLAUDE.md "code ↔ doc mismatch → report before changing behavior".)
```

## Tests added / updated

No test file added. Per the SKILL guidance, writing tests against a fictional endpoint would simply lock in the hallucination. The required-test mapping IF Option A above is approved would be:

- Test 1 (negative amount → 422): would assert against the chosen real endpoint (e.g. `POST /api/v1/smkpay/deposit/create`) using existing `validator(...)->validate()` pattern seen in `DepositController::loadBank` (line 39-41) and `WalletController::transactions` (line 179-181).
- Test 2 (zero amount → 422): same.
- Test 3 (boundary at limit and limit+1): requires the daily-cap source to be decided first (per-member column vs runtime config); test would mirror the withdraw daily-cap test pattern.
- Test 4 (duplicate idempotency_key within 24h returns prior response, no double-debit): blocked on idempotency design choice.
- Test 5 (same key after 24h allowed): blocked on (4).
- Test 6 (per-member key isolation): blocked on (4).

Sibling test patterns to reuse when this is unblocked: `tests/Feature/FrontendApi/WalletTransactionsControllerTest.php`, `tests/Feature/Member/MemberCashbackRefillSeamlessDirectWalletTransactionTest.php` (real `wallet_transactions` audit assertions).

## Self-audit claims

- Stubs/TODOs/mocks/fakes: **0** — no source code written.
- Required tests covered: **0 / 6** — intentionally, because each test would assert against fictional code. Documented blocker per test above.
- Forbidden patterns: not applicable (no diff). Confirmed I did not call any other-package controller from `FrontendApi`, did not touch `wallet_transactions` writes, did not bypass any state machine.
- Dead config check: not applicable — refused to introduce `config('wallet.daily_deposit_limit', 100000)` because nothing else in the codebase reads or writes it; that would be textbook dead config (SKILL Phase 5.C2).
- Migration audit: not applicable — no migration written. Adding an `idempotency_keys` (or column on `wallet_transactions`) would touch the financial audit source of truth and is out of scope for a "validation" task.
- Silent-fallback grep: not applicable.
- Call sites of modified methods checked: not applicable (no method modified). Did read all real `wallet/*` and `*/deposit/*` routes (`api.php:120-155`) and all callers of `WalletController` / `DepositController` to confirm no hidden alias for the missing endpoint.
- Sibling tests read: skimmed `WalletTransactionsControllerTest.php` and the wallet-transactions feature tests for the audit-of-truth pattern.
- Architecture boundaries respected (FrontendApi, wallet_transactions, state machine): yes — by not adding any code that would cross them under a wrong assumption.

## Reviewer risk notes

1. **Highest risk if I had implemented blindly:** introducing `config('wallet.daily_deposit_limit', 100000)` and a brand new route `POST /api/v1/wallet/deposit` that no UI/client calls, that doesn't actually credit `wallet_transactions`, and that ships an idempotency table with no admin/ops UI to inspect or expire entries. That's a textbook PR-#65-style "resolved but ignored" + dead-config blocker.
2. **Second risk:** writing tests that pass against a fake controller. Reviewer would (correctly) reject because no production caller exercises the path.
3. **Real next step risk:** if the user confirms Option A, the change touches `SmkPayController` / `DeepPayController` which live in the `Gametech\Payment` package. From `FrontendApi` we must NOT call those controllers directly (CLAUDE.md hard rule). Validation must be added inside the Payment package (or a shared FormRequest under a neutral namespace) and called by the existing controllers, not bridged from FrontendApi.

## Confidence

- **High** that the spec's `POST /api/wallet/deposit` endpoint, `config('wallet.daily_deposit_limit')` key, and idempotency machinery do not exist in this codebase as of `main` (HEAD `ddcbff56`). I read the route file, both candidate controllers in full, the config dir listing, and grepped the whole repo for the relevant identifiers.
- **High** that the right next action is to surface the mismatch to the user and let them choose Option A (retrofit existing provider deposit endpoints) or Option B (design a new unified deposit endpoint as a separate feature ticket), rather than guessing.
- **Medium** that Option A is the user's actual intent; phrasing like "wallet service / state machine" and "wallet_transactions audit pattern" suggests they expected an existing service layer that this monolith does not yet have.
