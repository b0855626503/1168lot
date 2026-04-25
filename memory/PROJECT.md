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

3. **Admin status route + dashboard load blocking** (2026-04-25)
   - แก้ admin root `/` ให้กลับไปหน้า login แทน redirect ไป `/status`
   - แยก status ping เป็น `status.ping` ที่ `/status/ping` เพื่อไม่ชน route name ของ admin package
   - Dashboard admin จำกัด request หนักด้วย queue concurrency, โหลดข้อมูลรองตามหลัง, abort request พื้นหลังเมื่อกดเมนูอื่น
   - ตัด initial refresh ซ้ำจาก datepicker initialization เพื่อให้เปิด dashboard แล้วโหลดรอบเดียว
   - Location: `routes/web.php`, `resources/views/status/index.blade.php`, `packages/Gametech/Admin/src/Resources/views/module/dashboard/index.blade.php`

## Architecture Decisions (ต้องจำ):
- **ADR-003**: `wallet_transactions` คือ financial source of truth
- **ADR-005**: Ticket cancellation ต้องเก็บ audit context
- **ADR-011**: Admin `loadCnt` คือ single aggregate source สำหรับ Lotto menu badges
- **Admin Dashboard Load Policy**: หน้า dashboard ต้องไม่ block navigation; request พื้นหลังต้องถูกจำกัด concurrency และ abort ได้เมื่อ user ไปเมนูอื่น

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
