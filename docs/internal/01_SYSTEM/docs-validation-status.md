# Docs Validation Status

อัปเดตล่าสุด: 2026-03-27

## ตรวจได้แล้ว (Phase 1)
- Required files
- Docs structure
- Markdown placement/naming (pragmatic)
- Plans README presence

## ตรวจได้แล้ว (Phase 2)
- Plan metadata header (สถานะ/วันที่/โดเมน-เรื่อง/แทนแผนเก่า)
- ACTIVE count policy (ACTIVE > 1 = fail, ACTIVE = 0 = warn)
- Broken internal doc paths (เฉพาะ `docs/` และ relative links)

## พฤติกรรมการรัน
- ใช้ `bash scripts/docs-validation/run.sh`
- ถ้ามี `[ERROR]` อย่างน้อย 1 รายการ: exit 1
- `[WARN]` ไม่ทำให้ fail
- มี summary จำนวน errors/warnings ตอนท้าย

## TODO (Phase 3)
- Code vs Doc sync policy (ตรวจ business logic เปลี่ยนแต่ docs ไม่เปลี่ยน)
- Domain-specific doc sync rules (เช่น Lotto Draw/Settlement lifecycle)
- ทำ ACTIVE ต่อ domain แบบละเอียด (ตอนนี้ใช้ fallback แบบรวมทั้งโฟลเดอร์)

## หมายเหตุ pragmatic
- ใช้ `docs/04_PLANS` เป็น source of truth ของ plans (ถ้ามี)
- ไฟล์ underscore เดิมใน docs ยัง allow แบบ warning และจะ migrate ในเฟสถัดไป
- `.github/*.md` อยู่ใน allowlist ชั่วคราวสำหรับ workflow/documentation ของ repository
