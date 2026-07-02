# ADR-003: Webhook Security Policy — Mandatory Signature Verification

**Date**: 2026-06-24
**Status**: Proposed
**Deciders**: Deep Analysis (6 perspectives)

---

## Context

12 of 14 active payment providers have **zero webhook authentication**. Callback URLs are publicly accessible (`/api/{provider}/deposit/callback`) with:
- No HMAC signature verification
- No IP whitelisting
- No API key validation
- CSRF protection explicitly disabled for all callback paths (`VerifyCsrfToken.php:14`)

The only protection is `tx_hash`/`txid` uniqueness at the database level, which prevents duplicate entries but does NOT prevent forged callbacks with new transaction IDs.

Only FlashPay and WealthPay implement HMAC-SHA256 signature verification — and both have it **gated behind a config flag that defaults to `false`**.

This was identified by 3 perspectives (Security, Integration, Data) as the single largest security gap in the system.

## Decision

**Webhook signature verification will be mandatory for all payment providers.** This will be enforced through a shared `WebhookVerifier` middleware or trait that:

1. **Verifies HMAC-SHA256 signature** against raw request body using provider-specific secret
2. **Returns HTTP 403** for unverified callbacks (not 200 — to prevent retry loops masking the failure)
3. **Supports multiple signature header formats**: `X-Signature`, `X-Webhook-Signature`, `X-Payment-Signature`
4. **Is configurable per provider** with `{PROVIDER}_WEBHOOK_SECRET` and `{PROVIDER}_VERIFY_WEBHOOK=true` env vars
5. **Defaults to `VERIFY_WEBHOOK=true`** for new providers, with explicit opt-out requiring justification

For providers that do NOT document webhook signing in their API docs, we will:
- Contact the provider to confirm webhook security mechanism
- If unavailable: implement IP allowlisting as minimum fallback
- If IP is dynamic: implement shared secret via custom HTTP header as negotiated with provider

## Consequences

### Positive
- Closes the callback forgery attack vector for all providers
- Standardized verification across all providers → one code path to audit
- HTTP 403 on unverified callbacks gives clear signal vs silent failures
- Config-based enable/disable allows gradual rollout

### Negative
- Providers without documented signing require coordination (contact provider, negotiate)
- If a provider changes their signing mechanism, all callbacks fail until config is updated
- Performance: HMAC computation on every callback (negligible — microseconds)

### Neutral
- Monitoring needed: alert on 403 rates per provider to detect config mismatches
- Secrets management: webhook secrets added to .env, need rotation policy

## Migration Path

1. Create `VerifyPaymentWebhook` middleware with HMAC-SHA256 verification
2. Apply to FlashPay and WealthPay callbacks (already have signature logic, just extract it)
3. Set `FLASHPAY_VERIFY_WEBHOOK=true` and `WEALTHPAY_VERIFY_WEBHOOK=true` in production
4. For remaining providers:
   - Week 1-2: Contact top-volume providers for webhook signing documentation
   - Week 2-3: Implement IP whitelisting as interim measure
   - Week 3-4: Roll out signature verification provider by provider
5. Add monitoring: alert if webhook verification failure rate exceeds 1%

## Open Questions

1. What's the fallback timeline if a provider confirms they cannot support webhook signing?
2. Should we implement a "verification logging mode" first (verify but don't reject) to detect false positives?
3. What's the secret rotation policy? Annual? On security incident?
4. Should webhook secrets be stored in a vault (vs .env)?
