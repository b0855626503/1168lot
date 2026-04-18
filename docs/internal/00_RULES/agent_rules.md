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

## Targeted Lookup Policy (บังคับใช้)

- เป้าหมายหลัก: หา context แบบ "แคบและลึก" เฉพาะ task ปัจจุบัน
- ห้าม scan โค้ดทั้งระบบแบบกว้าง ถ้ายังไม่ได้ทำ targeted search ก่อน
- ต้องเริ่มจาก "จุดเข้าใช้งานจริง" (route/controller/service/repository) ก่อนขยาย scope
- จำกัด context ที่อ่านให้พอ answer task เท่านั้น และหยุดเมื่อได้หลักฐานเพียงพอ
- ทุกครั้งที่เปิดไฟล์เพิ่ม ต้องตอบได้ว่าไฟล์นั้นเกี่ยวกับ task อย่างไร

## Unified Sync Policy (บังคับใช้)

- เมื่อโค้ดเปลี่ยนที่กระทบ behavior/structure ต้องอัปเดตพร้อมกัน:
  - docs (`/docs/*.md`)
  - memory layer (`.codebase-memory/` หรือ `memory/`)
  - octocode index layer (`.ai/mcp/index-build.json`)
- อัปเดตไม่ครบถือว่า invalid state
- ตรวจด้วย:
  - `bash scripts/docs-validation/check-code-doc-drift.sh`
  - `bash scripts/docs-validation/check-semantic-sync.sh`
  - `bash scripts/docs-validation/check-octocode-index-sync.sh`
  - `bash scripts/docs-validation/check-unified-sync.sh`

## Retrieval Order Policy (Memory First)

- ห้ามเปิด doc ใหญ่เป็น default entry point
- ต้องอ่าน memory layer ก่อนเสมอ:
  - `.codebase-memory/SUMMARY.md`
  - `memory/<domain>.md`
- ค่อยเปิด docs เฉพาะ section ที่จำเป็นเมื่อ memory ยังไม่พอ
- ถ้าต้องตรวจหลักฐาน retrieval consistency ให้เปิด `docs/internal/01_SYSTEM/retrieval_system_status.md`

### ลำดับการค้นหาโค้ด (ต้องทำตามลำดับนี้)

1. หา endpoint/command/class name จากโจทย์
2. ใช้ targeted search หาไฟล์ที่ประกาศจริง
3. อ่านเฉพาะ function/method ที่เกี่ยวข้อง
4. ค่อยตาม call chain เฉพาะเส้นที่แตะ behavior นั้น
5. ถ้าความเสี่ยงสูงค่อย escalate ไปไฟล์ state/decision ขนาดใหญ่

### คำสั่งที่แนะนำสำหรับ targeted lookup

- `rg -n "<keyword>" <path>`
- `rg -n "function <name>|public function <name>" <path>`
- `rg -n "Route::(get|post|put|delete)|->name\\(" packages/Gametech/FrontendApi/src/Routes`
- `rg -n "<ClassName>" app packages`
- `sed -n '<start>,<end>p' <file>`

## กฎการเขียนเอกสาร

- ภาษาไทยเป็นหลัก
- ใช้ English เฉพาะชื่อ field, method/function และ technical keyword ที่ไม่ควรแปล
- หลีกเลี่ยง duplicate document โดยให้มี source-of-truth ต่อหัวข้อ

## เอกสารเดิมที่ถูกเก็บ archive

กฎ/แนวทางเวอร์ชันเก่าถูกย้ายไป:

- `docs/internal/05_ARCHIVE/rules/`
