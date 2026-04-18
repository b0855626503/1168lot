# System Current State (Index)

อัปเดตล่าสุด: 2026-04-18

เอกสารนี้เป็น entry point ใหม่แทน monolith เดิม เพื่อให้ agent เปิดเฉพาะ section ที่จำเป็นต่อ task

## Sections

- [01-overview.md](./01-overview.md): baseline runtime, source-of-truth, domain map
- [02-flows.md](./02-flows.md): critical flows ที่กระทบ behavior จริง
- [03-endpoints.md](./03-endpoints.md): API/Admin endpoint contracts ที่ใช้งานจริง
- [04-edge-cases.md](./04-edge-cases.md): compatibility, fallback, failure-handling

## ใช้เมื่อ

- ต้องตัดสินใจเชิง behavior หรือ high-risk changes
- ต้องยืนยันว่า flow ปัจจุบันยังตรงกับ decision ล่าสุด

## Legacy Full Dump

รายละเอียดเดิมทั้งก้อนถูกเก็บที่:
- `docs/internal/05_ARCHIVE/monolith/system_current_state.2026-04-18.md`
