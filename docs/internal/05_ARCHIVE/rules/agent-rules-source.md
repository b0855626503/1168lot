You are responsible for maintaining BOTH code and system documentation as a single source of truth.

All documentation MUST be written in Thai language (primary).
English is allowed ONLY for:

* field names
* method/function names
* technical keywords that should not be translated

Do NOT write full documentation in English.

---

# OBJECTIVE

รักษาเอกสารระบบให้เป็นปัจจุบันเสมอ เพื่อให้ agent คนถัดไปเข้าใจระบบได้ทันทีโดยไม่ต้องอ่านแชตย้อนหลัง

---

# REQUIRED DOCUMENTS

ต้องมีไฟล์เหล่านี้ในระบบ:

1. docs/internal/01_SYSTEM/system_current_state.md
2. docs/internal/02_DECISIONS/decision_log.md
3. docs/internal/03_DOMAINS/<domain>.md
4. docs/04_PLANS/YYYY-MM-DD_topic-name.md

---

# RULES BEFORE ANY CHANGE

ก่อนแก้ระบบ:

1. อ่าน:

    * docs/internal/01_SYSTEM/system_current_state.md
    * docs/internal/02_DECISIONS/decision_log.md
    * docs/internal/03_DOMAINS/*.md ที่เกี่ยวข้อง

2. ใช้เอกสารเหล่านี้เป็น source of truth เท่านั้น

3. ถ้าโค้ดจริงไม่ตรงกับเอกสาร:

    * ห้ามแก้ทันที
    * ต้องรายงาน mismatch ก่อน

---

# RULES DURING IMPLEMENTATION

ถ้างานมีผลต่อสิ่งเหล่านี้:

* lifecycle / state
* ความหมาย field
* validation
* permission / ACL
* route / API
* cron / automation
* schema / database

คุณ MUST:

---

## 1. อัปเดต docs/internal/02_DECISIONS/decision_log.md

ต้องเพิ่ม entry ใหม่:

* วันที่
* เรื่องที่ตัดสินใจ
* สรุป decision (สั้น ชัด)
* ของเดิมคืออะไร
* ของใหม่คืออะไร
* กระทบไฟล์/โมดูลไหน
* สถานะ: LOCKED

ห้ามแก้ entry เก่า ให้เพิ่มใหม่เท่านั้น

---

## 2. อัปเดต docs/internal/01_SYSTEM/system_current_state.md

* ต้องสะท้อน “ระบบปัจจุบันจริง”
* ห้ามเขียนเป็นแผน
* ลบข้อมูลเก่าที่ไม่ใช้แล้ว

---

## 3. อัปเดต docs/internal/03_DOMAINS/<domain>.md

สำหรับ domain เช่น Lotto:

ต้องมี:

* lifecycle/state
* rule การทำงาน
* ความหมาย field
* constraint สำคัญ
* edge case

---

## 4. อัปเดต docs/04_PLANS/YYYY-MM-DD_topic-name.md

ต้องมี:

* เป้าหมาย
* scope
* out of scope
* ขั้นตอน implement
* ไฟล์ที่กระทบ
* test plan
* acceptance criteria

---

# WRITING RULES (สำคัญมาก)

* เขียนภาษาไทยเป็นหลัก
* ห้ามเขียนกำกวม
* ห้ามสรุปแบบกว้างๆ
* ต้องระบุ:

    * อะไรเปลี่ยน
    * อะไรไม่เปลี่ยน
    * ทำไมถึงเปลี่ยน (สั้น)
* ใช้คำสั่งแบบ:

    * ต้อง (MUST)
    * ห้าม (MUST NOT)

---

# DECISION LOCK RULE

* สิ่งที่ LOCKED แล้ว ห้ามเปลี่ยนเงียบ
* ถ้าจะเปลี่ยน ต้องสร้าง decision ใหม่
* ห้าม overwrite decision เก่า

---

# AFTER IMPLEMENTATION

ก่อนจบงาน:

1. ตรวจว่า:

    * docs/internal/01_SYSTEM/system_current_state.md ตรงกับโค้ดจริง
    * docs/internal/02_DECISIONS/decision_log.md มี decision ใหม่ครบ
    * DOMAIN docs อัปเดตแล้ว
    * WORK_PLAN ครบ

2. ต้องไม่มี:

    * rule เก่าที่ยังหลงเหลือ
    * ข้อมูลที่ไม่ตรงกับระบบจริง

---

# PROHIBITIONS

* ห้ามแก้ระบบโดยไม่อัปเดตเอกสาร
* ห้ามปล่อยเอกสารล้าสมัย
* ห้ามเดาพฤติกรรมระบบ ต้องอ้างอิงจากเอกสาร
* ห้ามรวม current state กับ plan ไว้ไฟล์เดียว

---

# FINAL GOAL

Agent คนใหม่ต้องสามารถ:

* อ่านเอกสารอย่างเดียว
* เข้าใจระบบ
* ทำงานต่อได้ทันที

โดยไม่ต้องอ่านแชตย้อนหลัง
