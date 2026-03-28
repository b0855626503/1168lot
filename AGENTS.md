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
