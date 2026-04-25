# Realtime Domain Note

อัปเดตล่าสุด: 2026-04-25

## ใช้อ่านเมื่อ

- เพิ่ม event ใหม่
- แก้ websocket / Echo / channel policy
- แก้ badge/toast/shared feed

## กติกาหลัก

- ทีมงานใช้ `{APP_NAME}_events`
- สมาชิกใช้ `{APP_NAME}_members`
- event ของทีมงานห้าม expose ให้ลูกค้า
- realtime total/badge ของ lotto tickets ต้องตรงกับ active-only semantics
- `public.activity.updated` ที่เป็น Lotto feed ต้องแนบ `message` พร้อมแสดงผลสำหรับ `lotto.draw_closed`, `lotto.draw_resulted`, `lotto.draw_status_changed`, และ `lotto.ticket.list.changed`

## Entry Points

- Realtime config/auth controllers: `packages/Gametech/FrontendApi/src/Http/Controllers/Api/V1/RealtimeController.php`
- Broadcast jobs/events: `app/Events/`, `app/Jobs/`, `app/Notifications/`
- Channel policy/config: `config/broadcasting.php`, `routes/channels.php` (ถ้ามี)

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system-current-state/index.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision-log/index.md`
