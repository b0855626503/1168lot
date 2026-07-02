# ADR-001: Payment Provider Interface Standardization

**Date**: 2026-06-24
**Status**: Proposed
**Deciders**: Deep Analysis (6 perspectives)

---

## Context

The payment system currently has 19 provider controllers with identical method signatures (`deposit()`, `deposit_callback()`, `withdraw_callback()`) but no shared interface contract. The `Payment/src/Contracts/Payment.php` interface exists but is empty — no method signatures defined.

Each provider also has inconsistent:
- Webhook security: only 2/14 active providers implement HMAC signature verification
- Retry strategy: only APay has `retry(2, 250ms)`, OnPay has commented-out backoff reference
- Error handling: some return mock 500 responses, some throw exceptions, some silently swallow
- Transport layer: mix of Laravel Http facade, raw curl, and custom wrappers
- Idempotency: inconsistent across providers (Idempotency-Key vs tx_hash vs nothing)

This was identified by 3 perspectives (Integration, Security, Technical) as a systemic risk.

## Decision

We will define and enforce a `PaymentProviderContract` interface with the following methods:

```php
interface PaymentProviderContract
{
    public function createDeposit(DepositRequest $request): DepositResponse;
    public function createWithdraw(WithdrawRequest $request): WithdrawResponse;
    public function verifyWebhookSignature(Request $request): bool;
    public function processDepositCallback(array $payload): CallbackResult;
    public function processWithdrawCallback(array $payload): CallbackResult;
    public function checkStatus(string $txid): StatusResponse;
    public function cancelDeposit(string $txid): CancelResponse;
}
```

A `WebhookVerifier` trait will provide shared HMAC-SHA256 signature verification that all controllers can use.

Webhook signature verification will be **mandatory** for all providers. Providers without documented webhook signing will use the `WebhookVerifier` trait with config-driven secrets.

## Consequences

### Positive
- Single contract to implement for new providers
- Standardized error handling and retry behavior
- Mandatory webhook signature verification closes the biggest security gap
- Can write shared integration tests against the interface
- `PaymentProviderGeneratorV3` can scaffold against a real contract

### Negative
- 19 providers need migration to the interface (incremental, not big-bang)
- Providers without webhook signing docs need alternative auth (IP whitelist or shared secret)
- Risk of breaking existing callback handling during migration

### Neutral
- Library classes remain provider-specific (only Controller contract is standardized)
- Existing `Payment.php` empty interface can be replaced or extended

## Migration Path

1. Define `PaymentProviderContract` interface with all method signatures
2. Extract `WebhookVerifier` trait from FlashPay/WealthPay signature verification
3. Implement contract on 2 providers (FlashPay, WealthPay) as proof of concept
4. Add integration tests against the contract
5. Roll out to remaining providers incrementally:
   - Phase 1 (week 1-2): AutoTransfer, PayoneX, APay, WildPay
   - Phase 2 (week 3-4): OnPay, MaxPay, KingPay, WellPay, XEPay
   - Phase 3 (week 5-6): DeepPay, SmkPay, TlConnectPay, remaining

## Open Questions

1. Should the deprecated providers (MatePay, SulifuPay) implement the contract or be removed?
2. What's the fallback for providers where webhook IP is unknown and signature docs are unavailable?
3. Should the `PaymentProviderGeneratorV3` be updated to generate against the new contract?
