# แผนงาน: ตรวจสอบและปรับปรุง Query ซ้ำซ้อน/ประสิทธิภาพในทุกเมนู Admin (ADMIN_QUERY_AUDIT_2026)

## วัตถุประสงค์
- วิเคราะห์ log query ช้าและ query ซ้ำซ้อนในฝั่ง Admin
- ปรับปรุง index/query ให้มีประสิทธิภาพขึ้น โดยไม่เปลี่ยนผลลัพธ์
- สรุปผลแยกตาม route/action พร้อมรายละเอียด patch
- บันทึกเอกสารสรุปผลและ patch ที่ดำเนินการ

## ขั้นตอนดำเนินการ
1. วิเคราะห์ log (storage/logs/slow-requests.log) เพื่อ group ตาม url/route/action และ sql query เฉพาะ admin context
2. ตรวจสอบแต่ละเมนู/route ฝั่ง Admin ว่ามี query ซ้ำซ้อนหรือ query เดิมถูกยิงซ้ำหรือไม่
3. ประเมินประสิทธิภาพ query (execution time, pattern, join, where, limit ฯลฯ)
4. ตรวจสอบโครงสร้าง index ของ table ที่เกี่ยวข้องกับ query ช้า/ซ้ำซ้อน
5. ออก patch สำหรับปรับปรุง index หรือ query ให้มีประสิทธิภาพขึ้น (เพิ่ม index, ปรับ where/join, limit, eager loading ฯลฯ) โดยต้องไม่ทำให้ผลลัพธ์เปลี่ยนแปลง
6. บันทึกเอกสารสรุปผลการตรวจสอบและรายการ patch ที่ดำเนินการลงในโปรเจค (docs/ADMIN_QUERY_AUDIT_2026.md) โดยแยกตาม route และเขียนเป็นภาษาไทย พร้อมระบุรายละเอียด table/index ที่ปรับ

## ตัวอย่างการสรุปผล (จะจัดทำในเอกสารสรุป)

### admin.home.loadcnt
- พบ query ซ้ำซ้อนกับ table bank_payment, withdraws_seamless, members ฯลฯ
- ข้อเสนอ: รวม query ที่เหมือนกัน, เพิ่ม index ที่ field date_create, value, enable, status
- Patch: ALTER TABLE bank_payment ADD INDEX idx_enable_status_date (enable, status, date_create);

### admin.member.index
- พบ query join และ count หลายรอบ
- ข้อเสนอ: ใช้ eager loading, เพิ่ม composite index ที่ใช้ join/filter
- Patch: ALTER TABLE members ADD INDEX idx_confirm_datecreate (confirm, date_create);

## หมายเหตุ
- หากพบ query ที่ต้อง refactor code มาก จะดำเนินการทันทีโดยระบุรายละเอียดในเอกสาร
- เอกสารจะสรุปผลแยกตาม route และระบุรายละเอียด table/index ที่ปรับปรุง
- จะสรุปผลการดำเนินการทั้งหมดในเอกสารเดียว

## Output
- เอกสารสรุปผล: docs/ADMIN_QUERY_AUDIT_2026.md
- Patch SQL: รวมในเอกสารสรุป
- รายละเอียดแยกตาม route/action

---
วันที่สร้างแผน: 21 มีนาคม 2026

