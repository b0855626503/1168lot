# Agent Rules (Source of Truth)

เอกสารนี้เป็นกฎกลางสำหรับการทำงานของ agent ในโปรเจกต์นี้
และเป็นไฟล์หลักที่ต้องอ้างอิงก่อนเริ่มแก้ระบบ

## หลักสำคัญ

- เอกสารภายในทั้งหมดอยู่ภายใต้ `docs/internal`
- เอกสารเผยแพร่ภายนอกอยู่ภายใต้ `docs/public`
- ห้ามย้ายเอกสาร internal ไปไว้ `docs/public`
- ทุกการเปลี่ยนแปลงที่กระทบ lifecycle/validation/ACL/route/cron/schema ต้องอัปเดต:
  - `docs/internal/01_SYSTEM/system_current_state.md`
  - `docs/internal/02_DECISIONS/decision_log.md`
  - เอกสาร domain/plan ที่เกี่ยวข้อง

## ลำดับการทำงานก่อนแก้ระบบ

1. อ่าน `docs/internal/01_SYSTEM/system_current_state.md`
2. อ่าน `docs/internal/02_DECISIONS/decision_log.md`
3. อ่าน `docs/internal/02_DECISIONS/adr_baseline.md`
4. อ่าน `docs/internal/02_DECISIONS/adr_index_by_domain.md`
5. อ่านเอกสาร domain/plan ที่เกี่ยวข้อง
6. ถ้าโค้ดจริงไม่ตรงเอกสาร ให้รายงาน mismatch ก่อนลงมือแก้

## กฎการเขียนเอกสาร

- ภาษาไทยเป็นหลัก
- ใช้ English เฉพาะชื่อ field, method/function และ technical keyword ที่ไม่ควรแปล
- หลีกเลี่ยง duplicate document โดยให้มี source-of-truth ต่อหัวข้อ

## เอกสารเดิมที่ถูกเก็บ archive

กฎ/แนวทางเวอร์ชันเก่าถูกย้ายไป:

- `docs/internal/05_ARCHIVE/rules/`
