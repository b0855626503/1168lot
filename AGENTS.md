# AGENTS.md

## 🔰 Startup Instruction

ก่อนทำงานทุกครั้ง ต้องทำตามนี้:

1. อ่าน docs/START_HERE.md
2. อ่าน docs/internal/00_RULES/agent_rules.md
3. อ่าน docs/internal/01_SYSTEM/system_current_state.md
4. อ่าน docs/internal/02_DECISIONS/decision_log.md
ห้ามเริ่ม implement จนกว่าจะอ่านเอกสารครบตามลำดับ
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

---

## ❌ Prohibitions

* ห้ามเดาระบบ
* ห้ามแก้ behavior โดยไม่อัปเดต doc
* ห้ามข้ามการอ่านเอกสาร

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
ให้พิจารณา แต่ละครั้งที่ได้รับ ว่า ควรแบ่งงาน ให้ SubAgent ช่วยทำด้วยหรือไม่
ถ้า แบ่งแล้ว งานไวขึ้น ดีขึ้น ให้ดำเนินการ แบ่งได้เลย

## 🧩 Sub-Agent Decomposition (MANDATORY)

ก่อนเริ่ม implement ทุก task ต้องประเมินความซับซ้อนของงาน:

ถ้าเข้าเงื่อนไขข้อใดข้อหนึ่งต่อไปนี้:
- มีมากกว่า 1 domain (เช่น backend + UI + infra)
- มีมากกว่า 3 implementation steps
- มี dependency ระหว่าง component
- มี async/queue/job/pipeline involved
- มี state machine / retry logic

👉 ต้องแตกเป็น Sub-Agents ก่อนทันที

---

### Required Output

ต้องสร้าง "Sub-Agent Plan" ก่อน implement:

- แบ่งงานเป็นหน่วยย่อย (sub-agents)
- แต่ละ sub-agent ต้องมี:
    - name
    - responsibility
    - input/output
    - dependency

ตัวอย่าง:

Sub-Agent Plan:
1. BrowserRuntimeAgent
    - responsibility: implement playwright execution
2. FetchDriverAgent
    - responsibility: orchestration + receipt cache
3. PipelineAgent
    - responsibility: integrate NOT_READY flow

---

### Execution Rule

- ห้าม implement จนกว่าจะมี Sub-Agent Plan
- ต้อง execute ทีละ sub-agent ตาม dependency
- ถ้ามี bug → แก้ใน sub-agent scope เท่านั้น

---

### Goal

- ลด complexity ต่อ agent
- เพิ่ม parallel thinking
- ลด regression ข้าม component
