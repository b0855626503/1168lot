# Payment Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแล deposit/payment integration และ callback/status lifecycle ที่กระทบ wallet ledger

## Key Endpoints

- `GET /api/v1/deposit/channels`
- `POST /api/v1/deposit/loadbank`
- `GET /api/v1/smkpay/deposit/status/{txid}`
- `POST /api/v1/smkpay/deposit/expire/{txid}`
- `POST /api/v1/smkpay/deposit/create`
- `GET /api/v1/smkpay/qrcode/{id}`

## Module Map

- `packages/Gametech/FrontendApi/src/Routes/api.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/DepositController.php`
- `packages/Gametech/Payment/src/`

## Main Services / Actions

- channel discovery / bank resolution
- create payment transaction
- provider status + expire handling

## Important Dependencies

- wallet ledger update path
- gateway/provider config + callback contract

## Short Execution Flow

- frontend request -> DepositController/provider controller -> payment domain -> wallet side effects

## Source-of-Truth References

- `docs/internal/03_DOMAINS/payment.md`
- `docs/public/api/frontend-v1/03-endpoints.md`
- `docs/internal/03_DOMAINS/wallet.md`
