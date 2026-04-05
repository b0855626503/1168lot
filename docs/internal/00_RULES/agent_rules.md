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

## Startup Core (ต้องอ่านทุกครั้ง)

1. `docs/internal/01_SYSTEM/startup_digest.md`
2. `docs/internal/02_DECISIONS/adr_baseline.md`
3. `docs/internal/02_DECISIONS/adr_index_by_domain.md`
4. `docs/04_PLANS/README.md`

## ลำดับการอ่านต่อแบบ on-demand

1. อ่าน domain note ที่เกี่ยวข้องใน `docs/internal/03_DOMAINS/`
2. ถ้างานจะเปลี่ยน behavior, แตะ high-risk flow, หรือ domain note ไม่พอ:
   - เปิด `docs/internal/01_SYSTEM/system_current_state.md`
   - เปิด `docs/internal/02_DECISIONS/decision_log.md`
3. ถ้าโค้ดจริงไม่ตรงเอกสาร ให้รายงาน mismatch ก่อนลงมือแก้

## หลักการลด token โดยไม่ลดคุณภาพ

- ห้ามอ่านไฟล์ใหญ่ทั้งก้อนทุกครั้งโดยไม่มีเหตุจำเป็น
- ใช้ `startup_digest + ADR + domain note` เป็น default path
- ใช้ `system_current_state` และ `decision_log` เป็น escalation path ตาม risk
- งานเล็กไม่ควรต้องแบก startup cost เท่างานใหญ่

## กฎการเขียนเอกสาร

- ภาษาไทยเป็นหลัก
- ใช้ English เฉพาะชื่อ field, method/function และ technical keyword ที่ไม่ควรแปล
- หลีกเลี่ยง duplicate document โดยให้มี source-of-truth ต่อหัวข้อ

## เอกสารเดิมที่ถูกเก็บ archive

กฎ/แนวทางเวอร์ชันเก่าถูกย้ายไป:

- `docs/internal/05_ARCHIVE/rules/`
