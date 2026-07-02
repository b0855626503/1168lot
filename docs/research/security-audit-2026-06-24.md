# Security Audit — 2026-06-24

> **Perspective**: 🔐 Security — auth flows, authorization, financial integrity, threat model
> **Method**: Trace full auth/security flows from entry to decision to response

---

## 1. Authentication Architecture

### 1.1 Dual Guard Session Auth + JWT BFF
- **Session guards**: `customer` (Member model), `admin` (Admin model) — `config/auth.php:41-62`
- **Admin 2FA**: Google2FA via `LoginSecurityMiddleware` — optional TOTP second factor
- **FrontendApi JWT**: HS256 using `app.key`, single-active-token model (latest token invalidates previous), blacklist via Cache — `FrontendTokenService.php:14-137`
- **Customer 2FA**: ❌ NOT SUPPORTED — gap for high-value accounts

### 1.2 Defense-in-Depth
- Login throttling via `ThrottlesLogins` trait (~5 attempts/min) — `LoginController.php:474-478`
- Unicode sanitization on login inputs (ZWSP, ZWJ, BOM stripped) — `LoginRequest.php:32-82`
- Session invalidation: `logoutOtherDevices` on login, `session_id` tracked on Member model — `LoginController.php:1550,1571`

## 2. Authorization

### 2.1 Admin ACL (Bouncer)
- Custom `Bouncer` class with role-based permissions — `Admin/src/Bouncer.php:1-54`
- Superadmin bypass: `$admin->superadmin == 'Y'` skips all permission checks
- Permission format: dotted keys like `admin.marketing_team.index`

### 2.2 Customer Route Protection
- `RedirectIfNotCustomer` middleware: checks `enable='Y'` and `confirm='Y'` on every request — `RedirectIfNotCustomer.php:21-44`
- Webhook/callback routes OUTSIDE `member` prefix — correctly no auth required

## 3. Financial Security (CRITICAL)

### 3.1 Race Condition: Cache::has()/Cache::put() Pattern
- **Files**: `TransferGameController.php:270-276`, `TransferWalletController.php:232-237`, `WithdrawController.php:418-422`
- **Risk**: 🔴🔴 CRITICAL — double-spend possible on concurrent transfers
- **Fix**: Replace with atomic `Cache::lock($key, 30)->get()` pattern

### 3.2 BillRepository::transferGame — Wallet Debit Outside Transaction
- **File**: `BillRepository.php:402-441`
- **Risk**: 🔴 HIGH — if game deposit fails and `auto_wallet='N'`, funds lost silently
- **Fix**: Move wallet debit inside DB transaction

### 3.3 Plaintext Password Storage
- **File**: `LoginController.php:326,330`
- **Risk**: 🔴🔴 CRITICAL — `user_pass` stores plaintext alongside hashed `password`
- **Fix**: Drop `user_pass` column, rotate all passwords

### 3.4 Withdraw Concurrency Control (Good Pattern)
- `withdrawSingle()` and `withdrawSeamless()` use `lockForUpdate()` + DB transaction — CORRECT
- Legacy `withdraw()` method does NOT use `lockForUpdate()` — still exploitable
- `store_api` withdraw has `Cache::lock` COMMENTED OUT — needs restoration

### 3.5 Payment Callback Security
- **Webhook signature verification**:
  - ✅ FlashPay: HMAC-SHA256 via `X-Webhook-Signature` — `FlashPay.php:177-195`
  - ✅ WealthPay: HMAC-SHA256 via `X-Signature` — `WealthPay.php:158-173`
  - ❌ All other providers: NO verification
- **Config gate**: Both FlashPay and WealthPay have `verify_callback_signature` defaulting to `false`
- **CSRF exemption**: All callback URLs excluded from CSRF in `VerifyCsrfToken.php:14`
- **No IP whitelisting**: No callback endpoint checks requester IP
- **Risk**: 🔴 HIGH — attackers who discover callback URLs can forge deposits for 12/14 providers

## 4. Input Validation & Data Protection

### 4.1 Weak Password Policy
- Min length: 4-6 characters, no complexity requirements — `LoginController.php:219-243,540-563,797-821`
- No mixed case, special char, or digit requirements

### 4.2 Login CSRF Exclusion
- `login` path excluded from CSRF in `VerifyCsrfToken.php:14`
- Risk: Login CSRF — attacker can force victim to authenticate as attacker's account

### 4.3 CORS Wildcard
- `config/cors.php:29,34`: `allowed_origins = ['*']`
- Any website can make cross-origin requests to API

## 5. Broadcast Security

### 5.1 Public Events Channel
- `routes/channels.php:35-37`: `_events` channel returns `true` unconditionally — no auth
- Risk: Anyone with WebSocket connection can listen to all broadcast events

### 5.2 Private Channels (Correct)
- Member channels: `(int)$user->code === (int)$id` — correct ownership check
- Admin channels: same pattern

## 6. Threat Model Summary

| # | Threat | Severity | Status |
|---|--------|----------|--------|
| 1 | Plaintext password storage | CRITICAL | ❌ Unfixed |
| 2 | Transfer race condition | CRITICAL | ❌ Unfixed |
| 3 | Webhook forgery (12 providers) | HIGH | ❌ Unfixed |
| 4 | Wallet debit outside transaction | HIGH | ❌ Unfixed |
| 5 | `store_api` no lock | HIGH | ❌ Unfixed |
| 6 | Legacy withdraw no lockForUpdate | HIGH | ❌ Unfixed |
| 7 | Weak password policy | MEDIUM | ❌ Unfixed |
| 8 | Public broadcast channel | MEDIUM | ❌ Unfixed |
| 9 | Login CSRF exclusion | MEDIUM | ❌ Unfixed |
| 10 | Wildcard CORS | MEDIUM | ❌ Unfixed |
