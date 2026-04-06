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
- `profit-loss-forecast` ต้องเลือก filter แบบ `ตลาด -> แพกเกจ -> งวดหวย` ก่อนโหลดข้อมูล
- payout/discount ในหน้า `profit-loss-forecast` ต้องอิงจาก package ที่เลือก
- ตารางเลขด้านล่างของ `profit-loss-forecast` รองรับ toggle `แสดงเฉพาะแถวที่มียอดแทง`
- ตาราง `profit-loss-forecast` ปรับ layout ให้เห็นข้อมูลครบในกรอบหน้าเป็นหลัก (ไม่บังคับ scroll แนวนอน)
- หน้า `profit-loss-forecast` รองรับ `Auto Reload` ทุก 10 วินาที พร้อม countdown บนปุ่ม
- รายงาน `tickets-cancel` รองรับปุ่ม `รายละเอียด` ต่อแถว เพื่อเปิด modal ดูรายการเลขในโพยที่เลือก
- `/lotto/draws` แสดงคอลัมน์ `เวลาออกผล` หลัง `ปิดรับ` และถอดคอลัมน์ `2 ตัวบน`
- `/lotto/draws` ให้คอลัมน์จำนวนรายการที่มีค่ามากกว่า 0 เป็นลิงก์เปิด modal รายการโพย
- `/lotto/reports/member-bet-types` ให้ filter และตารางสรุปแบบเดียวกับ `profit-loss-forecast` พร้อมตารางประเภท-เลขเรียงตามยอดเงินได้
- summary matrix ของ `profit-loss-forecast` ใช้ลำดับ metric:
  - ยอดแทง
  - ส่วนลด
  - ยอดรับ
  - ยอดจ่าย
  - ยอดสุทธิ
- semantic badge/toast ต้องตรงกับ dataset ของเมนูจริง

## เปิดไฟล์เพิ่มเมื่อจำเป็น

- behavior ปัจจุบัน -> `docs/internal/01_SYSTEM/system_current_state.md`
- decision ลึก -> `docs/internal/02_DECISIONS/decision_log.md`
