# Wallet Memory

อัปเดตล่าสุด: 2026-04-18

## Responsibility

ดูแลยอดเงินสมาชิก, transaction history, claim flow โดยยึด ledger semantics

## Key Flows (สั้น)

- transactions read -> unified member cash history
- claim -> validate source type -> append ledger transaction
- withdraw -> request -> policy check -> ledger/state transition

## Important Modules

- `packages/Gametech/Wallet/src/`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WalletController.php`
- `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/WithdrawController.php`
- `database/migrations/`

## Dependencies

- payment callbacks
- lotto bet/refund flows
- realtime member activity events
