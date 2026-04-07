# 1168lot Project Memory

## Issues Fixed:
1. **Withdraw clear ไม่ทำงาน** (2026-04-07)
   - ปัญหา: เรียก `setWalletSingleWithdraw()` ซึ่งพยายามฝากเงินเข้าเกม
   - แก้ไข: เปลี่ยนเป็น `setWalletSeamlessWithdraw()` สำหรับคืนยอด
   - Location: `packages/Gametech/Admin/src/Http/Controllers/WithdrawController.php`

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