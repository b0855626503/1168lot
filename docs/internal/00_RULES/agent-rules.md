# Agent Rules (Source of Truth)

เอกสารนี้เป็นกฎกลางสำหรับการทำงานของ agent ในโปรเจกต์นี้
และเป็นไฟล์หลักที่ต้องอ้างอิงก่อนเริ่มแก้ระบบ

## หลักสำคัญ

- เอกสารภายในทั้งหมดอยู่ภายใต้ `docs/internal`
- เอกสารเผยแพร่ภายนอกอยู่ภายใต้ `docs/public`
- ห้ามย้ายเอกสาร internal ไปไว้ `docs/public`
- ทุกการเปลี่ยนแปลงที่กระทบ lifecycle/validation/ACL/route/cron/schema ต้องอัปเดต:
  - `docs/internal/01_SYSTEM/system-current-state/index.md`
  - `docs/internal/02_DECISIONS/decision-log/index.md`
  - `docs/internal/01_SYSTEM/system-current-state.md` (entrypoint/compat)
  - `docs/internal/02_DECISIONS/decision-log.md` (entrypoint/compat)
  - เอกสาร domain/plan ที่เกี่ยวข้อง

## Startup Path

→ ดู `docs/START-HERE.md` สำหรับ startup path ที่กำหนดไว้

สรุปย่อ:
1. `docs/START-HERE.md`
2. `boat_ask(question="system overview + architecture map")` — quick code intelligence via Boat MCP
3. `docs/internal/01_SYSTEM/system-map.md`
4. domain note ที่เกี่ยวข้อง 1 ไฟล์
5. Code Discovery — `docs/internal/00_RULES/code-discovery-protocol.md`

เปิด `system-current-state/index.md` และ `decision-log/index.md` เฉพาะเมื่อ high-risk หรือ mismatch

## หลักการลด token โดยไม่ลดคุณภาพ

- ห้ามอ่านไฟล์ใหญ่ทั้งก้อนทุกครั้งโดยไม่มีเหตุจำเป็น
- ใช้ `boat_ask` + `system_map + domain note` เป็น default path
- ใช้ `system-current-state` และ `decision-log` เป็น escalation path ตาม risk
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
  - memory layer (`memory/`)
  - Boat MCP code intelligence (ใช้ `boat_ask` สำหรับ query/verify)
- อัปเดตไม่ครบถือว่า invalid state
- ตรวจด้วย:
  - `bash scripts/docs-validation/check-code-doc-drift.sh`
  - `bash scripts/docs-validation/check-semantic-sync.sh`
  - `bash scripts/docs-validation/check-unified-sync.sh`

**Synchronization Contract:**
การเปลี่ยนแปลงต่อไปนี้ต้องอัปเดต doc ด้วยเสมอ:
- เปลี่ยน route หรือ entrypoint → อัปเดต `docs/internal/01_SYSTEM/system-map.md`
- เปลี่ยน service / flow → อัปเดต `*_discovery.md` ที่เกี่ยวข้อง พร้อม Last Verified ใหม่
- เปลี่ยน table / schema → อัปเดต domain note + discovery map

## Retrieval Order Policy (Memory First)

- ห้ามเปิด doc ใหญ่เป็น default entry point
- ใช้ `boat_ask` + memory layer เป็น fast path เบื้องต้น:
  - `boat_ask(question="หา <เรื่อง>")` — code intelligence + memory
  - `memory/<domain>.md`
- ค่อยเปิด docs เฉพาะ section ที่จำเป็นเมื่อ memory ยังไม่พอ
- ถ้าต้องตรวจหลักฐาน retrieval consistency ให้เปิด `docs/internal/01_SYSTEM/retrieval-system-status.md`

**Memory boundary:**
- memory = keyword lookup + context เบื้องต้น
- ห้ามใช้ memory เป็นหลักฐานเพียงอย่างเดียวสำหรับตัดสินใจ architecture หรือ flow
- ถ้า memory stale → ให้ verify ด้วย code (`rg`, `git log`) หรือ `boat_ask` เสมอ

### ลำดับการค้นหาโค้ด (ต้องทำตามลำดับนี้)

1. หา endpoint/command/class name จากโจทย์
2. ใช้ `boat_ask` หรือ targeted search หาไฟล์ที่ประกาศจริง
3. อ่านเฉพาะ function/method ที่เกี่ยวข้อง
4. ค่อยตาม call chain เฉพาะเส้นที่แตะ behavior นั้น
5. ถ้าความเสี่ยงสูงค่อย escalate ไปไฟล์ state/decision ขนาดใหญ่

### คำสั่งที่แนะนำสำหรับ targeted lookup

- `boat_ask(question="หา <keyword> ในโค้ด")`
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
