# 1168lot Project Memory

## Issues Fixed:
1. **Withdraw clear ไม่ทำงาน** (2026-04-07)
   - ปัญหา: เรียก `setWalletSingleWithdraw()` ซึ่งพยายามฝากเงินเข้าเกม
   - แก้ไข: เปลี่ยนเป็น `setWalletSeamlessWithdraw()` สำหรับคืนยอด
   - Location: `packages/Gametech/Admin/src/Http/Controllers/WithdrawController.php`

2. **Lotto Navbar Config v1** (2026-04-19)
   - เพิ่ม domain schema ใน Lotto package: `lotto_navbars`, `lotto_navbar_items`
   - เพิ่ม FrontendApi public endpoint: `GET /api/v1/lotto/navbar-config` (`code` optional, default `mobile_bottom_nav`)
   - publish model: `published_version` monotonic per `code`, public อ่านเฉพาะ active+published
   - locale fallback: requested -> `th` -> `en` -> `key`
   - default code unpublish/ไม่พบ published row ต้องตอบ `404` แบบคงที่

3. **Frontend API single active login** (2026-04-25)
   - สมาชิก 1 คนมี active token ได้ครั้งละ 1 ตัว; login ใหม่ invalidate token เดิมทันที

4. **Lotto public realtime message** (2026-04-25)
   - `.public.activity.updated` ของ Lotto แนบ `message` พร้อมแสดงผลสำหรับ draw closed/resulted/reopened และ ticket-list resulted update

5. **Frontend deposit minimum config exposure** (2026-04-25)
   - `GET /api/v1/meta/site` และ `GET /api/v1/member/profile` ส่ง `deposit_min` จาก `configs.deposit_min`
   - `/api/v1/deposit/loadbank` และ `/deposit/loadbank/random` ใช้ `bank_account.deposit_min` ก่อน ถ้าเป็น `0` จึง fallback ไป `configs.deposit_min`

## Architecture Decisions (ต้องจำ):
- **ADR-003**: `wallet_transactions` คือ financial source of truth
- **ADR-005**: Ticket cancellation ต้องเก็บ audit context
- **ADR-011**: Admin `loadCnt` คือ single aggregate source สำหรับ Lotto menu badges

## Tech Stack:
- **Backend**: Laravel 8 (กำลัง upgrade ไป 9)
- **Frontend**: Vue.js + Bootstrap
- **Database**: MySQL + Redis
- **Integrations**: Telegram API, Payment gateways

## Project Structure:
```
packages/Gametech/
├── Admin/          # Admin panel
├── FrontendApi/    # BFF สำหรับ frontend
├── Payment/        # Payment processing
├── Member/         # Member management
├── Lotto/          # Lottery system
└── Wallet/         # Wallet/Financial
```

## Important Notes:
- `/docs` คือ source of truth
- ห้ามใช้ chat history เป็นหลัก
- ทุกการเปลี่ยนแปลงต้องอัปเดต documentation
- ใช้ AGENTS.md สำหรับ startup instructions
