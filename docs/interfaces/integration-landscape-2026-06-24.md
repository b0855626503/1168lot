# Integration Landscape — 2026-06-24

> **Perspective**: 🔌 Integration — external APIs, webhooks, payment gateways, failure modes

---

## Payment Gateway Inventory (16 providers, 11+ active)

| # | Provider | Bank Code | Webhook Auth | Retry | Signature |
|---|----------|-----------|-------------|-------|-----------|
| 1 | WildPay | 300 | None | No | None |
| 2 | XEPay | — | None | No | None |
| 3 | KingPay | — | None | No | None |
| 4 | WellPay | — | None | No | None |
| 5 | APay | — | None | ✅ retry(2, 250ms) | None |
| 6 | TlConnectPay | — | None | No | None |
| 7 | OnPay | — | None | Commented ref to 3x backoff | None |
| 8 | MaxPay | — | None | No | None |
| 9 | AutoTransfer | — | Optional API key | No | Optional HMAC |
| 10 | SmkPay | — | None | No | None |
| 11 | PayoneX | 307 | None | No | Cancel only (HMAC) |
| 12 | DeepPay | — | None | No | None |
| 13 | WealthPay | 317 | HMAC-SHA256 (configurable) | No | ✅ Request + callback |
| 14 | FlashPay | 318 | HMAC-SHA256 (configurable) | No | ✅ Callback only |
| 15 | MatePay | — | Deprecated | — | — |
| 16 | SulifuPay | — | Deprecated | — | — |

---

## Deposit Flow (End-to-End)

```
1. POST /member/{provider}/deposit/create
   → Controller.deposit() validates amount, resolves bank_account
   → Library.create() calls external API (QR generation)
   → Creates check_cases record (status=PENDING/CREATE)
   → Returns QR URL to frontend

2. External payment received → provider sends webhook
   → Controller.deposit_callback() receives JSON
   → Updates check_cases.status (PENDING→PAID/SUCCESS)
   → Creates bank_payment record (autocheck='W', status=0)
   → Dispatches UpdateBalance{Provider} job

3. BankPaymentObserver.created()
   → TopupPayments job dispatched (2s delay)

4. TopupPayments.handle()
   → Idempotency: skip if status!=0 or autocheck!='W'
   → Orphan allLog cleanup
   → Routes to refillPayment*() based on mode
   → Creates bills, member_credit_logs, updates member balance
```

## Withdraw Flow (End-to-End)

```
1. POST /wallet/withdraw
   → Creates withdraws record (status_withdraw='A' or 'P')
   → Job dispatches withdrawal request to external API

2. External system processes → sends webhook
   → Controller.withdraw_callback() matches by txid/referenceId
   → SUCCESS: status_withdraw='C', credit_log created
   → FAILURE: status_withdraw='F'/'R', balance rollback
   → Broadcasts RealTimeNewMessage to admin panel
```

---

## Provider Deep Dives

### AutoTransfer (Most Sophisticated)
- **3-pass member matching**: bank+account → name disambiguation → fallback
- **Transaction isolation**: `DB::transaction` + `lockForUpdate()`
- **Fatal error handling**: Sets HOLD state, NEVER returns non-200 to provider
- **Ambiguous failure**: Different handling for `success`/`ambiguous_failure`/`failure`
- **File**: `AutoTransferController.php:120-1057`

### FlashPay (Best Webhook Security)
- **Signature**: `hash_equals(hash_hmac('sha256', rawBody, secret), signature)` — `FlashPay.php:177-195`
- **Separate secrets**: Different secrets for deposit vs withdraw webhooks
- **Idempotency**: `Idempotency-Key` header on withdraw creation
- **Transport**: Raw curl (not Laravel Http client) with configurable timeouts

### WealthPay (Request + Callback Signing)
- **Every outbound request**: HMAC-SHA256 signature in `X-Signature` header
- **Every callback**: Signature verification (configurable on/off)
- **Replay protection**: `time` field in request body
- **Error detection**: HTTP 409 = duplicate pending order

---

## Webhook Security by Provider

| Provider | Signature Verification | Config Gate | Default | Risk if Off |
|----------|----------------------|-------------|---------|-------------|
| FlashPay | ✅ HMAC-SHA256 | `flashpay.verify_callback_signature` | false | 🔴 HIGH |
| WealthPay | ✅ HMAC-SHA256 | `wealthpay.verify_callback_signature` | false | 🔴 HIGH |
| AutoTransfer | ⚠️ Optional API key | config key presence | depends | 🟡 MEDIUM |
| All others | ❌ None | N/A | N/A | 🔴 HIGH |

---

## Lotto External Result Sources

### Architecture
- **DB-driven config**: `LottoResultSource` model — `endpoint_url`, `http_method`, `headers_json`, `timeout_seconds`, `priority`, `is_active`
- **Multi-driver fetch**: HtmlHttp, JsonHttp, RenderedBrowser, ManualInput, EmbeddedJson
- **Pipeline**: FetchExecutor → ExtractExecutor → SelectionExecutor → NormalizeComposeExecutor → ValidationExecutor
- **Cache**: Past dates 1800s TTL, current date 60s TTL — `CentralLotteryResultService.php:144-205`

### Result Source Drivers
- `ExpalertResultDriver` — HTML scraping
- `DowjonesMidnightResultDriver` — stock market-derived
- `ExphuayPythonWorkerClient` — Python worker for browser rendering
- `HttpResultFetcher` — generic HTTP fetch
- `InternalResultService` — routes to appropriate driver

---

## Failure Modes & Resilience

| Risk | Severity | Providers | Details |
|------|----------|-----------|---------|
| No webhook signature | 🔴 HIGH | 12 providers | Callback forgery possible |
| No retry on outbound | 🟡 MEDIUM | All except APay | Single-attempt for API calls |
| No circuit breaker | 🟡 MEDIUM | All | 30s timeout blocks workers |
| Race: callback before DB | 🔴 HIGH | AutoTransfer, WildPay | May silently drop callback |
| Orphan allLog records | 🟡 MEDIUM | All | Cleaned by next TopupPayments |
| Rollback failure | 🔴 CRITICAL | AutoTransfer, WildPay | Money in limbo if restore fails |
| Ambiguous withdraw | 🟡 MEDIUM | AutoTransfer | HOLD status, no auto-recovery |
| Bank code mismatch | 🟡 MEDIUM | All | Silent false/wrong code |
| No dead letter queue | 🟡 MEDIUM | All | Failed TopupPayments (tries=1) not retried |
