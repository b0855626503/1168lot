# Wallet Discovery Map

> **Last Verified:** 2026-06-18 | **Source:** manual | **Confidence:** medium | **Stability:** stable
> ไฟล์นี้เป็น derived snapshot — อาจ drift ได้ถ้า code เปลี่ยนโดยไม่อัปเดต
> **ให้ verify entrypoint ด้วย `rg` เสมอก่อนตัดสินใจ**

แผนที่สำหรับค้น code path — ใช้คู่กับ `wallet.md` และ `docs/internal/00_RULES/code-discovery-protocol.md`

---

## Balance Read

- Entrypoint: `GET /api/v1/member/balance`
- Controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- Source: `members.balance` (verify with `rg "balance" packages/Gametech/FrontendApi/`)
- Search keywords: `balance`, `WalletController`, `member/balance`

## Deposit

- Package: `packages/Gametech/Payment/src/`
- Entrypoint: provider-specific controllers (e.g. `SmkPayController`, `DeepPayController`)
- Tables write: `bank_payment`, `payments_waiting`, `wallet_transactions`
- Search keywords: `deposit`, `bank_payment`, `PaymentLog`, `payments_waiting`

## Withdraw

- Controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WithdrawController.php`
- Tables: `withdraws`, `wallet_transactions`
- Search keywords: `WithdrawController`, `withdraws`, `wallet/withdraw`

## Ledger / Transaction

- Source of truth: `wallet_transactions`
- Key fields: `direction` (CREDIT/DEBIT), `ref_type`, `ref_id`
- Service: `WalletTransactionService` — verify with `rg "WalletTransactionService" packages/`
- Search keywords: `wallet_transactions`, `WalletTransactionService`, `CREDIT`, `DEBIT`, `ref_type`

## Lotto Bet / Refund

- Written by: Lotto `BetService` / `SettlementService`
- Table: `wallet_transactions` with `ref_type` = lotto bet or refund
- Search keywords: `lotto_refund`, `lotto_bet`, `wallet_transactions`, `ref_type`, `SettlementService`

## Claim / Bonus

- Entrypoint: `POST /api/v1/wallet/claim`
- Maps to: `bonus|faststart|cashback|ic`
- Note: uses domain repository directly, not Wallet package controller
- Search keywords: `wallet/claim`, `cashback`, `WalletClaimController`, `ref_type=TRANCB`

## Cashback

- Config: `cashback:start --target=wallet|cashback`
- Table: `wallet_transactions` with `ref_type = TRANCB`
- Search keywords: `cashback:start`, `TRANCB`, `cashback`
