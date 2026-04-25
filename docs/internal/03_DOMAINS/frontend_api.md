# FrontendApi Domain Note

อัปเดตล่าสุด: 2026-04-25

## ใช้อ่านเมื่อ

- ทำ route ใหม่ใน `FrontendApi`
- แก้ payload ฝั่งลูกค้า
- ทำ auth/member/wallet/game/lotto API

## กติกาหลัก

- `FrontendApi` เป็น BFF ของลูกค้า
- ห้ามเรียก controller ของ package อื่นโดยตรง
- อนุญาตให้ reuse ผ่าน repository, model, query, domain service
- response contract ของลูกค้าต้องอยู่ใน `FrontendApi`
- `GET /api/v1/meta/site` ส่ง `header_code` จาก `configs.header_code` และ `deposit_min` จาก `configs.deposit_min`
- `GET /api/v1/member/profile` ส่ง `profile.deposit_min` จาก `configs.deposit_min`
- Deposit API:
  - `POST /api/v1/deposit/loadbank` คืนบัญชีรับฝากตาม method เดิม
  - `POST /api/v1/deposit/loadbank/random` คืนบัญชีรับฝากแบบสุ่ม 1 รายการสำหรับ `bank|tw|slip`
  - field รูป `bank_pic` และ `qr_pic` ถูก normalize เป็น public URL ผ่าน `BaseController`

## เส้นสำคัญที่มีอยู่

- `auth/*`
- `member/profile|balance|contributor|history`
- `wallet/transactions|withdraw|claim`
- `reward/list|redeem|history`
- `coupon/*`
- `games/*`
- `lotto/*`
- `wheel/*`

## Entry Points

- Route: `packages/Gametech/FrontendApi/src/Routes/api.php`
- Controllers: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/`
- Middleware: `packages/Gametech/FrontendApi/src/Http/Middleware/`
- Public API docs: `docs/public/api/frontend-v1/index.md`

## ข้อควรจำ

- ลูกค้าห้ามใช้ admin realtime channel
- ประวัติการเงินรวมยึด `wallet_transactions`
- ถ้าเพิ่ม endpoint ใหม่ ต้องอัปเดต public API doc
- Lotto navbar config read model อยู่ใน FrontendApi endpoint:
  - `GET /api/v1/lotto/navbar-config`
  - `code` เป็น optional query (default `mobile_bottom_nav`)
  - public response อ่านเฉพาะ published row

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- route/payload ปัจจุบัน -> `docs/public/api/frontend-v1/index.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision-log/index.md`
- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system-current-state/index.md`
