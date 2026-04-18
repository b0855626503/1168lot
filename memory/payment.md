# Payment Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแล deposit/payment integration และ callback lifecycle ที่มีผลต่อ wallet ledger

## Key Flows (สั้น)

- load channel -> create payment -> track status/expire
- provider callback -> validate -> update payment state -> sync wallet

## Important Modules

- `packages/Gametech/Payment/src/`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/DepositController.php`
- `packages/Gametech/FrontendApi/src/Routes/api.php`

## Dependencies

- wallet ledger update path
- gateway/provider config และ webhook endpoints
