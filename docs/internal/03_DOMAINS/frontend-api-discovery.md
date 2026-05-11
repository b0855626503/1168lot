# Frontend API Discovery Map

> **Last Verified:** 2026-05-03 | **Source:** manual | **Confidence:** medium | **Stability:** volatile
> ไฟล์นี้เป็น derived snapshot — อาจ drift ได้ถ้า code เปลี่ยนโดยไม่อัปเดต
> **ให้ verify entrypoint ด้วย `rg` เสมอก่อนตัดสินใจ**

แผนที่สำหรับค้น code path — ใช้คู่กับ `frontend-api.md` และ `docs/internal/00_RULES/code-discovery-protocol.md`

---

## Routes (authoritative)

- `packages/Gametech/FrontendApi/src/Routes/api.php`

## Auth

- Entrypoint: `POST /api/v1/auth/login`
- Controller: verify with `rg "AuthController\|auth/login" packages/Gametech/FrontendApi/`
- Search keywords: `AuthController`, `auth/login`, `Bearer`, `access_token`

## Member / Profile

- Entrypoints: `GET /api/v1/member/profile`, `GET /api/v1/member/balance`
- Controller: verify with `rg "MemberController" packages/Gametech/FrontendApi/`
- Search keywords: `MemberController`, `member/profile`, `member/balance`, `header_code`

## Wallet / Withdraw / Claim

- Controller: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- Withdraw: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WithdrawController.php`
- Endpoints: `POST /api/v1/wallet/claim`, `GET /api/v1/wallet/transactions`
- Search keywords: `WalletController`, `WithdrawController`, `wallet/transactions`

## Lotto API

- Endpoints: `POST /api/v1/lotto/bet`, `GET /api/v1/lotto/navbar-config`
- Controller: verify with `rg "lotto/bet\|LottoBet\|LottoController" packages/Gametech/FrontendApi/`
- Search keywords: `lotto/bet`, `navbar-config`, `lotto_tickets`

## Deposit / Payment

- Entrypoints: `POST /api/v1/deposit/loadbank`, `POST /api/v1/deposit/loadbank/random`
- Controller: verify with `rg "DepositController\|loadbank" packages/Gametech/FrontendApi/`
- Search keywords: `loadbank`, `DepositController`, `bank_account`, `smkpay`

## Reward / Coupon / Games

- Controller: verify with `rg "RewardController\|CouponController\|GamesController" packages/Gametech/FrontendApi/`
- Search keywords: `RewardController`, `CouponController`, `reward/list`, `games/`

## Public API Docs

- Active source: `docs/public/api/frontend-v1/07-route-reference.md`
- Index: `docs/public/api/frontend-v1/index.md`
- Tests: `tests/Feature/FrontendApi/`, `tests/Unit/FrontendApi/`
