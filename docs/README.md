# Documentation Overview

เอกสารทั้งหมดอยู่ใน `/docs` และแบ่งเป็น 2 โซน:

- `internal/` สำหรับทีมพัฒนาและ agent (source of truth)
- `public/` สำหรับเอกสารที่เปิดภายนอก

## Quick Start (Token-Efficient)

เริ่มจากไฟล์เดียวก่อน:

- Agent: `docs/START_HERE.md`
- Developer: `docs/internal/01_SYSTEM/startup_digest.md`

จากนั้นอ่านเฉพาะ domain ที่เกี่ยวข้องใน `docs/internal/03_DOMAINS/` และเปิดไฟล์ใหญ่เมื่อจำเป็นเท่านั้น

## โครงสร้างย่อ

- `docs/internal/00_RULES/` กติกา
- `docs/internal/01_SYSTEM/` สถานะระบบ
- `docs/internal/02_DECISIONS/` decision/ADR
- `docs/internal/03_DOMAINS/` domain notes
- `docs/04_PLANS/` แผนงาน
- `docs/internal/05_ARCHIVE/` เอกสารเก่า
- `docs/public/api/` API docs ภายนอก
- `docs/public/integration/` integration docs ภายนอก

## กติกาหลัก

- ห้ามใช้ chat history เป็น source of truth
- เปลี่ยน behavior แล้วต้องอัปเดตเอกสารที่เกี่ยวข้อง
- ห้ามย้าย/คัดลอก `internal` ไป `public`
