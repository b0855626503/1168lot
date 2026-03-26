# Documentation Overview

เอกสารทั้งหมดของระบบนี้อยู่ภายใต้โฟลเดอร์ `/docs`

---

## 🗂️ โครงสร้างเอกสาร

### internal/

ใช้สำหรับการพัฒนาและ agent (ห้าม expose)

* `00_RULES/` → กติกาการทำงานของ agent
* `01_SYSTEM/` → สภาพระบบปัจจุบัน (source of truth)
* `02_DECISIONS/` → decision ที่ล็อกแล้ว
* `03_DOMAINS/` → logic ของแต่ละ domain
* `04_PLANS/` → แผนงาน (active/pending/done)
* `05_ARCHIVE/` → เอกสารเก่า

---

### public/

ใช้สำหรับเอกสารที่เปิดผ่าน URL

* `api/`
* `integration/`

---

## 📌 จุดเริ่มต้น

### สำหรับ Developer

* อ่าน `internal/01_SYSTEM/system_current_state.md`

### สำหรับ Agent

* เริ่มที่ `START_HERE.md`

---

## ⚠️ หมายเหตุ

* เอกสารใน internal ถือเป็น source of truth
* ห้ามแก้ logic โดยไม่อัปเดตเอกสาร
* ห้ามนำ internal docs ไปเปิด public
