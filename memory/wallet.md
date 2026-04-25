# Wallet Memory

อัปเดตล่าสุด: 2026-04-19

## Responsibility

ดูแลยอดเงินสมาชิก, history, claim, withdraw โดยรักษา ledger semantics

## Key Endpoints

- `GET /api/v1/member/profile`
- `GET /api/v1/member/balance`
- `GET /api/v1/member/history`
- `GET /api/v1/member/history/{type}`
- `POST /api/v1/member/change-password`
- `POST /api/v1/member/wallet-address`
- `GET /api/v1/wallet/transactions`
- `POST /api/v1/wallet/claim`
- `POST /api/v1/wallet/withdraw`
- `GET /api/v1/reward/list`
- `POST /api/v1/reward/redeem`
- `GET /api/v1/reward/history`

## Module Map

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/RewardController.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WithdrawController.php`
- `packages/Gametech/Wallet/src/`
- `database/migrations/`

## Main Services / Actions

- unified transactions read
- claim source validation + ledger append
- withdraw policy check + state transition
- reward catalog read + point redemption + redemption history timeline
- `MemberCashbackRepository::refillSeamlessDirect()` append `wallet_transactions` แบบ `TRANCB` เมื่อเครดิต cashback เข้า `member.balance`
- `cashback:start` รองรับ `--mode=range|daily` และ `--target=wallet|cashback`; daily mode ใช้วันธุรกิจเดียว และ target cashback จะเติมเข้า `member.cashback`

## Important Dependencies

- payment callback flows
- lotto bet/refund side effects
- realtime member activity events

## Short Execution Flow

- wallet endpoint -> controller -> wallet domain/repository -> ledger write/read -> response

## Source-of-Truth References

- `docs/internal/03_DOMAINS/wallet.md`
- `docs/public/api/frontend-v1/03-endpoints.md`
- `docs/internal/01_SYSTEM/system-current-state/02-flows.md`
