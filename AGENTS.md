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
