# Payment Domain Note

อัปเดตล่าสุด: 2026-04-18

## ใช้อ่านเมื่อ

- แก้ deposit channel/payment gateway
- แก้ callback/expire/status flow
- แก้ payment integration ที่กระทบ wallet ledger

## Entry Points

- Package root: `packages/Gametech/Payment/src/`
- API integration routes: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controllers: `packages/Gametech/Payment/src/Http/Controllers/`
- Wallet impact: `packages/Gametech/Wallet/src/`

## กติกาหลัก

- payment flow ต้องไม่ทำให้ ledger semantics เพี้ยน
- endpoint/contract เปลี่ยนต้องอัปเดต doc ในรอบเดียวกัน

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- wallet policy -> `docs/internal/03_DOMAINS/wallet.md`
- API docs -> `docs/public/api/frontend-v1/03-endpoints.md`
- system flow -> `docs/internal/01_SYSTEM/system-current-state/02-flows.md`
