# Wallet Domain Note

อัปเดตล่าสุด: 2026-04-06

## ใช้อ่านเมื่อ

- แตะยอดเงินสมาชิก
- ทำ ledger / refund / claim / history
- reconcile หรือ audit รายการเงิน

## กติกาหลัก

- `wallet_transactions` คือ financial source of truth
- งานใหม่ต้องรักษา append-only ledger semantics
- ห้ามเปลี่ยนยอดกระเป๋าหลักโดยไม่มี transaction context

## flow สำคัญ

- deposit / withdraw
- lotto bet / lotto refund
- referral / cashback / ic / bonus
- admin adjust / rollback

## claim โบนัส/ค่าแนะนำ

- `FrontendApi` ใช้ `POST /api/v1/wallet/claim`
- map `bonus|faststart|cashback|ic` ไป legacy id เดิม
- ใช้ domain repository โดยตรง ไม่เรียก controller package `Wallet`

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- policy ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
- decisions -> `docs/internal/02_DECISIONS/decision_log.md`
- future ledger work -> `docs/04_PLANS/2026-03-21_wallet-ledger-implementation.md`
