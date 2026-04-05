# Admin Lotto Domain Note

อัปเดตล่าสุด: 2026-04-06

## ใช้อ่านเมื่อ

- ทำหน้า admin ของ Lotto
- แก้ report / DataTable / Vue report
- แก้ badge / toast / loadCnt

## กติกาหลัก

- `/lotto/tickets` แสดงเฉพาะ `active`
- badge เมนู Lotto ต้องยึด `DashboardController@loadCnt`
- หน้า Lotto ทุกหน้าต้อง trigger `loadCnt`
- report ที่มี filter ตลาด ใช้ grouped select และ immediate apply

## ข้อควรจำ

- `profit-loss-forecast` เป็น Vue report ไม่ใช่ DataTable ปกติ
- semantic badge/toast ต้องตรงกับ dataset ของเมนูจริง

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision_log.md`
