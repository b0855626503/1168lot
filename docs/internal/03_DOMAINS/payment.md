# Payment Domain Note

อัปเดตล่าสุด: 2026-06-18

## ใช้อ่านเมื่อ

- แก้ deposit channel/payment gateway
- แก้ callback/expire/status flow
- แก้ payment integration ที่กระทบ wallet ledger
- เพิ่ม payment provider ใหม่ (see also `[[pattern-payment-provider]]`)

## Entry Points

- Package root: `packages/Gametech/Payment/src/`
- API integration routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controllers: `packages/Gametech/Payment/src/Http/Controllers/`
- Wallet impact: `packages/Gametech/Wallet/src/`

## Payment Providers

| Provider | Code | Auth | Deposit | Withdraw | Status |
|----------|------|------|---------|----------|--------|
| WealthPay | 317 | HMAC-SHA256 + merchant_id/token | Payment URL redirect | Async webhook | Live |
| FlashPay | 318 | X-API-Key header | QR PromptPay | Idempotency-Key + webhook | Live |
| DeepPay | 316 | HMAC-SHA256 | Payment URL redirect | Async webhook | Live |
| SmkPay | 313 | HMAC-SHA256 + PaymentProviderAccount | QR | Async webhook | Live |

## Provider Files (9-file pattern)

ต่อ provider: Config + Library + Controller + UpdateBalanceJob + PayoutJob
+ Payment Routes + FrontendApi Routes + Admin WithdrawController + logging.php
Full pattern: `[[pattern-payment-provider]]`

## กติกาหลัก

- payment flow ต้องไม่ทำให้ ledger semantics เพี้ยน
- endpoint/contract เปลี่ยนต้องอัปเดต doc ในรอบเดียวกัน

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- wallet policy -> `docs/internal/03_DOMAINS/wallet.md`
- API docs -> `docs/public/api/frontend-v1/03-endpoints.md`
- system flow -> `docs/internal/01_SYSTEM/system-current-state/02-flows.md`
