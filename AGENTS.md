# AGENTS.md

## 🔰 Startup Instruction

ก่อนทำงานทุกครั้ง ต้องทำตามนี้:

1. อ่าน docs/START_HERE.md
2. อ่าน docs/internal/00_RULES/agent_rules.md
3. อ่าน docs/internal/01_SYSTEM/startup_digest.md
4. อ่าน docs/internal/02_DECISIONS/adr_baseline.md
5. อ่าน docs/internal/02_DECISIONS/adr_index_by_domain.md
6. อ่าน docs/04_PLANS/README.md

จากนั้นให้อ่านเฉพาะ domain note ที่เกี่ยวข้องใน `docs/internal/03_DOMAINS/`

ห้ามเริ่ม implement จนกว่าจะอ่าน core startup ครบตามลำดับ
ห้ามเปิด `system_current_state.md` และ `decision_log.md` ทั้งก้อนโดยอัตโนมัติทุกงาน
ให้เปิดเพิ่มเฉพาะเมื่อ task มีความเสี่ยงสูง, จะเปลี่ยน behavior, หรือ domain note ไม่พอ
---

## 📚 Source of Truth

* เอกสารใน /docs คือ source of truth
* ห้ามใช้ chat history เป็นหลัก
* ถ้า code ไม่ตรง doc → ต้องรายงานก่อน

---

## 🔄 Workflow

* หาแผนล่าสุดใน docs/04_PLANS/README.md
* ทำงานตามแผนเท่านั้น
* ห้ามทำ feature นอก plan
* เวลาตอบ ให้ตอบสั้นๆ ไม่ต้องอธิบายเยอะ 
* ทุกอย่างให้ minimal output เพื่อลดการใช้ Token 
* ถ้าให้แก้ Code แล้วมี จุดอื่นที่เกี่ยวข้อง ที่ควรแก้ด้วย ก็ถามมาเลย อย่ารอให้บอกก่อน 
* ไม่ต้องรอให้บอกให้แก้ก่อน ถ้าเห็นว่าควรแก้ก็แก้ไปเลย แต่ต้องอัปเดต doc ให้ตรงกันด้วย 
* ไม่ต้องอธิบายเยอะ ถ้าไม่จำเป็น ให้ตอบสั้นๆ ตรงประเด็น และอัปเดต doc ให้ตรงกันด้วย 
* ถ้าเจอปัญหา หรืออะไรที่ ต้องเลือกหรือต้องตัดสินใจ ให้ถามมาเลย 
* พยายามให้ แต่ละครั้ง ใช้ token น้อยที่สุด ในส่วนของการที่ใช้สื่อสารกัน เพราะ token ที่ใช้ในการสื่อสารกัน ก็มีผลต่อค่าใช้จ่ายด้วย

---

## ❌ Prohibitions

* ห้ามเดาระบบ
* ห้ามแก้ behavior โดยไม่อัปเดต doc
* ห้ามข้ามการอ่านเอกสาร
* ห้ามใช้ startup flow แบบ heavy โดยไม่จำเป็น

---

## 🧪 Lotto Parser Check Command Contract (MANDATORY)

เมื่อ user พิมพ์คำสั่งรูปแบบนี้:

- `check <url> <draw_date> <first_prize> <last_2_raw>`

ให้ถือว่าเป็นคำสั่งของ skill `lotto-parser-config-generator` ทันที และต้องตอบตาม output contract ของ skill เท่านั้น
(ห้ามตอบแบบสรุปข้อความธุรกิจอย่างเดียว)
และต้องยึด schema ตามไฟล์:

- `docs/internal/00_RULES/lotto_check_output_contract.md`

### Output Rules (Strict)

- positional ครบ 4 ค่า ต้องเข้าโหมด `EXHAUSTIVE`
- ต้องส่ง `config` แยกคนละ code block ตามหัวข้อ:
  - `PAGE JSON`
  - `PAGE DOM (CSS)`
  - `PAGE REGEX`
  - `ENDPOINT JSON`
  - `ENDPOINT DOM (CSS)`
  - `ENDPOINT REGEX`
- ถ้าแบบใดไม่ feasible ต้องส่ง block ของแบบนั้นเป็น JSON error object:
  - `feasible=false`
  - `error_code`
  - `message`
- หลัง config ครบแล้ว ต้องส่ง self-test summary รวมอีก 1 code block
- ใน self-test ต้องมีอย่างน้อย:
  - `raw_result`
  - `transformed_result`
  - `validation_result`
  - `passed`

### Reject Rule

- ถ้า output ไม่ครบ 6 config blocks + 1 self-test summary block ให้ถือว่า "ผิด format" และต้องแก้ให้ถูกก่อนจบคำตอบ
- ถ้า shape ของ `json หลัก` ไม่ตรง "Minimum Required Schema" ใน contract file ให้ถือว่า "ผิด schema" และต้อง regenerate ก่อนส่ง
- อนุญาตเพิ่ม nested fields ได้เมื่อระบบรองรับจริง แต่ห้ามเพิ่ม top-level key ใหม่เอง

### Preflight Checklist (Before Send)

ก่อนส่งผลคำสั่ง `check` ทุกครั้ง ต้อง verify ให้ครบ:

1. block ครบตามลำดับใน contract file
2. top-level keys ครบทั้ง 10 keys ในทุก config block
3. `request_headers_json/request_query_template_json/request_body_template_json` เป็น array
4. `mapping_config_json.fields` เป็น object
5. มี `raw_result/transformed_result/validation_result/passed` ใน self-test summary

---

## 🎯 Goal

ทำให้ agent สามารถทำงานต่อได้โดยไม่ต้องมี context จากแชตก่อนหน้า
ให้เน้นความเร็วเป็นหลัก ภายใต้กติกา source of truth เดิม
ให้พิจารณา แต่ละครั้งที่ได้รับ ว่า การแบ่งงานจะช่วยให้งานเสร็จไวขึ้นจริงหรือไม่

## ⚡ Speed-First Execution

หลักการ:

- งานเล็ก ให้ทำตรง ๆ อย่าเพิ่ม coordination โดยไม่จำเป็น
- งานกลาง ให้ทำ `Task Plan` สั้น ๆ ก่อนลงมือ
- งานใหญ่หรือแยก ownership ได้ชัด ค่อยใช้ sub-agent
- เป้าหมายคือ “เสร็จไวและพลาดน้อย” ไม่ใช่ “แตก agent ให้ครบตามพิธี”

### 1. งานเล็ก: ทำเองได้เลย

เข้ากลุ่มนี้เมื่อ:

- แก้ไม่เกินประมาณ 3 ไฟล์
- อยู่ใน domain เดียว
- ไม่มี async/queue/job/pipeline/state machine
- ไม่มี prod diagnosis + code change + doc change หลายชั้นพร้อมกัน

กติกา:

- ไม่ต้อง spawn sub-agent
- ไม่ต้องทำ Sub-Agent Plan
- ถ้าไม่ trivial ให้สรุป `Task Plan` สั้น ๆ 1 ชุดก่อนลงมือ

### 2. งานกลาง: ใช้ Task Plan

เข้ากลุ่มนี้เมื่อ:

- มีหลาย step แต่ยังทำคนเดียวได้ไว
- เช่น code + test + doc ใน scope เดียว
- หรือมี dependency บางส่วน แต่ write scope ยังซ้อนกันเยอะ

กติกา:

- ต้องมี `Task Plan` ก่อน implement
- ยังไม่ต้อง spawn sub-agent ถ้า coordination cost สูงกว่าประโยชน์

รูปแบบ:

Task Plan:
1. อ่านจุดที่เกี่ยวข้อง
2. แก้ code
3. รัน test
4. อัปเดต doc

### 3. งานใหญ่: ค่อยใช้ Sub-Agent Plan

ใช้เมื่อ “แบ่งแล้วเร็วขึ้นจริง” เท่านั้น โดยปกติควรเข้าเงื่อนไขอย่างน้อย 1 ข้อ:

- มีมากกว่า 1 domain และแยก ownership ได้
- มี async/queue/job/pipeline/state machine
- ต้องไล่ prod evidence ควบกับการแก้ code
- มี sidecar work ที่ทำคู่ขนานได้โดยไม่ block critical path
- มี write scope แยกกันชัดเจน เช่น backend คนละ module / UI คนละหน้า / test คนละชุด

### 4. เกณฑ์ว่าไม่ควรใช้ Sub-Agent

แม้งานจะหลาย step แต่ไม่ควร spawn ถ้า:

- งานหลักยังไม่ชัด
- จุดแก้หลักอยู่ไฟล์เดียวหรือโมดูลเดียว
- sub-agent ต้องแตะไฟล์ชุดเดียวกับ main agent
- ผลลัพธ์ของ sub-agent เป็น blocker ทันทีของ step ถัดไป
- coordination cost มากกว่าลงมือทำเอง

### 5. ถ้าจะใช้ Sub-Agent ต้องระบุให้ชัด

Sub-Agent Plan:
1. name
2. responsibility
3. input/output
4. dependency
5. write scope

กติกา:

- อย่า spawn เพื่อ “ช่วยอ่านเฉย ๆ” ถ้า main agent อ่านเองได้ไวกว่า
- spawn เฉพาะ sidecar task ที่ bounded และไม่ซ้อน write scope
- ถ้างาน coupled มาก ให้ main agent ทำเองพร้อม plan แทน

### 6. Default ที่ต้องใช้ทุกครั้ง

- ทุกงานต้องประเมินก่อนว่าเป็น `งานเล็ก / งานกลาง / งานใหญ่`
- ถ้าไม่ trivial อย่างน้อยต้องมี `Task Plan`
- ใช้ `Sub-Agent Plan` เฉพาะเมื่อมั่นใจว่าเร็วกว่าการทำเอง
