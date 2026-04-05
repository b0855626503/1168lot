# Realtime Domain Note

อัปเดตล่าสุด: 2026-04-06

## ใช้อ่านเมื่อ

- เพิ่ม event ใหม่
- แก้ websocket / Echo / channel policy
- แก้ badge/toast/shared feed

## กติกาหลัก

- ทีมงานใช้ `{APP_NAME}_events`
- สมาชิกใช้ `{APP_NAME}_members`
- event ของทีมงานห้าม expose ให้ลูกค้า
- realtime total/badge ของ lotto tickets ต้องตรงกับ active-only semantics

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision_log.md`
